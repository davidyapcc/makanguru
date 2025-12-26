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
                            {--area=* : Area(s) to scrape. Use --area=all for all cities, or specify multiple: --area="KLCC" --area="Bangsar"}
                            {--radius=5000 : Radius in meters for geospatial search}
                            {--limit=50 : Maximum number of results per area}
                            {--batch-size=100 : Number of records to insert per database batch}
                            {--dry-run : Preview results without saving to database}
                            {--show-progress : Display detailed progress information}
                            {--no-duplicates : Remove duplicate entries before saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape Malaysian restaurant data from online sources';

    /**
     * Get city coordinates from config
     *
     * @return array<string, array{lat: float, lng: float}>
     */
    private function getCityCoordinates(): array
    {
        return config('locations.coordinates', []);
    }

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
        $areas = $this->option('area');
        $radius = (int) $this->option('radius');
        $limit = (int) $this->option('limit');
        $batchSize = (int) $this->option('batch-size');
        $isDryRun = $this->option('dry-run');
        $showProgress = $this->option('show-progress');
        $removeDuplicates = $this->option('no-duplicates');

        $this->displayHeader();

        // Validate source
        if (!in_array($source, ['overpass', 'manual'], true)) {
            $this->error("Invalid source: {$source}. Supported: overpass, manual");
            return Command::FAILURE;
        }

        // Get locations to scrape
        $locations = $this->getLocations($areas);
        if (empty($locations)) {
            $this->error("No valid areas specified. Available: " . implode(', ', array_keys($this->getCityCoordinates())));
            $this->info("Use --area=all to scrape all cities, or specify one or more areas.");
            return Command::FAILURE;
        }

        $this->displayConfiguration($locations, $radius, $limit, $batchSize, $isDryRun, $removeDuplicates);

        // Scrape restaurants
        $restaurants = $this->scrapeRestaurants($source, $locations, $radius, $limit, $showProgress);

        if (empty($restaurants)) {
            $this->warn('No restaurants found.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info("✅ Total restaurants fetched: " . $this->count($restaurants));

        // Remove duplicates if requested
        if ($removeDuplicates) {
            $originalCount = count($restaurants);
            $restaurants = $this->scraperService->removeDuplicates($restaurants);
            $removed = $originalCount - count($restaurants);
            $this->info("🔄 Removed {$removed} duplicate(s). Unique restaurants: " . count($restaurants));
        }

        $this->newLine();

        // Display preview
        $this->displayResults($restaurants);

        // Save to database (unless dry-run)
        if (!$isDryRun) {
            $this->saveBatchToDatabase($restaurants, $batchSize, $showProgress);
        } else {
            $this->warn('🔍 Dry-run mode: No data saved to database');
        }

        return Command::SUCCESS;
    }

    /**
     * Display command header
     *
     * @return void
     */
    private function displayHeader(): void
    {
        $this->info("🍜 MakanGuru Restaurant Scraper - Batch Mode");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();
    }

    /**
     * Get locations to scrape based on area option
     *
     * @param array<int, string> $areas
     * @return array<int, array{lat: float, lng: float, name: string}>
     */
    private function getLocations(array $areas): array
    {
        // If "all" is specified, use all cities
        if (in_array('all', $areas, true)) {
            return $this->getAllLocations();
        }

        // If no areas specified, return empty
        if (empty($areas)) {
            return [];
        }

        // Build locations array from specified areas
        $coordinates = $this->getCityCoordinates();
        $locations = [];

        foreach ($areas as $area) {
            if (isset($coordinates[$area])) {
                $locations[] = [
                    'lat' => $coordinates[$area]['lat'],
                    'lng' => $coordinates[$area]['lng'],
                    'name' => $area,
                ];
            } else {
                $this->warn("⚠️  Unknown area: {$area} (skipping)");
            }
        }

        return $locations;
    }

    /**
     * Get all available locations
     *
     * @return array<int, array{lat: float, lng: float, name: string}>
     */
    private function getAllLocations(): array
    {
        $coordinates = $this->getCityCoordinates();
        $locations = [];

        foreach ($coordinates as $name => $coords) {
            $locations[] = [
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'name' => $name,
            ];
        }

        return $locations;
    }

    /**
     * Display configuration summary
     *
     * @param array<int, array{lat: float, lng: float, name: string}> $locations
     * @param int $radius
     * @param int $limit
     * @param int $batchSize
     * @param bool $isDryRun
     * @param bool $removeDuplicates
     * @return void
     */
    private function displayConfiguration(
        array $locations,
        int $radius,
        int $limit,
        int $batchSize,
        bool $isDryRun,
        bool $removeDuplicates
    ): void {
        $this->info("📋 Configuration:");
        $this->line("   Areas: " . implode(', ', array_column($locations, 'name')));
        $this->line("   Locations: " . count($locations));
        $this->line("   Radius: {$radius}m per location");
        $this->line("   Limit: {$limit} restaurants per location");
        $this->line("   Batch Size: {$batchSize} records per insert");
        $this->line("   Estimated Max: " . (count($locations) * $limit) . " restaurants");
        if ($isDryRun) {
            $this->line("   Mode: 🔍 DRY RUN (no database changes)");
        }
        if ($removeDuplicates) {
            $this->line("   Duplicates: Will be removed before saving");
        }
        $this->newLine();
    }

    /**
     * Scrape restaurants from multiple locations
     *
     * @param string $source
     * @param array<int, array{lat: float, lng: float, name: string}> $locations
     * @param int $radius
     * @param int $limit
     * @param bool $showProgress
     * @return array<int, array<string, mixed>>
     */
    private function scrapeRestaurants(
        string $source,
        array $locations,
        int $radius,
        int $limit,
        bool $showProgress
    ): array {
        if ($source === 'manual') {
            $this->warn('📝 Manual data scraping not yet implemented');
            return [];
        }

        $this->info("🌐 Fetching from OpenStreetMap (Overpass API)...");
        $this->newLine();

        // Use batch fetch if multiple locations
        if (count($locations) > 1) {
            return $this->batchFetchFromOverpass($locations, $radius, $limit, $showProgress);
        }

        // Single location fetch
        $location = $locations[0];
        $this->info("📍 Fetching from: {$location['name']}");
        return $this->scraperService->fetchFromOverpass(
            $location['lat'],
            $location['lng'],
            $radius,
            $limit
        );
    }

    /**
     * Batch fetch restaurants from multiple locations
     *
     * @param array<int, array{lat: float, lng: float, name: string}> $locations
     * @param int $radius
     * @param int $limit
     * @param bool $showProgress
     * @return array<int, array<string, mixed>>
     */
    private function batchFetchFromOverpass(
        array $locations,
        int $radius,
        int $limit,
        bool $showProgress
    ): array {
        $progressBar = null;
        if ($showProgress) {
            $progressBar = $this->output->createProgressBar(count($locations));
            $progressBar->setFormat('verbose');
        }

        $callback = function (
            int $current,
            int $total,
            string $locationName,
            int $found,
            ?string $error = null
        ) use ($progressBar, $showProgress) {
            if ($showProgress && $progressBar !== null) {
                $progressBar->advance();
                if ($error !== null) {
                    $this->newLine();
                    $this->error("   ✗ {$locationName}: {$error}");
                } else {
                    $this->newLine();
                    $this->info("   ✓ {$locationName}: {$found} restaurants");
                }
            }
        };

        $result = $this->scraperService->fetchBatchFromOverpass(
            $locations,
            $radius,
            $limit,
            $callback
        );

        if ($progressBar !== null) {
            $progressBar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info("📊 Batch Summary:");
        $this->line("   Total locations: {$result['total']}");
        $this->line("   Successful: {$result['successful']}");
        $this->line("   Failed: {$result['failed']}");
        $this->line("   Restaurants found: " . count($result['restaurants']));

        return $result['restaurants'];
    }

    /**
     * Get coordinates for a given area
     *
     * @param string $area
     * @return array{lat: float, lng: float}|null
     */
    private function getCoordinates(string $area): ?array
    {
        $coordinates = $this->getCityCoordinates();
        return $coordinates[$area] ?? null;
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
     * Save restaurants to database in batches
     *
     * @param array<int, array<string, mixed>> $restaurants
     * @param int $batchSize
     * @param bool $showProgress
     * @return void
     */
    private function saveBatchToDatabase(array $restaurants, int $batchSize, bool $showProgress): void
    {
        $this->info("💾 Saving to database...");
        $this->newLine();

        $progressBar = null;
        if ($showProgress) {
            $progressBar = $this->output->createProgressBar(count($restaurants));
            $progressBar->setFormat('verbose');
        }

        $callback = function (int $current, int $total) use ($progressBar) {
            if ($progressBar !== null) {
                $progressBar->advance();
            }
        };

        $stats = $this->scraperService->saveBatch($restaurants, $batchSize, $callback);

        if ($progressBar !== null) {
            $progressBar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info("📊 Save Summary:");
        $this->line("   Total: {$stats['total']}");
        $this->line("   Saved: {$stats['saved']}");
        $this->line("   Skipped (duplicates/invalid): {$stats['skipped']}");
        $this->line("   Failed: {$stats['failed']}");
        $this->newLine();

        if ($stats['saved'] > 0) {
            $this->info("✅ Successfully saved {$stats['saved']} restaurant(s) to database!");
        }

        if ($stats['failed'] > 0) {
            $this->warn("⚠️  {$stats['failed']} restaurant(s) failed to save. Check logs for details.");
        }
    }

    /**
     * Save restaurants to database (legacy single-item method)
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
