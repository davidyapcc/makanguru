<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Place>
 */
class PlaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prices = ['budget', 'moderate', 'expensive'];
        $cuisines = ['Malaysian', 'Chinese', 'Indian', 'Western', 'Healthy', 'Fusion'];
        $areas = ['Bangsar', 'KLCC', 'Petaling Jaya', 'Damansara', 'Bukit Bintang', 'Subang'];

        return [
            'name' => fake()->company() . ' ' . fake()->randomElement(['Restaurant', 'Cafe', 'Bistro', 'Eatery']),
            'description' => fake()->sentence(12),
            'address' => fake()->streetAddress() . ', ' . fake()->city() . ', Selangor',
            'area' => fake()->randomElement($areas),
            'latitude' => fake()->latitude(3.0, 3.3),
            'longitude' => fake()->longitude(101.5, 101.8),
            'price' => fake()->randomElement($prices),
            'tags' => fake()->randomElements(['halal', 'spicy', 'healthy', 'breakfast', 'lunch', 'dinner'], 3),
            'is_halal' => fake()->boolean(60),
            'cuisine_type' => fake()->randomElement($cuisines),
            'opening_hours' => fake()->randomElement([
                '7:00 AM - 10:00 PM',
                '10:00 AM - 11:00 PM',
                '24 hours',
                '11:00 AM - 3:00 PM, 6:00 PM - 10:00 PM',
            ]),
        ];
    }
}
