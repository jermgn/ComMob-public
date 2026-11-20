<?php

namespace App\Arasaac;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ArasaacClient
{
    public function __construct(
        private HttpClientInterface $http
    ) {}

    public function findPictogramId(string $word): ?int
    {
        $response = $this->http->request(
            'GET',
            'https://api.arasaac.org/api/pictograms/fr/search/' . urlencode($word)
        );

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $data = $response->toArray(false);

        if (empty($data)) {
            return null;
        }

        return $data[0]['_id'] ?? null;
    }

    /**
     * Recherche des pictogrammes par mot-clé et retourne les résultats
     *
     * @param string $query Le mot-clé à rechercher
     * @param int $limit Nombre maximum de résultats (défaut: 20)
     * @return array Liste des pictogrammes trouvés avec [word, pictogramId, keywords]
     */
    public function searchPictograms(string $query, int $limit = 20): array
    {
        $response = $this->http->request(
            'GET',
            'https://api.arasaac.org/api/pictograms/fr/search/' . urlencode($query)
        );

        if ($response->getStatusCode() !== 200) {
            return [];
        }

        $data = $response->toArray(false);

        if (empty($data)) {
            return [];
        }

        $results = [];
        $count = 0;

        foreach ($data as $pictogram) {
            if ($count >= $limit) {
                break;
            }

            $id = $pictogram['_id'] ?? null;
            $keywords = $pictogram['keywords'] ?? [];

            if ($id !== null && !empty($keywords)) {
                // Prendre le premier mot-clé comme label principal
                $mainKeyword = $keywords[0]['keyword'] ?? $query;

                $results[] = [
                    'word' => $mainKeyword,
                    'pictogramId' => $id,
                    'allKeywords' => array_column($keywords, 'keyword')
                ];

                $count++;
            }
        }

        return $results;
    }
}