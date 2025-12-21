<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Restaurant Scraper Service
 *
 * Handles the business logic for scraping restaurant data from various online sources.
 * This service abstracts the scraping logic from the command layer.
 *
 * @package App\Services
 */
class RestaurantScraperService
{
    /**
     * Overpass API endpoint
     */
    private const OVERPASS_API = 'https://overpass-api.de/api/interpreter';

    /**
     * Request timeout in seconds
     */
    private const TIMEOUT = 30;

    /**
     * Fetch restaurants from OpenStreetMap via Overpass API
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radius Radius in meters
     * @param int $limit Maximum number of results
     * @return array<int, array<string, mixed>>
     */
    public function fetchFromOverpass(float $latitude, float $longitude, int $radius, int $limit): array
    {
        $query = $this->buildOverpassQuery($latitude, $longitude, $radius, $limit);

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->asForm()
                ->post(self::OVERPASS_API, ['data' => $query]);

            if (!$response->successful()) {
                Log::error('Overpass API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();
            return $this->parseOverpassResponse($data);
        } catch (\Exception $e) {
            Log::error('Overpass API exception', [
                'error' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
            return [];
        }
    }

    /**
     * Build Overpass QL query
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radius
     * @param int $limit
     * @return string
     */
    private function buildOverpassQuery(float $latitude, float $longitude, int $radius, int $limit): string
    {
        return <<<OVERPASS
[out:json][timeout:25];
(
  node["amenity"="restaurant"](around:{$radius},{$latitude},{$longitude});
  node["amenity"="cafe"](around:{$radius},{$latitude},{$longitude});
  node["amenity"="fast_food"](around:{$radius},{$latitude},{$longitude});
  way["amenity"="restaurant"](around:{$radius},{$latitude},{$longitude});
  way["amenity"="cafe"](around:{$radius},{$latitude},{$longitude});
);
out center body {$limit};
OVERPASS;
    }

    /**
     * Parse Overpass API response
     *
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    private function parseOverpassResponse(array $data): array
    {
        $elements = $data['elements'] ?? [];
        $restaurants = [];

        foreach ($elements as $element) {
            $parsed = $this->parseElement($element);
            if ($parsed !== null) {
                $restaurants[] = $parsed;
            }
        }

        return $restaurants;
    }

    /**
     * Parse a single OSM element
     *
     * @param array<string, mixed> $element
     * @return array<string, mixed>|null
     */
    private function parseElement(array $element): ?array
    {
        $tags = $element['tags'] ?? [];

        // Skip if no name
        if (empty($tags['name'])) {
            return null;
        }

        // Get coordinates (handle both nodes and ways)
        $latitude = $element['lat'] ?? $element['center']['lat'] ?? 0.0;
        $longitude = $element['lon'] ?? $element['center']['lon'] ?? 0.0;

        return [
            'name' => $this->sanitize($tags['name']),
            'description' => $this->generateDescription($tags),
            'address' => $this->extractAddress($tags),
            'area' => $this->extractArea($tags),
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'price' => $this->determinePriceRange($tags),
            'tags' => $this->extractTags($tags),
            'is_halal' => $this->determineHalal($tags),
            'cuisine_type' => $this->extractCuisine($tags),
            'opening_hours' => $tags['opening_hours'] ?? null,
        ];
    }

    /**
     * Sanitize string input
     *
     * @param mixed $value
     * @return string
     */
    private function sanitize(mixed $value): string
    {
        return trim(strip_tags((string) $value));
    }

    /**
     * Extract address from tags
     *
     * @param array<string, mixed> $tags
     * @return string
     */
    private function extractAddress(array $tags): string
    {
        $parts = [];

        if (!empty($tags['addr:housenumber'])) {
            $parts[] = $tags['addr:housenumber'];
        }
        if (!empty($tags['addr:street'])) {
            $parts[] = $tags['addr:street'];
        }
        if (!empty($tags['addr:postcode'])) {
            $parts[] = $tags['addr:postcode'];
        }

        return !empty($parts) ? implode(', ', $parts) : 'Address not available';
    }

    /**
     * Extract area from tags
     *
     * @param array<string, mixed> $tags
     * @return string
     */
    private function extractArea(array $tags): string
    {
        return $tags['addr:city']
            ?? $tags['addr:suburb']
            ?? $tags['addr:district']
            ?? 'Kuala Lumpur';
    }

    /**
     * Extract cuisine type
     *
     * @param array<string, mixed> $tags
     * @return string
     */
    private function extractCuisine(array $tags): string
    {
        if (isset($tags['cuisine'])) {
            $cuisines = explode(';', (string) $tags['cuisine']);
            return ucfirst(trim($cuisines[0]));
        }

        return 'Malaysian';
    }

    /**
     * Generate description based on tags
     *
     * @param array<string, mixed> $tags
     * @return string|null
     */
    private function generateDescription(array $tags): ?string
    {
        $cuisine = $this->extractCuisine($tags);
        $amenity = $tags['amenity'] ?? 'restaurant';

        $templates = [
            "Authentic {$cuisine} cuisine in a welcoming atmosphere",
            "Local favorite serving delicious {$cuisine} dishes",
            "Popular {$amenity} known for {$cuisine} specialties",
            "Cozy spot for {$cuisine} food lovers",
            "Must-try destination for authentic {$cuisine}",
        ];

        return $templates[array_rand($templates)];
    }

    /**
     * Determine price range from tags
     *
     * @param array<string, mixed> $tags
     * @return string
     */
    private function determinePriceRange(array $tags): string
    {
        // Check explicit price tag
        if (isset($tags['price'])) {
            $price = strtolower((string) $tags['price']);
            if (str_contains($price, 'expensive') || str_contains($price, 'high')) {
                return 'expensive';
            }
            if (str_contains($price, 'moderate') || str_contains($price, 'medium')) {
                return 'moderate';
            }
        }

        // Check for premium indicators
        $name = strtolower($tags['name'] ?? '');
        if (str_contains($name, 'hotel') || str_contains($name, 'fine dining')) {
            return 'expensive';
        }

        // Default based on amenity
        return match ($tags['amenity'] ?? 'restaurant') {
            'fast_food' => 'budget',
            'cafe' => 'moderate',
            default => 'moderate',
        };
    }

    /**
     * Extract searchable tags from OSM data
     *
     * @param array<string, mixed> $tags
     * @return array<int, string>
     */
    private function extractTags(array $tags): array
    {
        $extractedTags = [];

        // Cuisine tags
        if (isset($tags['cuisine'])) {
            $cuisines = explode(';', (string) $tags['cuisine']);
            foreach ($cuisines as $cuisine) {
                $extractedTags[] = strtolower(trim($cuisine));
            }
        }

        // Amenity tag
        if (isset($tags['amenity'])) {
            $extractedTags[] = strtolower($tags['amenity']);
        }

        // Diet tags
        $this->addDietTags($tags, $extractedTags);

        // Meal tags
        $this->addMealTags($tags, $extractedTags);

        return array_values(array_unique($extractedTags));
    }

    /**
     * Add diet-related tags
     *
     * @param array<string, mixed> $tags
     * @param array<int, string> &$extractedTags
     * @return void
     */
    private function addDietTags(array $tags, array &$extractedTags): void
    {
        if (isset($tags['diet:halal']) && $tags['diet:halal'] === 'yes') {
            $extractedTags[] = 'halal';
        }
        if (isset($tags['diet:vegetarian']) && $tags['diet:vegetarian'] === 'yes') {
            $extractedTags[] = 'vegetarian';
        }
        if (isset($tags['diet:vegan']) && $tags['diet:vegan'] === 'yes') {
            $extractedTags[] = 'vegan';
        }
    }

    /**
     * Add meal-related tags
     *
     * @param array<string, mixed> $tags
     * @param array<int, string> &$extractedTags
     * @return void
     */
    private function addMealTags(array $tags, array &$extractedTags): void
    {
        $mealTypes = ['breakfast', 'lunch', 'dinner', 'brunch'];
        foreach ($mealTypes as $meal) {
            if (isset($tags[$meal]) && $tags[$meal] === 'yes') {
                $extractedTags[] = $meal;
            }
        }
    }

    /**
     * Determine if restaurant is halal
     *
     * @param array<string, mixed> $tags
     * @return bool
     */
    private function determineHalal(array $tags): bool
    {
        // Explicit halal tag
        if (isset($tags['diet:halal']) && $tags['diet:halal'] === 'yes') {
            return true;
        }

        // Check cuisine type (Malaysian/Malay cuisines are often halal)
        $cuisine = strtolower($tags['cuisine'] ?? '');
        if (str_contains($cuisine, 'malay') || str_contains($cuisine, 'muslim')) {
            return true;
        }

        // Check name for halal keywords
        $name = strtolower($tags['name'] ?? '');
        if (str_contains($name, 'halal') || str_contains($name, 'restoran islam')) {
            return true;
        }

        return false;
    }

    /**
     * Validate restaurant data before saving
     *
     * @param array<string, mixed> $restaurant
     * @return bool
     */
    public function validateRestaurantData(array $restaurant): bool
    {
        // Required fields
        $required = ['name', 'latitude', 'longitude', 'price'];

        foreach ($required as $field) {
            if (empty($restaurant[$field])) {
                return false;
            }
        }

        // Validate coordinates (Malaysia bounds)
        $lat = (float) $restaurant['latitude'];
        $lng = (float) $restaurant['longitude'];

        // Malaysia approximate bounds
        if ($lat < 1.0 || $lat > 7.5 || $lng < 99.0 || $lng > 120.0) {
            return false;
        }

        // Validate price enum
        if (!in_array($restaurant['price'], ['budget', 'moderate', 'expensive'], true)) {
            return false;
        }

        return true;
    }
}
