<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Place;
use App\Services\RestaurantScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Scrape restaurant data from external sources
 *
 * This command fetches restaurant data from various online sources
 * and populates the places table with Malaysian restaurants.
 *
 * @package App\Console\Commands
 */
class ScrapeRestaurantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'makanguru:scrape
                            {--source=overpass : Data source (overpass|manual)}
                            {--area=Kuala Lumpur : Area to scrape (e.g., "Kuala Lumpur", "Petaling Jaya")}
                            {--radius=5000 : Radius in meters for geospatial search}
                            {--limit=50 : Maximum number of results to fetch}
                            {--dry-run : Preview results without saving to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape Malaysian restaurant data from online sources';

    /**
     * Default coordinates for Malaysian cities
     *
     * @var array<string, array{lat: float, lng: float}>
     */
    private const CITY_COORDINATES = [
        'Kuala Lumpur' => ['lat' => 3.1390, 'lng' => 101.6869],
        'Petaling Jaya' => ['lat' => 3.1073, 'lng' => 101.6067],
        'Bangsar' => ['lat' => 3.1305, 'lng' => 101.6711],
        'KLCC' => ['lat' => 3.1578, 'lng' => 101.7123],
        'Damansara' => ['lat' => 3.1478, 'lng' => 101.6158],
        'Subang Jaya' => ['lat' => 3.0433, 'lng' => 101.5875],
        'Shah Alam' => ['lat' => 3.0733, 'lng' => 101.5185],
    ];

    /**
     * Restaurant scraper service
     *
     * @var RestaurantScraperService
     */
    private RestaurantScraperService $scraperService;

    /**
     * Create a new command instance
     *
     * @param RestaurantScraperService $scraperService
     */
    public function __construct(RestaurantScraperService $scraperService)
    {
        parent::__construct();
        $this->scraperService = $scraperService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $source = $this->option('source');
        $area = $this->option('area');
        $radius = (int) $this->option('radius');
        $limit = (int) $this->option('limit');
        $isDryRun = $this->option('dry-run');

        $this->info("🍜 MakanGuru Restaurant Scraper");
        $this->newLine();

        // Validate source
        if (!in_array($source, ['overpass', 'manual'], true)) {
            $this->error("Invalid source: {$source}. Supported: overpass, manual");
            return Command::FAILURE;
        }

        // Get coordinates for area
        $coordinates = $this->getCoordinates($area);
        if ($coordinates === null) {
            $this->error("Unknown area: {$area}. Available: " . implode(', ', array_keys(self::CITY_COORDINATES)));
            return Command::FAILURE;
        }

        $this->info("📍 Area: {$area}");
        $this->info("🌍 Coordinates: {$coordinates['lat']}, {$coordinates['lng']}");
        $this->info("📏 Radius: {$radius}m");
        $this->info("🔢 Limit: {$limit}");
        $this->newLine();

        // Scrape based on source
        $restaurants = match ($source) {
            'overpass' => $this->scrapeFromOverpass($coordinates, $radius, $limit),
            'manual' => $this->scrapeManualData($area, $limit),
            default => [],
        };

        if (empty($restaurants)) {
            $this->warn('No restaurants found.');
            return Command::SUCCESS;
        }

        $this->info("✅ Found {$this->count($restaurants)} restaurants");
        $this->newLine();

        // Display results
        $this->displayResults($restaurants);

        // Save to database (unless dry-run)
        if (!$isDryRun) {
            $saved = $this->saveToDatabase($restaurants);
            $this->info("💾 Saved {$saved} restaurants to database");
        } else {
            $this->warn('🔍 Dry-run mode: No data saved to database');
        }

        return Command::SUCCESS;
    }

    /**
     * Get coordinates for a given area
     *
     * @param string $area
     * @return array{lat: float, lng: float}|null
     */
    private function getCoordinates(string $area): ?array
    {
        return self::CITY_COORDINATES[$area] ?? null;
    }

    /**
     * Scrape restaurant data from OpenStreetMap via Overpass API
     *
     * @param array{lat: float, lng: float} $coordinates
     * @param int $radius
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private function scrapeFromOverpass(array $coordinates, int $radius, int $limit): array
    {
        $this->info('🌐 Fetching data from OpenStreetMap (Overpass API)...');

        $restaurants = $this->scraperService->fetchFromOverpass(
            $coordinates['lat'],
            $coordinates['lng'],
            $radius,
            $limit
        );

        $this->info("📦 Received {$this->count($restaurants)} valid restaurants from Overpass API");

        return $restaurants;
    }

    /**
     * Scrape manual curated data (placeholder)
     *
     * @param string $area
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private function scrapeManualData(string $area, int $limit): array
    {
        $this->warn('📝 Manual data scraping not yet implemented');
        $this->info('Consider integrating with APIs like:');
        $this->line('  - Google Places API');
        $this->line('  - Foursquare API');
        $this->line('  - Zomato API');

        return [];
    }

    /**
     * Display scraped results in a table
     *
     * @param array<int, array<string, mixed>> $restaurants
     * @return void
     */
    private function displayResults(array $restaurants): void
    {
        $tableData = [];

        foreach (array_slice($restaurants, 0, 10) as $restaurant) {
            $tableData[] = [
                'name' => $restaurant['name'],
                'area' => $restaurant['area'],
                'cuisine' => $restaurant['cuisine_type'],
                'price' => $restaurant['price'],
                'halal' => $restaurant['is_halal'] ? '✓' : '✗',
            ];
        }

        $this->table(
            ['Name', 'Area', 'Cuisine', 'Price', 'Halal'],
            $tableData
        );

        if ($this->count($restaurants) > 10) {
            $remaining = $this->count($restaurants) - 10;
            $this->info("... and {$remaining} more restaurants");
        }
    }

    /**
     * Save restaurants to database
     *
     * @param array<int, array<string, mixed>> $restaurants
     * @return int Number of saved records
     */
    private function saveToDatabase(array $restaurants): int
    {
        $saved = 0;
        $bar = $this->output->createProgressBar($this->count($restaurants));
        $bar->start();

        foreach ($restaurants as $restaurant) {
            try {
                // Validate data before saving
                if (!$this->scraperService->validateRestaurantData($restaurant)) {
                    Log::warning('Invalid restaurant data', ['name' => $restaurant['name'] ?? 'Unknown']);
                    $bar->advance();
                    continue;
                }

                // Check if restaurant already exists (by name and coordinates)
                $exists = Place::where('name', $restaurant['name'])
                    ->where('latitude', $restaurant['latitude'])
                    ->where('longitude', $restaurant['longitude'])
                    ->exists();

                if (!$exists) {
                    Place::create($restaurant);
                    $saved++;
                }

                $bar->advance();
            } catch (\Exception $e) {
                Log::error('Failed to save restaurant', [
                    'name' => $restaurant['name'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $bar->finish();
        $this->newLine();

        return $saved;
    }

    /**
     * Count items in array (type-safe helper)
     *
     * @param array<mixed> $array
     * @return int
     */
    private function count(array $array): int
    {
        return count($array);
    }
}
