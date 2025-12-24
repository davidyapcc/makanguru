<?php

namespace Database\Seeders;

use App\Models\Place;
use App\Services\RestaurantScraperService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class PlaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder fetches real restaurant data from OpenStreetMap
     * for various Malaysian areas to provide quality seed data.
     */
    public function run(): void
    {
        $this->command->info('🌍 Fetching real restaurant data from OpenStreetMap...');

        // Define areas to scrape with coordinates and limits
        $areasToScrape = [
            ['name' => 'Bangsar', 'lat' => 3.1305, 'lng' => 101.6711, 'radius' => 2000, 'limit' => 10],
            ['name' => 'KLCC', 'lat' => 3.1578, 'lng' => 101.7123, 'radius' => 2000, 'limit' => 10],
            ['name' => 'Petaling Jaya', 'lat' => 3.1073, 'lng' => 101.6067, 'radius' => 3000, 'limit' => 10],
            ['name' => 'Damansara', 'lat' => 3.1466, 'lng' => 101.6190, 'radius' => 2000, 'limit' => 10],
            ['name' => 'Subang Jaya', 'lat' => 3.0478, 'lng' => 101.5866, 'radius' => 2000, 'limit' => 10],
            ['name' => 'Bukit Bintang', 'lat' => 3.1466, 'lng' => 101.7103, 'radius' => 1500, 'limit' => 10],
            ['name' => 'Shah Alam', 'lat' => 3.0738, 'lng' => 101.5183, 'radius' => 3000, 'limit' => 10],
        ];

        $scraper = new RestaurantScraperService();
        $totalImported = 0;
        $totalDuplicates = 0;

        foreach ($areasToScrape as $areaConfig) {
            $this->command->info("📍 Scraping {$areaConfig['name']}...");

            try {
                $restaurants = $scraper->fetchFromOverpass(
                    $areaConfig['lat'],
                    $areaConfig['lng'],
                    $areaConfig['radius'],
                    $areaConfig['limit']
                );

                // Import restaurants (skip duplicates)
                $imported = 0;
                $duplicates = 0;

                foreach ($restaurants as $restaurant) {
                    // Check for duplicate by name and approximate location
                    $exists = Place::where('name', $restaurant['name'])
                        ->where('area', 'LIKE', "%{$restaurant['area']}%")
                        ->exists();

                    if (!$exists) {
                        Place::create($restaurant);
                        $imported++;
                    } else {
                        $duplicates++;
                    }
                }

                $totalImported += $imported;
                $totalDuplicates += $duplicates;

                $this->command->info("  ✅ Imported: {$imported} | Skipped duplicates: {$duplicates}");
            } catch (\Exception $e) {
                $this->command->error("  ❌ Failed to scrape {$areaConfig['name']}: {$e->getMessage()}");
                Log::error("PlaceSeeder failed for {$areaConfig['name']}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Small delay to be respectful to OpenStreetMap API
            usleep(500000); // 0.5 seconds
        }

        $this->command->newLine();
        $this->command->info("🎉 Seeding complete!");
        $this->command->info("📊 Total imported: {$totalImported} restaurants");
        $this->command->info("⏭️  Skipped duplicates: {$totalDuplicates}");

        // If no restaurants were imported (e.g., API failure), add fallback golden records
        if ($totalImported === 0) {
            $this->command->warn('⚠️  No restaurants imported from OpenStreetMap. Adding fallback golden records...');
            $this->seedGoldenRecords();
        }
    }

    /**
     * Seed famous Malaysian restaurants as fallback data.
     * Used when OpenStreetMap scraping fails or returns no results.
     */
    private function seedGoldenRecords(): void
    {
        $goldenRecords = [
            [
                'name' => 'Village Park Restaurant',
                'description' => 'Legendary nasi lemak spot, famous for crispy fried chicken and sambal that will make you sweat.',
                'address' => '5, Jalan SS 21/37, Damansara Utama, 47400 Petaling Jaya, Selangor',
                'area' => 'Damansara Utama',
                'latitude' => 3.1351,
                'longitude' => 101.6215,
                'price' => 'budget',
                'tags' => ['nasi lemak', 'local', 'breakfast', 'famous'],
                'is_halal' => true,
                'cuisine_type' => 'Malaysian',
                'opening_hours' => '7:00 AM - 3:00 PM',
            ],
            [
                'name' => 'Jalan Alor Food Street',
                'description' => 'Iconic KL street food haven with endless hawker stalls. Go hungry, leave broke but happy.',
                'address' => 'Jalan Alor, Bukit Bintang, 50200 Kuala Lumpur',
                'area' => 'Bukit Bintang',
                'latitude' => 3.1466,
                'longitude' => 101.7072,
                'price' => 'moderate',
                'tags' => ['street food', 'variety', 'night market', 'tourist-friendly'],
                'is_halal' => false,
                'cuisine_type' => 'Malaysian, Chinese',
                'opening_hours' => '5:00 PM - 4:00 AM',
            ],
            [
                'name' => 'Restoran Yusoof Dan Zakhir',
                'description' => 'The OG of briyani in KL. Fragrant rice, tender meat, and a line that never ends.',
                'address' => '1, Jalan Tun H S Lee, City Centre, 50050 Kuala Lumpur',
                'area' => 'Pudu',
                'latitude' => 3.1419,
                'longitude' => 101.6961,
                'price' => 'budget',
                'tags' => ['briyani', 'indian', 'halal', 'local favorite'],
                'is_halal' => true,
                'cuisine_type' => 'Indian',
                'opening_hours' => '11:00 AM - 11:00 PM',
            ],
            [
                'name' => 'Kim Lian Kee Restaurant',
                'description' => 'Hokkien mee that slaps. Charcoal-fried perfection with a side of nostalgia.',
                'address' => '34, Jalan Petaling, City Centre, 50000 Kuala Lumpur',
                'area' => 'Petaling Street',
                'latitude' => 3.1431,
                'longitude' => 101.6966,
                'price' => 'budget',
                'tags' => ['hokkien mee', 'chinese', 'local', 'heritage'],
                'is_halal' => false,
                'cuisine_type' => 'Chinese',
                'opening_hours' => '5:00 PM - 2:00 AM',
            ],
            [
                'name' => 'The Owls Cafe',
                'description' => 'Hipster minimalist cafe. Overpriced oat milk latte but your Instagram will thank you.',
                'address' => '23, Jalan Telawi 3, Bangsar, 59100 Kuala Lumpur',
                'area' => 'Bangsar',
                'latitude' => 3.1296,
                'longitude' => 101.6714,
                'price' => 'expensive',
                'tags' => ['cafe', 'aesthetic', 'instagram-worthy', 'brunch'],
                'is_halal' => false,
                'cuisine_type' => 'Western, Fusion',
                'opening_hours' => '8:00 AM - 10:00 PM',
            ],
        ];

        foreach ($goldenRecords as $place) {
            // Check for duplicate
            $exists = Place::where('name', $place['name'])->exists();
            if (!$exists) {
                Place::create($place);
            }
        }

        $this->command->info('✅ Added 5 golden record restaurants');
    }
}
