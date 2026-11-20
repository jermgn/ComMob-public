<?php

namespace App\AI;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class GroqKeywordGenerator implements KeywordGeneratorInterface
{
    public function __construct(
        private HttpClientInterface $client,
        private string $apiKey,
        private LoggerInterface $logger
    ) {}

    public function generate(string $contextLabel): array
    {
        $this->logger->info(' GROQ generate appelé', [
            'context' => $contextLabel,
        ]);

        $prompt = <<<PROMPT
Contexte : "{$contextLabel}"

Génère 10 mots-clés simples (1 mot par entrée, en français).
Réponds SEULEMENT avec le tableau JSON, aucun texte autour.
Format : ["mot1", "mot2", "mot3", "mot4", "mot5", "mot6", "mot7", "mot8", "mot9", "mot10"]
PROMPT;

        try {
            $response = $this->client->request(
                'POST',
                'https://api.groq.com/openai/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model' => 'llama-3.3-70b-versatile',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Tu réponds UNIQUEMENT avec du JSON valide, sans texte avant ou après.',
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                        'temperature' => 0.2,
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            $this->logger->info(' Réponse API Groq', [
                'status' => $statusCode,
                'has_choices' => isset($data['choices']),
            ]);

            if ($statusCode !== 200) {
                $errorMsg = $data['error']['message'] ?? 'Erreur inconnue';
                $this->logger->error(' Erreur API Groq', [
                    'status' => $statusCode,
                    'error' => $errorMsg,
                ]);
                return [];
            }

            $content = $data['choices'][0]['message']['content'] ?? '[]';

            $this->logger->info(' Contenu reçu de Groq', [
                'content' => $content
            ]);

    
            if (preg_match('/\[([^\]]+)\]/', $content, $matches)) {
                $jsonMatch = '[' . $matches[1] . ']';
                $this->logger->info(' JSON extrait', ['json' => $jsonMatch]);
                $keywords = json_decode($jsonMatch, true);
            } else {
                $keywords = json_decode($content, true);
            }

            if (!is_array($keywords)) {
                $this->logger->warning('Réponse invalide (pas un tableau)', [
                    'content' => $content
                ]);
                return [];
            }

            $result = array_slice(
                array_map('strtolower', $keywords),
                0,
                10
            );

            $this->logger->info(' Mots-clés générés avec succès', [
                'count' => count($result),
                'keywords' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Exception lors de l\'appel Groq', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            return [];
        }
    }
}
