<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Place;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Restaurant List Component
 *
 * Displays all restaurants from the database with filtering and search
 *
 * @package App\Livewire
 */
class RestaurantList extends Component
{
    use WithPagination;

    /**
     * Search query
     *
     * @var string
     */
    public string $search = '';

    /**
     * Filter by halal
     *
     * @var bool
     */
    public bool $filterHalal = false;

    /**
     * Filter by price
     *
     * @var string
     */
    public string $filterPrice = '';

    /**
     * Filter by area
     *
     * @var string
     */
    public string $filterArea = '';

    /**
     * Filter by cuisine
     *
     * @var string
     */
    public string $filterCuisine = '';

    /**
     * Sort field
     *
     * @var string
     */
    public string $sortBy = 'created_at';

    /**
     * Sort direction
     *
     * @var string
     */
    public string $sortDirection = 'desc';

    /**
     * Reset pagination when filters change
     *
     * @return void
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when filters change
     *
     * @return void
     */
    public function updatingFilterHalal(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when filters change
     *
     * @return void
     */
    public function updatingFilterPrice(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when filters change
     *
     * @return void
     */
    public function updatingFilterArea(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when filters change
     *
     * @return void
     */
    public function updatingFilterCuisine(): void
    {
        $this->resetPage();
    }

    /**
     * Sort by column
     *
     * @param string $field
     * @return void
     */
    public function sortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Clear all filters
     *
     * @return void
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterHalal = false;
        $this->filterPrice = '';
        $this->filterArea = '';
        $this->filterCuisine = '';
        $this->resetPage();
    }

    /**
     * Get available areas
     *
     * @return array<string>
     */
    public function getAvailableAreas(): array
    {
        return Place::distinct('area')
            ->pluck('area')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get available cuisines
     *
     * @return array<string>
     */
    public function getAvailableCuisines(): array
    {
        return Place::distinct('cuisine_type')
            ->whereNotNull('cuisine_type')
            ->pluck('cuisine_type')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get restaurants with filters applied
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRestaurants()
    {
        $query = Place::query();

        // Search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('cuisine_type', 'like', '%' . $this->search . '%')
                    ->orWhere('area', 'like', '%' . $this->search . '%');
            });
        }

        // Halal filter
        if ($this->filterHalal) {
            $query->halalOnly();
        }

        // Price filter
        if (!empty($this->filterPrice)) {
            $query->byPrice($this->filterPrice);
        }

        // Area filter
        if (!empty($this->filterArea)) {
            $query->inArea($this->filterArea);
        }

        // Cuisine filter
        if (!empty($this->filterCuisine)) {
            $query->byCuisine($this->filterCuisine);
        }

        // Sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(20);
    }

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.restaurant-list', [
            'restaurants' => $this->getRestaurants(),
            'availableAreas' => $this->getAvailableAreas(),
            'availableCuisines' => $this->getAvailableCuisines(),
            'totalCount' => Place::count(),
        ]);
    }
}
