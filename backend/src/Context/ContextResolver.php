<?php

namespace App\Context;

use App\AI\KeywordGeneratorInterface;
use App\Arasaac\ArasaacClient;
use App\OpenStreetMap\OpenStreetMapClient;
use Psr\Log\LoggerInterface;

final class ContextResolver implements ContextResolverInterface
{
    private const CONTEXT_LABELS = [
        'bakery' => 'Boulangerie',
        'transport' => 'Transports',
        'restaurant' => 'Restaurant',
        'pharmacy' => 'Pharmacie',
        'supermarket' => 'Supermarché',
        'school' => 'École',
        'hospital' => 'Hôpital',
        'bank' => 'Banque',
        'post_office' => 'Poste',
        'park' => 'Parc',
        'stadium' => 'Stade',
        'beach' => 'Plage',
        'university' => 'Université',
        'library' => 'Bibliothèque',
        'cafe' => 'Café',
        'cinema' => 'Cinéma',
        'hotel' => 'Hôtel',
        'gym' => 'Salle de sport',
        'hairdresser' => 'Coiffeur',
        'dentist' => 'Dentiste',
        'police' => 'Police',
        'museum' => 'Musée',
        'train_station' => 'Gare',
        'swimming_pool' => 'Piscine',
        'airport' => 'Aéroport',
        'marketplace' => 'Marché',
        'veterinary' => 'Vétérinaire',
        'fast_food' => 'Fast-food',
        'doctors' => 'Cabinet médical',
        'street' => 'Rue',
        'home' => 'Maison',
    ];

    public function __construct(
        private ArasaacClient $arasaac,
        private KeywordGeneratorInterface $ai,
        private OpenStreetMapClient $osm,
        private LoggerInterface $logger
    ) {}

    public function resolveCandidates(float $lat, float $lon): array
    {
        $this->logger->info('Détection contexte automatique', [
            'lat' => $lat,
            'lon' => $lon
        ]);

        // Récupérer les POI à proximité via OpenStreetMap
        $pois = $this->osm->findNearbyPOIs($lat, $lon, 150);

        if (empty($pois)) {
            $this->logger->info('Aucun POI trouvé, contextes par défaut: Maison, Rue');
            return [
                new ContextCandidate('home', 'Maison', 0.6),
                new ContextCandidate('street', 'Rue', 0.5),
            ];
        }

        // Grouper les POI par type et calculer les scores
        $contextScores = [];
        foreach ($pois as $poi) {
            $type = $poi['type'];
            $distance = $poi['distance'];

            // Score inversement proportionnel à la distance (max 100m = score 1.0)
            $score = max(0, 1 - ($distance / 150));

            if (!isset($contextScores[$type])) {
                $contextScores[$type] = [
                    'score' => 0,
                    'count' => 0,
                    'minDistance' => $distance,
                    'name' => $poi['name'],
                ];
            }

            $contextScores[$type]['score'] = max($contextScores[$type]['score'], $score);
            $contextScores[$type]['count']++;
            if ($distance < $contextScores[$type]['minDistance']) {
                $contextScores[$type]['minDistance'] = $distance;
                $contextScores[$type]['name'] = $poi['name'];
            }
        }

        // Trier par score décroissant
        uasort($contextScores, fn($a, $b) => $b['score'] <=> $a['score']);

        // Construire la liste des candidats
        $candidates = [];
        foreach ($contextScores as $type => $data) {
            $label = self::CONTEXT_LABELS[$type] ?? ucfirst($type);

            // Ajouter le nom du lieu si disponible
            if ($data['name']) {
                $label .= ' - ' . $data['name'];
            }

            $candidates[] = new ContextCandidate(
                $type,
                $label,
                round($data['score'], 2)
            );
        }

        // Limiter les POI détectés à 7 max
        $candidates = array_slice($candidates, 0, 7);

        // Toujours ajouter "Maison" et "Rue" (affichés en permanence)
        $candidates[] = new ContextCandidate('home', 'Maison', 0.25);
        $candidates[] = new ContextCandidate('street', 'Rue', 0.2);

        $this->logger->info('Contextes détectés', [
            'count' => count($candidates),
            'contexts' => array_map(fn($c) => $c->id, $candidates)
        ]);

        return $candidates;
    }

    public function getAllContexts(): array
    {
        $contexts = [];
        foreach (self::CONTEXT_LABELS as $id => $label) {
            $contexts[] = new ContextCandidate($id, $label, 0);
        }
        return $contexts;
    }

    public function resolveContext(string $contextId): ContextResult
    {
        // Si le contextId n'est pas dans les labels prédéfinis, l'utiliser comme label (contexte personnalisé)
        $label = self::CONTEXT_LABELS[$contextId] ?? ucfirst($contextId);

        $this->logger->info('ContextResolver appelé', [
            'context' => $contextId,
            'label' => $label,
        ]);

        // Génération de mots-clés par IA
        $generated = $this->ai->generate($label);

        // Mots fixes AAC (communs à tous les contextes)
        $fixed = [
            'bonjour',
            'merci',
            's\'il vous plaît',
            'oui',
            'non',
            'aide',
        ];

        $keywords = array_values(
            array_unique(array_merge($fixed, $generated))
        );

        // Récupération des pictogrammes ARASAAC
        $pictograms = [];

        foreach ($keywords as $word) {
            $id = $this->arasaac->findPictogramId($word);

            if ($id !== null) {
                $pictograms[] = [
                    'word' => $word,
                    'pictogramId' => $id,
                ];
            }
        }

        return new ContextResult(
            $contextId,
            $pictograms,
            0.9
        );
    }
}
