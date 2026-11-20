<?php

namespace App\OpenStreetMap;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenStreetMapClient
{
    private const OVERPASS_API = 'https://overpass-api.de/api/interpreter';
    private const SEARCH_RADIUS = 100; // mètres

    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger
    ) {}

    /**
     * Trouve les POI (Points of Interest) à proximité d'une position
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param int $radius Rayon de recherche en mètres
     * @return array Liste des POI trouvés avec leur type
     */
    public function findNearbyPOIs(float $lat, float $lon, int $radius = self::SEARCH_RADIUS): array
    {
        $this->logger->info('Recherche POI OpenStreetMap', [
            'lat' => $lat,
            'lon' => $lon,
            'radius' => $radius
        ]);

        // Requête Overpass pour trouver les POI à proximité
        $query = $this->buildOverpassQuery($lat, $lon, $radius);

        try {
            $response = $this->http->request('POST', self::OVERPASS_API, [
                'body' => $query,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Erreur API Overpass', [
                    'status' => $response->getStatusCode()
                ]);
                return [];
            }

            $data = $response->toArray(false);
            $elements = $data['elements'] ?? [];

            $this->logger->info('POI trouvés', [
                'count' => count($elements)
            ]);

            return $this->parsePOIs($elements, $lat, $lon);

        } catch (\Exception $e) {
            $this->logger->error('Exception Overpass', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Construit la requête Overpass pour chercher les POI
     */
    private function buildOverpassQuery(float $lat, float $lon, int $radius): string
    {
        return sprintf(
            'data=[out:json][timeout:10];
            (
                node["shop"](around:%d,%f,%f);
                node["amenity"](around:%d,%f,%f);
                node["public_transport"](around:%d,%f,%f);
                node["railway"="station"](around:%d,%f,%f);
                node["railway"="tram_stop"](around:%d,%f,%f);
                node["highway"="bus_stop"](around:%d,%f,%f);
                way["shop"](around:%d,%f,%f);
                way["amenity"](around:%d,%f,%f);
            );
            out center;',
            $radius, $lat, $lon,
            $radius, $lat, $lon,
            $radius, $lat, $lon,
            $radius, $lat, $lon,
            $radius, $lat, $lon,
            $radius, $lat, $lon,
            $radius, $lat, $lon,
            $radius, $lat, $lon
        );
    }

    /**
     * Parse les éléments OSM et extrait les informations pertinentes
     */
    private function parsePOIs(array $elements, float $userLat, float $userLon): array
    {
        $pois = [];

        foreach ($elements as $element) {
            $tags = $element['tags'] ?? [];
            $poiType = $this->determinePoiType($tags);

            if ($poiType === null) {
                continue;
            }

            // Calcul de la position
            $poiLat = $element['lat'] ?? $element['center']['lat'] ?? null;
            $poiLon = $element['lon'] ?? $element['center']['lon'] ?? null;

            if ($poiLat === null || $poiLon === null) {
                continue;
            }

            // Calcul de la distance
            $distance = $this->calculateDistance($userLat, $userLon, $poiLat, $poiLon);

            $pois[] = [
                'type' => $poiType,
                'name' => $tags['name'] ?? null,
                'distance' => $distance,
                'tags' => $tags,
            ];
        }

        // Trier par distance
        usort($pois, fn($a, $b) => $a['distance'] <=> $b['distance']);

        return $pois;
    }

    /**
     * Détermine le type de POI basé sur les tags OSM
     */
    private function determinePoiType(array $tags): ?string
    {
        // Boulangerie / Pâtisserie
        if (isset($tags['shop']) && in_array($tags['shop'], ['bakery', 'pastry'])) {
            return 'bakery';
        }

        // Transports
        if (isset($tags['public_transport']) ||
            isset($tags['railway']) ||
            (isset($tags['highway']) && $tags['highway'] === 'bus_stop') ||
            (isset($tags['amenity']) && in_array($tags['amenity'], ['bus_station', 'ferry_terminal']))) {
            return 'transport';
        }

        // Restaurant / Café
        if (isset($tags['amenity']) && in_array($tags['amenity'], ['restaurant', 'cafe', 'fast_food', 'food_court'])) {
            return 'restaurant';
        }

        // Pharmacie
        if (isset($tags['amenity']) && $tags['amenity'] === 'pharmacy') {
            return 'pharmacy';
        }

        // Supermarché / Épicerie
        if (isset($tags['shop']) && in_array($tags['shop'], ['supermarket', 'convenience', 'grocery'])) {
            return 'supermarket';
        }

        // École
        if (isset($tags['amenity']) && in_array($tags['amenity'], ['school', 'university', 'college', 'kindergarten'])) {
            return 'school';
        }

        // Hôpital / Médecin
        if (isset($tags['amenity']) && in_array($tags['amenity'], ['hospital', 'clinic', 'doctors'])) {
            return 'hospital';
        }

        // Banque
        if (isset($tags['amenity']) && $tags['amenity'] === 'bank') {
            return 'bank';
        }

        // Poste
        if (isset($tags['amenity']) && $tags['amenity'] === 'post_office') {
            return 'post_office';
        }

        // Parc
        if (isset($tags['leisure']) && in_array($tags['leisure'], ['park', 'garden', 'playground'])) {
            return 'park';
        }

        return null;
    }

    /**
     * Calcule la distance entre deux points GPS en mètres (formule Haversine)
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // mètres

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
