<?php

namespace Tests\Unit;

use App\Models\Place;
use App\Services\PlaceCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * PlaceCacheService Unit Tests
 *
 * Tests caching logic, cache key generation, and cache invalidation.
 */
class PlaceCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlaceCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlaceCacheService();
        Cache::flush(); // Clear cache before each test
    }

    /**
     * Test getPlaces returns results from database on cache miss.
     */
    public function test_get_places_returns_from_database_on_cache_miss(): void
    {
        // Arrange
        Place::factory()->count(3)->create();

        // Act
        $results = $this->service->getPlaces();

        // Assert
        $this->assertCount(3, $results);
    }

    /**
     * Test getPlaces caches results on first call.
     */
    public function test_get_places_caches_results(): void
    {
        // Arrange
        Place::factory()->count(2)->create();

        // Act: First call
        $this->service->getPlaces();

        // Assert: Cache should be set
        $this->assertTrue(Cache::has('places:filtered:halal_0:price_all:area_all:tags_none'));
    }

    /**
     * Test getPlaces returns from cache on subsequent calls.
     */
    public function test_get_places_returns_from_cache(): void
    {
        // Arrange
        Place::factory()->create(['name' => 'Original Place']);
        $this->service->getPlaces(); // Prime cache

        // Act: Delete from DB but cache should still have it
        Place::query()->delete();
        $results = $this->service->getPlaces();

        // Assert: Should still return cached result
        $this->assertCount(1, $results);
        $this->assertEquals('Original Place', $results->first()->name);
    }

    /**
     * Test getPlaces with halal filter creates different cache key.
     */
    public function test_get_places_halal_filter_uses_different_cache_key(): void
    {
        // Arrange
        Place::factory()->create(['is_halal' => true]);
        Place::factory()->create(['is_halal' => false]);

        // Act
        $all = $this->service->getPlaces(halalOnly: false);
        $halal = $this->service->getPlaces(halalOnly: true);

        // Assert
        $this->assertCount(2, $all);
        $this->assertCount(1, $halal);
        $this->assertTrue(Cache::has('places:filtered:halal_0:price_all:area_all:tags_none'));
        $this->assertTrue(Cache::has('places:filtered:halal_1:price_all:area_all:tags_none'));
    }

    /**
     * Test getPlaces with price filter.
     */
    public function test_get_places_price_filter(): void
    {
        // Arrange
        Place::factory()->create(['price' => 'budget']);
        Place::factory()->create(['price' => 'expensive']);

        // Act
        $budget = $this->service->getPlaces(price: 'budget');

        // Assert
        $this->assertCount(1, $budget);
        $this->assertEquals('budget', $budget->first()->price);
    }

    /**
     * Test getPlaces with area filter.
     */
    public function test_get_places_area_filter(): void
    {
        // Arrange
        Place::factory()->create(['area' => 'Bangsar']);
        Place::factory()->create(['area' => 'KLCC']);

        // Act
        $bangsar = $this->service->getPlaces(area: 'Bangsar');

        // Assert
        $this->assertCount(1, $bangsar);
        $this->assertEquals('Bangsar', $bangsar->first()->area);
    }

    /**
     * Test getPlaces with tags filter.
     */
    public function test_get_places_tags_filter(): void
    {
        // Arrange
        Place::factory()->create(['tags' => ['nasi lemak', 'breakfast']]);
        Place::factory()->create(['tags' => ['burger', 'western']]);

        // Act
        $results = $this->service->getPlaces(tags: ['nasi lemak']);

        // Assert
        $this->assertCount(1, $results);
        $this->assertContains('nasi lemak', $results->first()->tags);
    }

    /**
     * Test getPlaces with multiple filters.
     */
    public function test_get_places_multiple_filters(): void
    {
        // Arrange
        Place::factory()->create([
            'area' => 'Bangsar',
            'price' => 'budget',
            'is_halal' => true,
        ]);
        Place::factory()->create([
            'area' => 'KLCC',
            'price' => 'budget',
            'is_halal' => true,
        ]);

        // Act
        $results = $this->service->getPlaces(
            halalOnly: true,
            price: 'budget',
            area: 'Bangsar'
        );

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Bangsar', $results->first()->area);
    }

    /**
     * Test getPlacesNear with coordinates.
     */
    public function test_get_places_near_with_coordinates(): void
    {
        // Arrange: Create test places at specific distances
        $nearPlace = Place::factory()->create([
            'name' => 'Near KLCC',
            'latitude' => 3.1590,
            'longitude' => 101.7130,
        ]);
        $farPlace = Place::factory()->create([
            'name' => 'Far from KLCC',
            'latitude' => 3.0500,
            'longitude' => 101.6000,
        ]);

        // Act: Query within 1km of KLCC (smaller radius to avoid the far place)
        $results = $this->service->getPlacesNear(3.1578, 101.7123, 1);

        // Assert: Filter to only our test places
        $testResults = $results->whereIn('id', [$nearPlace->id, $farPlace->id]);
        $this->assertCount(1, $testResults);
        $this->assertEquals('Near KLCC', $testResults->first()->name);
    }

    /**
     * Test getPlacesNear caches results.
     */
    public function test_get_places_near_caches_results(): void
    {
        // Arrange
        Place::factory()->create([
            'latitude' => 3.1590,
            'longitude' => 101.7130,
        ]);

        // Act: First call
        $this->service->getPlacesNear(3.1578, 101.7123, 5);

        // Assert: Cache should be set (coordinates rounded to 4 decimal places)
        $cacheKey = 'places:near:lat_3.1578:lng_101.7123:radius_5:halal_0:price_all';
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * Test getPlacesNear with halal filter.
     */
    public function test_get_places_near_with_halal_filter(): void
    {
        // Arrange
        Place::factory()->create([
            'name' => 'Halal Near',
            'latitude' => 3.1590,
            'longitude' => 101.7130,
            'is_halal' => true,
        ]);
        Place::factory()->create([
            'name' => 'Non-Halal Near',
            'latitude' => 3.1591,
            'longitude' => 101.7131,
            'is_halal' => false,
        ]);

        // Act
        $results = $this->service->getPlacesNear(3.1578, 101.7123, 5, halalOnly: true);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Halal Near', $results->first()->name);
    }

    /**
     * Test getPlacesNear with price filter.
     */
    public function test_get_places_near_with_price_filter(): void
    {
        // Arrange
        Place::factory()->create([
            'latitude' => 3.1590,
            'longitude' => 101.7130,
            'price' => 'budget',
        ]);
        Place::factory()->create([
            'latitude' => 3.1591,
            'longitude' => 101.7131,
            'price' => 'expensive',
        ]);

        // Act
        $results = $this->service->getPlacesNear(3.1578, 101.7123, 5, price: 'budget');

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('budget', $results->first()->price);
    }

    /**
     * Test cache key generation creates unique keys for different filters.
     */
    public function test_cache_key_generation_is_unique(): void
    {
        // Arrange
        Place::factory()->create();

        // Act: Generate different cache scenarios
        $this->service->getPlaces(halalOnly: true);
        $this->service->getPlaces(halalOnly: false);
        $this->service->getPlaces(price: 'budget');
        $this->service->getPlaces(area: 'Bangsar');

        // Assert: Different cache keys should exist
        $this->assertTrue(Cache::has('places:filtered:halal_1:price_all:area_all:tags_none'));
        $this->assertTrue(Cache::has('places:filtered:halal_0:price_all:area_all:tags_none'));
        $this->assertTrue(Cache::has('places:filtered:halal_0:price_budget:area_all:tags_none'));
    }

    /**
     * Test invalidateCache clears all Place caches (Redis driver).
     */
    public function test_invalidate_cache_clears_caches(): void
    {
        // Skip if not using Redis
        if (config('cache.default') !== 'redis') {
            $this->markTestSkipped('This test requires Redis cache driver');
        }

        // Arrange
        Place::factory()->create();
        $this->service->getPlaces();
        $this->service->getPlaces(halalOnly: true);

        // Act
        $result = $this->service->invalidateCache();

        // Assert
        $this->assertTrue($result);
        $this->assertFalse(Cache::has('places:filtered:halal_0:price_all:area_all:tags_none'));
        $this->assertFalse(Cache::has('places:filtered:halal_1:price_all:area_all:tags_none'));
    }

    /**
     * Test cache TTL is 1 hour.
     */
    public function test_cache_ttl_is_one_hour(): void
    {
        // Arrange
        Place::factory()->create(['name' => 'Test Place']);

        // Act
        $this->service->getPlaces();

        // Assert: Cache should exist
        $cacheKey = 'places:filtered:halal_0:price_all:area_all:tags_none';
        $this->assertTrue(Cache::has($cacheKey));

        // Fast forward time by 3600 seconds (1 hour)
        $this->travel(3601)->seconds();

        // Assert: Cache should be expired
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test edge case: Empty result set is cached.
     */
    public function test_empty_result_set_is_cached(): void
    {
        // Arrange: No places in database
        Place::query()->delete();

        // Act
        $results = $this->service->getPlaces(area: 'NonExistentArea');

        // Assert
        $this->assertCount(0, $results);
        $this->assertTrue(Cache::has('places:filtered:halal_0:price_all:area_' . md5('NonExistentArea') . ':tags_none'));
    }

    /**
     * Test edge case: Very large radius.
     */
    public function test_very_large_radius(): void
    {
        // Arrange
        Place::factory()->create([
            'latitude' => 3.1578,
            'longitude' => 101.7123,
        ]);

        // Act: Query with 1000km radius
        $results = $this->service->getPlacesNear(3.1578, 101.7123, 1000);

        // Assert: Should return all places
        $this->assertCount(1, $results);
    }

    /**
     * Test edge case: Coordinates with many decimal places are rounded.
     */
    public function test_coordinates_rounded_in_cache_key(): void
    {
        // Arrange
        Place::factory()->create([
            'latitude' => 3.1590,
            'longitude' => 101.7130,
        ]);

        // Act: Use coordinates with many decimal places
        $this->service->getPlacesNear(3.15789123456, 101.71234567890, 5);

        // Assert: Cache key should have rounded coordinates
        $cacheKey = 'places:near:lat_3.1579:lng_101.7123:radius_5:halal_0:price_all';
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * Test edge case: Null price filter.
     */
    public function test_null_price_filter(): void
    {
        // Arrange
        Place::factory()->create(['price' => 'budget']);

        // Act
        $results = $this->service->getPlaces(price: null);

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue(Cache::has('places:filtered:halal_0:price_all:area_all:tags_none'));
    }

    /**
     * Test edge case: Empty area string.
     */
    public function test_empty_area_string(): void
    {
        // Arrange
        Place::factory()->create();

        // Act
        $results = $this->service->getPlaces(area: '');

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue(Cache::has('places:filtered:halal_0:price_all:area_all:tags_none'));
    }

    /**
     * Test edge case: Tags array with duplicate values.
     */
    public function test_tags_with_duplicates(): void
    {
        // Arrange
        Place::factory()->create(['tags' => ['nasi lemak', 'breakfast']]);

        // Act: Pass duplicate tags
        $results = $this->service->getPlaces(tags: ['nasi lemak', 'nasi lemak', 'breakfast']);

        // Assert
        $this->assertCount(1, $results);
    }

    /**
     * Test cache key generation with special characters in area.
     */
    public function test_cache_key_with_special_characters_in_area(): void
    {
        // Arrange
        Place::factory()->create(['area' => "Bangsar / KL Sentral (Central)"]);

        // Act
        $results = $this->service->getPlaces(area: "Bangsar / KL Sentral (Central)");

        // Assert: Cache key should be created with MD5 hash
        $this->assertCount(1, $results);
        $expectedHash = md5("Bangsar / KL Sentral (Central)");
        $this->assertTrue(Cache::has("places:filtered:halal_0:price_all:area_{$expectedHash}:tags_none"));
    }
}
