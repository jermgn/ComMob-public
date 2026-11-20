<?php

namespace App\AI;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class GroqPhraseGenerator
{
    public function __construct(
        private HttpClientInterface $client,
        private string $apiKey,
        private LoggerInterface $logger
    ) {}

    /**
     * Génère une phrase à partir d'une liste de mots-clés
     *
     * @param array $words Liste de mots-clés
     * @return string La phrase générée
     */
    public function generate(array $words, string $userContext = ''): string
    {
        $this->logger->info('GROQ phrase generator appelé', [
            'words' => $words,
        ]);

        if (empty($words)) {
            return '';
        }

        $wordList = implode(', ', $words);

        $prompt = <<<PROMPT
L'utilisateur a sélectionné ces pictogrammes/mots : {$wordList}

Tu dois créer UNE SEULE phrase que l'utilisateur veut dire à quelqu'un (commerçant, médecin, etc.).

RÈGLES STRICTES :
1. La phrase doit exprimer UNE SEULE idée claire et logique
2. Utilise UNIQUEMENT les mots qui ont du sens ensemble
3. IGNORE les mots qui ne collent pas avec les autres (ex: si "chambre" ne va pas avec "boulangerie", ignore "chambre")
4. Maximum 12 mots
5. La phrase doit être ce que l'utilisateur VEUT DIRE (une demande, une question, un besoin)
6. Conjugue les verbes correctement à la première personne (je voudrais, j'aimerais, etc.)
7. Utilise tous les mots (l'ordre n'est pas important)
EXEMPLES :
- Mots: bonjour, pain, acheter → "Bonjour, je voudrais acheter du pain"
- Mots: merci, aide, s'il vous plaît → "Merci pour votre aide, s'il vous plaît"
- Mots: où, toilettes → "Où sont les toilettes s'il vous plaît ?"
- Mots: mal, tête, médicament → "J'ai mal à la tête, je voudrais un médicament"

Réponds UNIQUEMENT avec la phrase, sans guillemets ni explications.
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
                                'content' => 'Tu es un assistant qui crée des phrases simples pour aider les personnes utilisant la communication alternative (AAC). Réponds uniquement avec la phrase demandée, sans guillemets ni explications.'
                                    . ($userContext !== '' ? "\n\n" . $userContext : ''),
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                        'temperature' => 0.7,
                        'max_tokens' => 100,
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            $this->logger->info('Réponse API Groq (phrase)', [
                'status' => $statusCode,
            ]);

            if ($statusCode !== 200) {
                $errorMsg = $data['error']['message'] ?? 'Erreur inconnue';
                $this->logger->error('Erreur API Groq (phrase)', [
                    'status' => $statusCode,
                    'error' => $errorMsg,
                ]);
                return $this->fallbackPhrase($words);
            }

            $phrase = $data['choices'][0]['message']['content'] ?? '';
            $phrase = trim($phrase, " \t\n\r\0\x0B\"'");

            $this->logger->info('Phrase générée', [
                'phrase' => $phrase
            ]);

            return $phrase ?: $this->fallbackPhrase($words);

        } catch (\Exception $e) {
            $this->logger->error('Exception lors de la génération de phrase', [
                'message' => $e->getMessage(),
            ]);
            return $this->fallbackPhrase($words);
        }
    }

    /**
     * Phrase de secours si Groq échoue
     */
    private function fallbackPhrase(array $words): string
    {
        return implode(' ', $words);
    }
}











