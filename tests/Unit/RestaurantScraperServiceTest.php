<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RestaurantScraperService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Restaurant Scraper Service Tests
 *
 * @package Tests\Unit
 */
class RestaurantScraperServiceTest extends TestCase
{
    private RestaurantScraperService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RestaurantScraperService();
    }

    /**
     * Test fetching from Overpass API with valid response
     *
     * @return void
     */
    public function test_fetch_from_overpass_returns_parsed_restaurants(): void
    {
        // Mock Overpass API response
        Http::fake([
            'overpass-api.de/*' => Http::response([
                'elements' => [
                    [
                        'type' => 'node',
                        'id' => 123456,
                        'lat' => 3.1390,
                        'lon' => 101.6869,
                        'tags' => [
                            'name' => 'Restoran Nasi Lemak Wanjo',
                            'amenity' => 'restaurant',
                            'cuisine' => 'malaysian',
                            'addr:street' => 'Jalan Kampung Baru',
                            'addr:city' => 'Kuala Lumpur',
                            'diet:halal' => 'yes',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $restaurants = $this->service->fetchFromOverpass(3.1390, 101.6869, 5000, 10);

        $this->assertCount(1, $restaurants);
        $this->assertEquals('Restoran Nasi Lemak Wanjo', $restaurants[0]['name']);
        $this->assertEquals('Kuala Lumpur', $restaurants[0]['area']);
        $this->assertTrue($restaurants[0]['is_halal']);
        $this->assertIsArray($restaurants[0]['tags']);
    }

    /**
     * Test handling API failure gracefully
     *
     * @return void
     */
    public function test_fetch_from_overpass_handles_api_failure(): void
    {
        Http::fake([
            'overpass-api.de/*' => Http::response([], 500),
        ]);

        $restaurants = $this->service->fetchFromOverpass(3.1390, 101.6869, 5000, 10);

        $this->assertIsArray($restaurants);
        $this->assertEmpty($restaurants);
    }

    /**
     * Test validating restaurant data with valid input
     *
     * @return void
     */
    public function test_validate_restaurant_data_with_valid_data(): void
    {
        $validData = [
            'name' => 'Test Restaurant',
            'latitude' => 3.1390,
            'longitude' => 101.6869,
            'price' => 'moderate',
            'tags' => ['malaysian'],
        ];

        $result = $this->service->validateRestaurantData($validData);

        $this->assertTrue($result);
    }

    /**
     * Test validating restaurant data with missing required fields
     *
     * @return void
     */
    public function test_validate_restaurant_data_with_missing_fields(): void
    {
        $invalidData = [
            'name' => 'Test Restaurant',
            // Missing latitude, longitude, price
        ];

        $result = $this->service->validateRestaurantData($invalidData);

        $this->assertFalse($result);
    }

    /**
     * Test validating restaurant data with invalid coordinates
     *
     * @return void
     */
    public function test_validate_restaurant_data_with_invalid_coordinates(): void
    {
        $invalidData = [
            'name' => 'Test Restaurant',
            'latitude' => 50.0, // Outside Malaysia
            'longitude' => 101.6869,
            'price' => 'moderate',
        ];

        $result = $this->service->validateRestaurantData($invalidData);

        $this->assertFalse($result);
    }

    /**
     * Test validating restaurant data with invalid price enum
     *
     * @return void
     */
    public function test_validate_restaurant_data_with_invalid_price(): void
    {
        $invalidData = [
            'name' => 'Test Restaurant',
            'latitude' => 3.1390,
            'longitude' => 101.6869,
            'price' => 'super-expensive', // Invalid enum
        ];

        $result = $this->service->validateRestaurantData($invalidData);

        $this->assertFalse($result);
    }

    /**
     * Test parsing element with complete tags
     *
     * @return void
     */
    public function test_parse_element_with_complete_tags(): void
    {
        Http::fake([
            'overpass-api.de/*' => Http::response([
                'elements' => [
                    [
                        'lat' => 3.1390,
                        'lon' => 101.6869,
                        'tags' => [
                            'name' => 'Mamak Stall',
                            'amenity' => 'restaurant',
                            'cuisine' => 'indian;malaysian',
                            'addr:housenumber' => '123',
                            'addr:street' => 'Jalan Bukit Bintang',
                            'addr:postcode' => '50200',
                            'addr:city' => 'Kuala Lumpur',
                            'diet:halal' => 'yes',
                            'diet:vegetarian' => 'yes',
                            'opening_hours' => '24/7',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $restaurants = $this->service->fetchFromOverpass(3.1390, 101.6869, 5000, 10);

        $this->assertCount(1, $restaurants);
        $restaurant = $restaurants[0];

        $this->assertEquals('Mamak Stall', $restaurant['name']);
        $this->assertEquals('Kuala Lumpur', $restaurant['area']);
        $this->assertStringContainsString('123', $restaurant['address']);
        $this->assertTrue($restaurant['is_halal']);
        $this->assertContains('halal', $restaurant['tags']);
        $this->assertContains('vegetarian', $restaurant['tags']);
        $this->assertEquals('24/7', $restaurant['opening_hours']);
    }

    /**
     * Test skipping elements without names
     *
     * @return void
     */
    public function test_skips_elements_without_names(): void
    {
        Http::fake([
            'overpass-api.de/*' => Http::response([
                'elements' => [
                    [
                        'lat' => 3.1390,
                        'lon' => 101.6869,
                        'tags' => [
                            // No name field
                            'amenity' => 'restaurant',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $restaurants = $this->service->fetchFromOverpass(3.1390, 101.6869, 5000, 10);

        $this->assertEmpty($restaurants);
    }

    /**
     * Test handling way elements (with center coordinates)
     *
     * @return void
     */
    public function test_handles_way_elements_with_center_coordinates(): void
    {
        Http::fake([
            'overpass-api.de/*' => Http::response([
                'elements' => [
                    [
                        'type' => 'way',
                        'center' => [
                            'lat' => 3.1390,
                            'lon' => 101.6869,
                        ],
                        'tags' => [
                            'name' => 'Restaurant Building',
                            'amenity' => 'restaurant',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $restaurants = $this->service->fetchFromOverpass(3.1390, 101.6869, 5000, 10);

        $this->assertCount(1, $restaurants);
        $this->assertEquals(3.1390, $restaurants[0]['latitude']);
        $this->assertEquals(101.6869, $restaurants[0]['longitude']);
    }
}
