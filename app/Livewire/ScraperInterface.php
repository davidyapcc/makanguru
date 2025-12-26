<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Place;
use App\Services\RestaurantScraperService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Scraper Interface Component
 *
 * Provides a web UI for scraping restaurant data from OpenStreetMap
 *
 * @package App\Livewire
 */
class ScraperInterface extends Component
{
    /**
     * Selected area to scrape
     *
     * @var string
     */
    public string $selectedArea = 'Kuala Lumpur';

    /**
     * Search radius in meters
     *
     * @var int
     */
    public int $radius = 5000;

    /**
     * Maximum number of results
     *
     * @var int
     */
    public int $limit = 50;

    /**
     * Preview mode (dry-run)
     *
     * @var bool
     */
    public bool $previewMode = true;

    /**
     * Scraping progress
     *
     * @var bool
     */
    public bool $isScrapingNow = false;

    /**
     * Scraped restaurants (preview or results)
     *
     * @var array<int, array<string, mixed>>
     */
    public array $scrapedRestaurants = [];

    /**
     * Scraping statistics
     *
     * @var array{found: int, saved: int, duplicates: int}
     */
    public array $stats = [
        'found' => 0,
        'saved' => 0,
        'duplicates' => 0,
    ];

    /**
     * Error message
     *
     * @var string|null
     */
    public ?string $errorMessage = null;

    /**
     * Success message
     *
     * @var string|null
     */
    public ?string $successMessage = null;

    /**
     * Get location coordinates from config
     *
     * @return array<string, array{lat: float, lng: float}>
     */
    private function getLocationCoordinates(): array
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
     * Boot the component
     *
     * @param RestaurantScraperService $scraperService
     */
    public function boot(RestaurantScraperService $scraperService): void
    {
        $this->scraperService = $scraperService;
    }

    /**
     * Validation rules
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'selectedArea' => 'required|string',
            'radius' => 'required|integer|min:1000|max:15000',
            'limit' => 'required|integer|min:1|max:200',
        ];
    }

    /**
     * Start scraping process
     *
     * @return void
     */
    public function startScraping(): void
    {
        $this->validate();

        $this->isScrapingNow = true;
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->scrapedRestaurants = [];
        $this->stats = ['found' => 0, 'saved' => 0, 'duplicates' => 0];

        try {
            // Get coordinates for selected area
            $areas = $this->getLocationCoordinates();
            $coordinates = $areas[$this->selectedArea] ?? null;

            if ($coordinates === null) {
                throw new \Exception("Invalid area selected: {$this->selectedArea}");
            }

            // Fetch restaurants from Overpass API
            $restaurants = $this->scraperService->fetchFromOverpass(
                $coordinates['lat'],
                $coordinates['lng'],
                $this->radius,
                $this->limit
            );

            $this->stats['found'] = count($restaurants);
            $this->scrapedRestaurants = $restaurants;

            // If not preview mode, save to database
            if (!$this->previewMode) {
                $this->saveRestaurants($restaurants);
            } else {
                $this->successMessage = "Preview complete! Found {$this->stats['found']} restaurants. Toggle preview mode to import.";
            }
        } catch (\Exception $e) {
            $this->errorMessage = "Scraping failed: " . $e->getMessage();
            Log::error('Scraper UI error', ['error' => $e->getMessage()]);
        } finally {
            $this->isScrapingNow = false;
        }
    }

    /**
     * Save restaurants to database
     *
     * @param array<int, array<string, mixed>> $restaurants
     * @return void
     */
    private function saveRestaurants(array $restaurants): void
    {
        foreach ($restaurants as $restaurant) {
            try {
                // Validate data
                if (!$this->scraperService->validateRestaurantData($restaurant)) {
                    Log::warning('Invalid restaurant data', ['name' => $restaurant['name'] ?? 'Unknown']);
                    continue;
                }

                // Check for duplicates
                $exists = Place::where('name', $restaurant['name'])
                    ->where('latitude', $restaurant['latitude'])
                    ->where('longitude', $restaurant['longitude'])
                    ->exists();

                if ($exists) {
                    $this->stats['duplicates']++;
                    continue;
                }

                // Save to database
                Place::create($restaurant);
                $this->stats['saved']++;
            } catch (\Exception $e) {
                Log::error('Failed to save restaurant', [
                    'name' => $restaurant['name'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->successMessage = "Successfully imported {$this->stats['saved']} restaurants! " .
            "({$this->stats['duplicates']} duplicates skipped)";
    }

    /**
     * Clear results and reset form
     *
     * @return void
     */
    public function clearResults(): void
    {
        $this->scrapedRestaurants = [];
        $this->stats = ['found' => 0, 'saved' => 0, 'duplicates' => 0];
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    /**
     * Get available areas
     *
     * @return array<string>
     */
    public function getAvailableAreas(): array
    {
        return array_keys($this->getLocationCoordinates());
    }

    /**
     * Get current database stats
     *
     * @return array{total: int, halal: int, areas: int}
     */
    public function getDatabaseStats(): array
    {
        return [
            'total' => Place::count(),
            'halal' => Place::halalOnly()->count(),
            'areas' => Place::distinct('area')->count('area'),
        ];
    }

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.scraper-interface', [
            'availableAreas' => $this->getAvailableAreas(),
            'dbStats' => $this->getDatabaseStats(),
        ]);
    }
}
