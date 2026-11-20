<?php

namespace App\Controller;

use App\AI\GroqPhraseGenerator;
use App\AI\GroqResponseGenerator;
use App\AI\UserContextBuilder;
use App\Arasaac\ArasaacClient;
use App\Context\ContextResolverInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ContextController extends AbstractController
{
    public function __construct(
        private readonly ContextResolverInterface $contextResolver,
        private readonly ArasaacClient $arasaac,
        private readonly GroqPhraseGenerator $phraseGenerator,
        private readonly GroqResponseGenerator $responseGenerator,
        private readonly UserContextBuilder $userContextBuilder,
    ) {}

    private function getUserContext(): string
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return '';
        }
        return $this->userContextBuilder->build($user);
    }

    /**
     * Retourne la liste des contextes possibles selon la position
     */
    #[Route('/contexts', name: 'api_contexts', methods: ['POST'])]
    public function contexts(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            !is_array($data) ||
            !isset($data['latitude'], $data['longitude'])
        ) {
            return $this->json(
                ['error' => 'latitude and longitude are required'],
                400
            );
        }

        $lat = (float) $data['latitude'];
        $lon = (float) $data['longitude'];

        $contexts = $this->contextResolver->resolveCandidates($lat, $lon);

        return $this->json($contexts);
    }

    /**
     * Retourne tous les contextes disponibles (sélection manuelle)
     */
    #[Route('/all-contexts', name: 'api_all_contexts', methods: ['GET'])]
    public function allContexts(): JsonResponse
    {
        $allContexts = $this->contextResolver->getAllContexts();
        return $this->json($allContexts);
    }

    /**
     * Retourne les pictogrammes d'un contexte précis
     */
    #[Route('/context/{id}', name: 'api_context', methods: ['GET'])]
    public function context(string $id): JsonResponse
    {
        $result = $this->contextResolver->resolveContext($id);

        return $this->json([
            'context' => $result->context,
            'keywords' => $result->keywords,
            'confidence' => $result->confidence,
        ]);
    }

    /**
     * Recherche des pictogrammes par mot-clé
     */
    #[Route('/search', name: 'api_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (empty($query) || strlen($query) < 2) {
            return $this->json(
                ['error' => 'La recherche doit contenir au moins 2 caractères'],
                400
            );
        }

        $results = $this->arasaac->searchPictograms($query, 20);

        return $this->json([
            'query' => $query,
            'results' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Génère une phrase à partir de mots-clés sélectionnés
     */
    #[Route('/generate-phrase', name: 'api_generate_phrase', methods: ['POST'])]
    public function generatePhrase(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['words']) || !is_array($data['words'])) {
            return $this->json(
                ['error' => 'Le paramètre "words" est requis et doit être un tableau'],
                400
            );
        }

        $words = array_filter($data['words'], fn($w) => is_string($w) && !empty(trim($w)));

        if (empty($words)) {
            return $this->json(
                ['error' => 'Au moins un mot est requis'],
                400
            );
        }

        $phrase = $this->phraseGenerator->generate(array_values($words), $this->getUserContext());

        return $this->json([
            'words' => $words,
            'phrase' => $phrase,
        ]);
    }

    /**
     * Génère des réponses possibles basées sur ce que l'interlocuteur a dit
     */
    #[Route('/generate-responses', name: 'api_generate_responses', methods: ['POST'])]
    public function generateResponses(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['context']) || !isset($data['speech'])) {
            return $this->json(['error' => 'context and speech are required'], 400);
        }

        $context = $data['context'];
        $speech = $data['speech'];

        $responses = $this->responseGenerator->generateResponses($context, $speech, $this->getUserContext());

        return $this->json([
            'context' => $context,
            'speech' => $speech,
            'responses' => $responses,
        ]);
    }

    /**
     * Développe une réponse en une version plus détaillée
     */
    #[Route('/expand-response', name: 'api_expand_response', methods: ['POST'])]
    public function expandResponse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['context']) || !isset($data['response'])) {
            return $this->json(['error' => 'context and response are required'], 400);
        }

        $expanded = $this->responseGenerator->expandResponse($data['context'], $data['response'], $this->getUserContext());

        return $this->json([
            'original' => $data['response'],
            'expanded' => $expanded,
        ]);
    }

    /**
     * Modifie complètement une réponse en générant une alternative
     */
    #[Route('/modify-response', name: 'api_modify_response', methods: ['POST'])]
    public function modifyResponse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['context']) || !isset($data['speech']) || !isset($data['response'])) {
            return $this->json(['error' => 'context, speech and response are required'], 400);
        }

        $modified = $this->responseGenerator->modifyResponse($data['context'], $data['speech'], $data['response'], $this->getUserContext());

        return $this->json([
            'original' => $data['response'],
            'modified' => $modified,
        ]);
    }
}