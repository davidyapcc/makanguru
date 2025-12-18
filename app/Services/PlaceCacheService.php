<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Place;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Place Cache Service
 *
 * Handles Redis caching for restaurant queries to reduce database load.
 * Caches are invalidated after 1 hour or when Place data is modified.
 *
 * @package App\Services
 */
class PlaceCacheService
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Cache key prefix for all Place queries
     */
    private const CACHE_PREFIX = 'places';

    /**
     * Get places with optional filters, using cache
     *
     * @param bool $halalOnly Filter for halal places only
     * @param string|null $price Price range filter (budget|moderate|expensive)
     * @param string|null $area Area filter
     * @param array $tags Tag filters
     * @return Collection<int, Place>
     */
    public function getPlaces(
        bool $halalOnly = false,
        ?string $price = null,
        ?string $area = null,
        array $tags = []
    ): Collection {
        $cacheKey = $this->buildCacheKey($halalOnly, $price, $area, $tags);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($halalOnly, $price, $area, $tags) {
            Log::info('Cache miss - querying database', [
                'cache_key' => $this->buildCacheKey($halalOnly, $price, $area, $tags),
            ]);

            return $this->queryPlaces($halalOnly, $price, $area, $tags);
        });
    }

    /**
     * Get places near coordinates with optional filters, using cache
     *
     * @param float $latitude Latitude coordinate
     * @param float $longitude Longitude coordinate
     * @param int $radiusKm Search radius in kilometers
     * @param bool $halalOnly Filter for halal places only
     * @param string|null $price Price range filter
     * @return Collection<int, Place>
     */
    public function getPlacesNear(
        float $latitude,
        float $longitude,
        int $radiusKm = 10,
        bool $halalOnly = false,
        ?string $price = null
    ): Collection {
        $cacheKey = $this->buildNearCacheKey($latitude, $longitude, $radiusKm, $halalOnly, $price);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $latitude,
            $longitude,
            $radiusKm,
            $halalOnly,
            $price
        ) {
            Log::info('Cache miss - querying database for nearby places', [
                'cache_key' => $this->buildNearCacheKey($latitude, $longitude, $radiusKm, $halalOnly, $price),
            ]);

            $query = Place::near($latitude, $longitude, $radiusKm);

            if ($halalOnly) {
                $query->halalOnly();
            }

            if ($price !== null) {
                $query->byPrice($price);
            }

            return $query->get();
        });
    }

    /**
     * Invalidate all Place caches
     *
     * Call this method when Place data is modified (create, update, delete)
     *
     * @return bool
     */
    public function invalidateCache(): bool
    {
        $pattern = self::CACHE_PREFIX . ':*';

        Log::info('Invalidating all Place caches', ['pattern' => $pattern]);

        // Note: This requires Redis driver
        // For file/database cache drivers, consider using Cache::tags()
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);

            foreach ($keys as $key) {
                Cache::forget(str_replace(config('database.redis.options.prefix'), '', $key));
            }

            return true;
        }

        // Fallback: flush entire cache (not ideal for production)
        // Consider implementing cache tags for better control
        Log::warning('Cache driver does not support selective invalidation, flushing entire cache');
        Cache::flush();

        return true;
    }

    /**
     * Query places from database with filters
     *
     * @param bool $halalOnly
     * @param string|null $price
     * @param string|null $area
     * @param array $tags
     * @return Collection<int, Place>
     */
    private function queryPlaces(
        bool $halalOnly,
        ?string $price,
        ?string $area,
        array $tags
    ): Collection {
        $query = Place::query();

        if ($halalOnly) {
            $query->halalOnly();
        }

        if ($price !== null) {
            $query->byPrice($price);
        }

        if ($area !== null && $area !== '') {
            $query->inArea($area);
        }

        if (!empty($tags)) {
            $query->withTags($tags);
        }

        return $query->get();
    }

    /**
     * Build cache key for filtered queries
     *
     * @param bool $halalOnly
     * @param string|null $price
     * @param string|null $area
     * @param array $tags
     * @return string
     */
    private function buildCacheKey(
        bool $halalOnly,
        ?string $price,
        ?string $area,
        array $tags
    ): string {
        $key = self::CACHE_PREFIX . ':filtered';

        $key .= ':halal_' . ($halalOnly ? '1' : '0');
        $key .= ':price_' . ($price ?? 'all');
        $key .= ':area_' . ($area !== null && $area !== '' ? md5($area) : 'all');
        $key .= ':tags_' . (!empty($tags) ? md5(json_encode(sort($tags))) : 'none');

        return $key;
    }

    /**
     * Build cache key for geospatial queries
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radiusKm
     * @param bool $halalOnly
     * @param string|null $price
     * @return string
     */
    private function buildNearCacheKey(
        float $latitude,
        float $longitude,
        int $radiusKm,
        bool $halalOnly,
        ?string $price
    ): string {
        $key = self::CACHE_PREFIX . ':near';

        // Round coordinates to 4 decimal places (~11m precision) for cache efficiency
        $key .= ':lat_' . round($latitude, 4);
        $key .= ':lng_' . round($longitude, 4);
        $key .= ':radius_' . $radiusKm;
        $key .= ':halal_' . ($halalOnly ? '1' : '0');
        $key .= ':price_' . ($price ?? 'all');

        return $key;
    }
}
