<?php

namespace App\Controller;

use App\Entity\Dialogue;
use App\Entity\DialogueBlock;
use App\Repository\DialogueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dialogues')]
final class DialogueController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DialogueRepository $dialogueRepository,
    ) {}

    #[Route('', name: 'api_dialogues_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $dialogues = $this->dialogueRepository->findBy(['user' => $user], ['createdAt' => 'ASC']);

        return $this->json(array_map(fn(Dialogue $d) => $d->toArray(), $dialogues));
    }

    #[Route('', name: 'api_dialogues_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['name'], $data['contextType'])) {
            return $this->json(['error' => 'name et contextType requis'], 400);
        }

        $dialogue = new Dialogue();
        $dialogue->setUser($this->getUser());
        $dialogue->setName(trim($data['name']));
        $dialogue->setContextType(trim($data['contextType']));
        $dialogue->setContextLabel(isset($data['contextLabel']) ? trim($data['contextLabel']) : null);

        $this->em->persist($dialogue);
        $this->em->flush();

        return $this->json($dialogue->toArray(), 201);
    }

    #[Route('/{id}', name: 'api_dialogues_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $dialogue = $this->dialogueRepository->find($id);

        if ($dialogue === null || $dialogue->getUser()?->getId() !== $this->getUser()?->getId()) {
            return $this->json(['error' => 'Dialogue introuvable'], 404);
        }

        $this->em->remove($dialogue);
        $this->em->flush();

        return $this->json(null, 204);
    }

    #[Route('/{id}/blocks', name: 'api_dialogues_add_block', methods: ['POST'])]
    public function addBlock(int $id, Request $request): JsonResponse
    {
        $dialogue = $this->dialogueRepository->find($id);

        if ($dialogue === null || $dialogue->getUser()?->getId() !== $this->getUser()?->getId()) {
            return $this->json(['error' => 'Dialogue introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['text']) || trim($data['text']) === '') {
            return $this->json(['error' => 'text requis'], 400);
        }

        $block = new DialogueBlock();
        $block->setDialogue($dialogue);
        $block->setText(trim($data['text']));
        $block->setBlockOrder($dialogue->getBlocks()->count());

        $dialogue->touch();

        $this->em->persist($block);
        $this->em->flush();

        return $this->json($block->toArray(), 201);
    }

    #[Route('/{id}/blocks/{blockId}', name: 'api_dialogues_remove_block', methods: ['DELETE'])]
    public function removeBlock(int $id, int $blockId): JsonResponse
    {
        $dialogue = $this->dialogueRepository->find($id);

        if ($dialogue === null || $dialogue->getUser()?->getId() !== $this->getUser()?->getId()) {
            return $this->json(['error' => 'Dialogue introuvable'], 404);
        }

        $block = $dialogue->getBlocks()->findFirst(fn($k, $b) => $b->getId() === $blockId);

        if ($block === null) {
            return $this->json(['error' => 'Bloc introuvable'], 404);
        }

        $dialogue->getBlocks()->removeElement($block);
        $dialogue->touch();
        $this->em->remove($block);
        $this->em->flush();

        return $this->json(null, 204);
    }
}
