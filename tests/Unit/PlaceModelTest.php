<?php

namespace Tests\Unit;

use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Place Model Unit Tests
 *
 * Tests all scopes, attributes, and edge cases for the Place model.
 */
class PlaceModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the scopeNear method with valid coordinates.
     */
    public function test_scope_near_filters_by_distance(): void
    {
        // Arrange: Clean database and create places at specific distances
        // Note: This test may fail if PlaceSeeder adds data. Ensure seeders are not run in tests.
        $klccLat = 3.1578;
        $klccLng = 101.7123;

        // Place very close (< 1km from KLCC) - about 0.15km away
        $nearPlace = Place::factory()->create([
            'name' => 'Near Place',
            'latitude' => 3.1590,
            'longitude' => 101.7130,
        ]);

        // Place far away (> 10km from KLCC) - about 15km away
        $farPlace = Place::factory()->create([
            'name' => 'Far Place',
            'latitude' => 3.0500,
            'longitude' => 101.6000,
        ]);

        // Act: Query places within 1km of KLCC
        $results = Place::near($klccLat, $klccLng, 1)->get();

        // Assert: Should only include the near place
        // Filter results to only check our test places
        $testResults = $results->whereIn('id', [$nearPlace->id, $farPlace->id]);
        $this->assertCount(1, $testResults);
        $this->assertEquals('Near Place', $testResults->first()->name);
    }

    /**
     * Test scopeNear orders by distance.
     */
    public function test_scope_near_orders_by_distance(): void
    {
        // Arrange: Create places at different distances
        $centerLat = 3.1578;
        $centerLng = 101.7123;

        Place::factory()->create([
            'name' => 'Far',
            'latitude' => 3.1700,
            'longitude' => 101.7300,
        ]);

        Place::factory()->create([
            'name' => 'Near',
            'latitude' => 3.1580,
            'longitude' => 101.7125,
        ]);

        Place::factory()->create([
            'name' => 'Medium',
            'latitude' => 3.1650,
            'longitude' => 101.7200,
        ]);

        // Act
        $results = Place::near($centerLat, $centerLng, 20)->get();

        // Assert: Results should be ordered by distance
        $this->assertEquals('Near', $results[0]->name);
        $this->assertEquals('Medium', $results[1]->name);
        $this->assertEquals('Far', $results[2]->name);
    }

    /**
     * Test scopeNear with very small radius.
     */
    public function test_scope_near_with_very_small_radius(): void
    {
        // Arrange: Create test places at specific locations
        $veryClose = Place::factory()->create([
            'name' => 'Very Close',
            'latitude' => 3.1578,
            'longitude' => 101.7123,
        ]);
        $farAway = Place::factory()->create([
            'name' => 'Far Away',
            'latitude' => 3.2000,
            'longitude' => 101.8000,
        ]);

        // Act: Query with 0.5km radius
        $results = Place::near(3.1578, 101.7123, 0.5)->get();

        // Assert: Filter to only our test places and verify
        $testResults = $results->whereIn('id', [$veryClose->id, $farAway->id]);
        $this->assertCount(1, $testResults);
        $this->assertEquals('Very Close', $testResults->first()->name);
    }

    /**
     * Test scopeInArea with exact match.
     */
    public function test_scope_in_area_with_exact_match(): void
    {
        // Arrange
        Place::factory()->create(['name' => 'Place 1', 'area' => 'Bangsar']);
        Place::factory()->create(['name' => 'Place 2', 'area' => 'KLCC']);

        // Act
        $results = Place::inArea('Bangsar')->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Place 1', $results->first()->name);
    }

    /**
     * Test scopeInArea with partial match.
     */
    public function test_scope_in_area_with_partial_match(): void
    {
        // Arrange
        Place::factory()->create(['area' => 'Bangsar South']);
        Place::factory()->create(['area' => 'Bangsar Village']);
        Place::factory()->create(['area' => 'KLCC']);

        // Act: Partial match on "Bangsar"
        $results = Place::inArea('Bangsar')->get();

        // Assert: Should match both Bangsar places
        $this->assertCount(2, $results);
    }

    /**
     * Test scopeInArea is case-insensitive.
     */
    public function test_scope_in_area_is_case_insensitive(): void
    {
        // Arrange
        Place::factory()->create(['area' => 'Bangsar']);

        // Act: Query with lowercase
        $results = Place::inArea('bangsar')->get();

        // Assert: Should match (SQL LIKE is case-insensitive by default)
        $this->assertCount(1, $results);
    }

    /**
     * Test scopeByPrice filters correctly.
     */
    public function test_scope_by_price_filters_correctly(): void
    {
        // Arrange
        Place::factory()->create(['name' => 'Budget Place', 'price' => 'budget']);
        Place::factory()->create(['name' => 'Moderate Place', 'price' => 'moderate']);
        Place::factory()->create(['name' => 'Expensive Place', 'price' => 'expensive']);

        // Act
        $budgetResults = Place::byPrice('budget')->get();
        $expensiveResults = Place::byPrice('expensive')->get();

        // Assert
        $this->assertCount(1, $budgetResults);
        $this->assertEquals('Budget Place', $budgetResults->first()->name);
        $this->assertCount(1, $expensiveResults);
        $this->assertEquals('Expensive Place', $expensiveResults->first()->name);
    }

    /**
     * Test scopeHalalOnly filters correctly.
     */
    public function test_scope_halal_only_filters_correctly(): void
    {
        // Arrange
        Place::factory()->create(['name' => 'Halal Place', 'is_halal' => true]);
        Place::factory()->create(['name' => 'Non-Halal Place', 'is_halal' => false]);

        // Act
        $results = Place::halalOnly()->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Halal Place', $results->first()->name);
        $this->assertTrue($results->first()->is_halal);
    }

    /**
     * Test scopeWithTags with single tag.
     */
    public function test_scope_with_tags_single_tag(): void
    {
        // Arrange
        Place::factory()->create([
            'name' => 'Nasi Lemak Place',
            'tags' => ['nasi lemak', 'breakfast', 'halal'],
        ]);
        Place::factory()->create([
            'name' => 'Burger Place',
            'tags' => ['burger', 'western'],
        ]);

        // Act
        $results = Place::withTags(['nasi lemak'])->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Nasi Lemak Place', $results->first()->name);
    }

    /**
     * Test scopeWithTags with multiple tags (OR logic).
     */
    public function test_scope_with_tags_multiple_tags(): void
    {
        // Arrange
        Place::factory()->create([
            'name' => 'Place 1',
            'tags' => ['nasi lemak'],
        ]);
        Place::factory()->create([
            'name' => 'Place 2',
            'tags' => ['burger'],
        ]);
        Place::factory()->create([
            'name' => 'Place 3',
            'tags' => ['pizza'],
        ]);

        // Act: Search for nasi lemak OR burger
        $results = Place::withTags(['nasi lemak', 'burger'])->get();

        // Assert: Should return both Place 1 and Place 2
        $this->assertCount(2, $results);
    }

    /**
     * Test scopeWithTags with empty tags array.
     */
    public function test_scope_with_tags_empty_array(): void
    {
        // Arrange
        Place::factory()->create(['tags' => ['nasi lemak']]);

        // Act
        $results = Place::withTags([])->get();

        // Assert: Empty tags array returns all results (no filter applied)
        // This is expected behavior - empty filter means no restriction
        $this->assertCount(1, $results);
    }

    /**
     * Test scopeByCuisine with exact match.
     */
    public function test_scope_by_cuisine_exact_match(): void
    {
        // Arrange
        Place::factory()->create(['name' => 'Place 1', 'cuisine_type' => 'Malaysian']);
        Place::factory()->create(['name' => 'Place 2', 'cuisine_type' => 'Chinese']);

        // Act
        $results = Place::byCuisine('Malaysian')->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Place 1', $results->first()->name);
    }

    /**
     * Test scopeByCuisine with partial match.
     */
    public function test_scope_by_cuisine_partial_match(): void
    {
        // Arrange
        Place::factory()->create(['cuisine_type' => 'Chinese-Malaysian Fusion']);
        Place::factory()->create(['cuisine_type' => 'Chinese']);

        // Act
        $results = Place::byCuisine('Chinese')->get();

        // Assert: Should match both
        $this->assertCount(2, $results);
    }

    /**
     * Test scopeMinRating filters correctly.
     */
    public function test_scope_min_rating_filters_correctly(): void
    {
        // Arrange
        Place::factory()->create(['name' => 'High Rated', 'google_rating' => 4.5]);
        Place::factory()->create(['name' => 'Low Rated', 'google_rating' => 3.2]);
        Place::factory()->create(['name' => 'No Rating', 'google_rating' => null]);

        // Act
        $results = Place::minRating(4.0)->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('High Rated', $results->first()->name);
    }

    /**
     * Test scopeOperational filters correctly.
     */
    public function test_scope_operational_filters_correctly(): void
    {
        // Arrange
        Place::factory()->create(['name' => 'Open Place', 'business_status' => 'OPERATIONAL']);
        Place::factory()->create(['name' => 'Closed Place', 'business_status' => 'CLOSED_TEMPORARILY']);

        // Act
        $results = Place::operational()->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Open Place', $results->first()->name);
    }

    /**
     * Test scopeWithServices with takeout.
     */
    public function test_scope_with_services_takeout(): void
    {
        // Arrange
        Place::factory()->create([
            'name' => 'Takeout Place',
            'takeout_available' => true,
            'delivery_available' => false,
        ]);
        Place::factory()->create([
            'name' => 'Dine-in Only',
            'takeout_available' => false,
        ]);

        // Act
        $results = Place::withServices(takeout: true)->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Takeout Place', $results->first()->name);
    }

    /**
     * Test scopeWithServices with multiple service filters.
     */
    public function test_scope_with_services_multiple_filters(): void
    {
        // Arrange
        Place::factory()->create([
            'name' => 'Full Service',
            'takeout_available' => true,
            'delivery_available' => true,
            'dine_in_available' => true,
        ]);
        Place::factory()->create([
            'name' => 'Partial Service',
            'takeout_available' => true,
            'delivery_available' => false,
            'dine_in_available' => true,
        ]);

        // Act: Must have both takeout AND delivery
        $results = Place::withServices(takeout: true, delivery: true)->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Full Service', $results->first()->name);
    }

    /**
     * Test getPriceLabelAttribute returns correct labels.
     */
    public function test_price_label_attribute(): void
    {
        // Arrange & Act
        $budget = Place::factory()->make(['price' => 'budget']);
        $moderate = Place::factory()->make(['price' => 'moderate']);
        $expensive = Place::factory()->make(['price' => 'expensive']);

        // Assert
        $this->assertEquals('RM 10-20', $budget->price_label);
        $this->assertEquals('RM 20-50', $moderate->price_label);
        $this->assertEquals('RM 50+', $expensive->price_label);
    }

    /**
     * Test getHalalStatusAttribute returns correct status.
     */
    public function test_halal_status_attribute(): void
    {
        // Arrange & Act
        $halal = Place::factory()->make(['is_halal' => true]);
        $nonHalal = Place::factory()->make(['is_halal' => false]);

        // Assert
        $this->assertEquals('Halal', $halal->halal_status);
        $this->assertEquals('Non-Halal', $nonHalal->halal_status);
    }

    /**
     * Test combining multiple scopes.
     */
    public function test_combining_multiple_scopes(): void
    {
        // Arrange
        Place::factory()->create([
            'name' => 'Perfect Match',
            'area' => 'Bangsar',
            'price' => 'budget',
            'is_halal' => true,
            'tags' => ['nasi lemak'],
        ]);
        Place::factory()->create([
            'name' => 'Wrong Area',
            'area' => 'KLCC',
            'price' => 'budget',
            'is_halal' => true,
            'tags' => ['nasi lemak'],
        ]);
        Place::factory()->create([
            'name' => 'Wrong Price',
            'area' => 'Bangsar',
            'price' => 'expensive',
            'is_halal' => true,
            'tags' => ['nasi lemak'],
        ]);

        // Act: Combine area, price, halal filters
        $results = Place::inArea('Bangsar')
            ->byPrice('budget')
            ->halalOnly()
            ->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Perfect Match', $results->first()->name);
    }

    /**
     * Test JSON casting for tags field.
     */
    public function test_tags_are_cast_to_array(): void
    {
        // Arrange
        $place = Place::factory()->create([
            'tags' => ['tag1', 'tag2', 'tag3'],
        ]);

        // Act
        $fresh = Place::find($place->id);

        // Assert
        $this->assertIsArray($fresh->tags);
        $this->assertEquals(['tag1', 'tag2', 'tag3'], $fresh->tags);
    }

    /**
     * Test decimal casting for coordinates.
     */
    public function test_coordinates_are_cast_to_decimal(): void
    {
        // Arrange
        $place = Place::factory()->create([
            'latitude' => 3.1578952,
            'longitude' => 101.7123456,
        ]);

        // Act
        $fresh = Place::find($place->id);

        // Assert: Should be rounded to 7 decimal places
        $this->assertEquals('3.1578952', $fresh->latitude);
        $this->assertEquals('101.7123456', $fresh->longitude);
    }

    /**
     * Test boolean casting for is_halal.
     */
    public function test_is_halal_is_cast_to_boolean(): void
    {
        // Arrange
        $place = Place::factory()->create(['is_halal' => true]);

        // Act
        $fresh = Place::find($place->id);

        // Assert
        $this->assertIsBool($fresh->is_halal);
        $this->assertTrue($fresh->is_halal);
    }

    /**
     * Test edge case: Empty tags array.
     */
    public function test_empty_tags_array(): void
    {
        // Arrange
        $place = Place::factory()->create(['tags' => []]);

        // Act
        $fresh = Place::find($place->id);

        // Assert
        $this->assertIsArray($fresh->tags);
        $this->assertEmpty($fresh->tags);
    }

    /**
     * Test edge case: Null cuisine type.
     */
    public function test_null_cuisine_type(): void
    {
        // Arrange
        $place = Place::factory()->create(['cuisine_type' => null]);

        // Act & Assert
        $this->assertNull($place->cuisine_type);
    }

    /**
     * Test edge case: Very long area name.
     */
    public function test_very_long_area_name(): void
    {
        // Arrange
        $longArea = str_repeat('Very Long Area Name ', 10);
        $place = Place::factory()->create(['area' => $longArea]);

        // Act
        $fresh = Place::find($place->id);

        // Assert
        $this->assertEquals($longArea, $fresh->area);
    }
}
