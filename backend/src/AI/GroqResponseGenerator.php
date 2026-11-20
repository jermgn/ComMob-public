<?php

namespace App\AI;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class GroqResponseGenerator
{
    public function __construct(
        private HttpClientInterface $client,
        private string $apiKey,
        private LoggerInterface $logger
    ) {}

    public function generateResponses(string $context, string $interlocutorSpeech, string $userContext = ''): array
    {
        $this->logger->info('Generating responses from interlocutor speech', [
            'context' => $context,
            'interlocutorSpeech' => $interlocutorSpeech,
        ]);

        $prompt = <<<PROMPT
Contexte : "{$context}"
Un interlocuteur (commerçant, employé, etc.) a dit : "{$interlocutorSpeech}"

L'utilisateur utilise une application de communication alternative (AAC) et doit répondre à cette personne.
Génère 4 réponses possibles que l'utilisateur pourrait vouloir donner.
Les réponses doivent être des phrases complètes, polies et naturelles adaptées au contexte.
Varie les réponses (positive, négative, question, demande de précision).
Chaque réponse doit faire maximum 12 mots.

Réponds UNIQUEMENT avec un tableau JSON, sans texte autour.
Format : ["réponse1", "réponse2", "réponse3", "réponse4"]
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
                                'content' => 'Tu réponds UNIQUEMENT avec du JSON valide, sans texte avant ou après.'
                                    . ($userContext !== '' ? "\n\n" . $userContext : ''),
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                        'temperature' => 0.7,
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode !== 200) {
                $this->logger->error('Groq API error', [
                    'status' => $statusCode,
                    'error' => $data['error']['message'] ?? 'Unknown error',
                ]);
                return $this->getFallbackResponses($context);
            }

            $content = $data['choices'][0]['message']['content'] ?? '[]';

            if (preg_match('/\[([^\]]+)\]/', $content, $matches)) {
                $jsonMatch = '[' . $matches[1] . ']';
                $responses = json_decode($jsonMatch, true);
            } else {
                $responses = json_decode($content, true);
            }

            if (!is_array($responses) || count($responses) < 2) {
                return $this->getFallbackResponses($context);
            }

            return array_slice($responses, 0, 4);

        } catch (\Exception $e) {
            $this->logger->error('Exception calling Groq', [
                'message' => $e->getMessage(),
            ]);
            return $this->getFallbackResponses($context);
        }
    }

    /**
     * Développe une réponse courte en une réponse plus détaillée
     */
    public function expandResponse(string $context, string $response, string $userContext = ''): string
    {
        $this->logger->info('Expanding response', [
            'context' => $context,
            'response' => $response,
        ]);

        $prompt = <<<PROMPT
Contexte : "{$context}"
Réponse courte de l'utilisateur : "{$response}"

L'utilisateur utilise une application de communication alternative (AAC).
Développe cette réponse en une phrase plus complète et détaillée, tout en gardant le même sens.
La réponse développée doit faire entre 15 et 25 mots maximum.
Elle doit rester naturelle, polie et adaptée au contexte.

Réponds UNIQUEMENT avec la phrase développée, sans guillemets ni texte autour.
PROMPT;

        try {
            $apiResponse = $this->client->request(
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
                                'content' => 'Tu réponds UNIQUEMENT avec la phrase demandée, sans texte avant ou après.'
                                    . ($userContext !== '' ? "\n\n" . $userContext : ''),
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                        'temperature' => 0.7,
                    ],
                ]
            );

            $data = $apiResponse->toArray(false);
            $content = $data['choices'][0]['message']['content'] ?? $response;

            return trim($content, " \n\r\t\"'");

        } catch (\Exception $e) {
            $this->logger->error('Exception expanding response', ['message' => $e->getMessage()]);
            return $response;
        }
    }

    /**
     * Modifie complètement une réponse en générant une alternative
     */
    public function modifyResponse(string $context, string $interlocutorSpeech, string $originalResponse, string $userContext = ''): string
    {
        $this->logger->info('Modifying response', [
            'context' => $context,
            'interlocutorSpeech' => $interlocutorSpeech,
            'originalResponse' => $originalResponse,
        ]);

        $prompt = <<<PROMPT
Contexte : "{$context}"
L'interlocuteur a dit : "{$interlocutorSpeech}"
Réponse originale (que l'utilisateur ne veut pas) : "{$originalResponse}"

L'utilisateur utilise une application de communication alternative (AAC).
Génère une réponse COMPLÈTEMENT DIFFÉRENTE de la réponse originale.
La nouvelle réponse doit être adaptée au contexte et à ce que l'interlocuteur a dit.
Elle doit faire maximum 12 mots.

Réponds UNIQUEMENT avec la nouvelle phrase, sans guillemets ni texte autour.
PROMPT;

        try {
            $apiResponse = $this->client->request(
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
                                'content' => 'Tu réponds UNIQUEMENT avec la phrase demandée, sans texte avant ou après.'
                                    . ($userContext !== '' ? "\n\n" . $userContext : ''),
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                        'temperature' => 0.9,
                    ],
                ]
            );

            $data = $apiResponse->toArray(false);
            $content = $data['choices'][0]['message']['content'] ?? $originalResponse;

            return trim($content, " \n\r\t\"'");

        } catch (\Exception $e) {
            $this->logger->error('Exception modifying response', ['message' => $e->getMessage()]);
            return $originalResponse;
        }
    }

    private function getFallbackResponses(string $context): array
    {
        $fallbacks = [
            'bakery' => ["Une baguette s'il vous plaît", "Je voudrais des croissants", "Un pain au chocolat", "Je regarde juste"],
            'restaurant' => ["Je voudrais commander", "L'addition s'il vous plaît", "Un verre d'eau", "Je n'ai pas encore choisi"],
            'pharmacy' => ["J'ai mal à la tête", "Je cherche du paracétamol", "J'ai une ordonnance", "Je voudrais des conseils"],
            'supermarket' => ["Où sont les fruits ?", "Je cherche le pain", "Où est la caisse ?", "Je regarde juste"],
            'transport' => ["Un ticket s'il vous plaît", "Quelle ligne pour le centre ?", "À quelle heure le prochain bus ?", "Je voudrais un abonnement"],
        ];

        return $fallbacks[$context] ?? ["Oui", "Non", "Je ne sais pas", "Merci"];
    }
}
