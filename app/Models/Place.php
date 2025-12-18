<?php

namespace App\Models;

use Database\Factories\PlaceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'address',
        'area',
        'latitude',
        'longitude',
        'price',
        'tags',
        'is_halal',
        'cuisine_type',
        'opening_hours',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'tags' => 'array',
            'is_halal' => 'boolean',
        ];
    }

    /**
     * Scope a query to filter places near a specific location.
     * Uses Haversine formula for distance calculation.
     *
     * @param Builder $query
     * @param float $latitude
     * @param float $longitude
     * @param float $radiusKm
     * @return Builder
     */
    public function scopeNear(Builder $query, float $latitude, float $longitude, float $radiusKm = 10): Builder
    {
        $haversine = "(6371 * acos(cos(radians(?))
                     * cos(radians(latitude))
                     * cos(radians(longitude) - radians(?))
                     + sin(radians(?))
                     * sin(radians(latitude))))";

        return $query
            ->selectRaw("*, {$haversine} AS distance", [$latitude, $longitude, $latitude])
            ->whereRaw("{$haversine} <= ?", [$latitude, $longitude, $latitude, $radiusKm])
            ->orderBy('distance');
    }

    /**
     * Scope a query to filter places by area.
     *
     * @param Builder $query
     * @param string $area
     * @return Builder
     */
    public function scopeInArea(Builder $query, string $area): Builder
    {
        return $query->where('area', 'LIKE', "%{$area}%");
    }

    /**
     * Scope a query to filter places by price range.
     *
     * @param Builder $query
     * @param string $price
     * @return Builder
     */
    public function scopeByPrice(Builder $query, string $price): Builder
    {
        return $query->where('price', $price);
    }

    /**
     * Scope a query to filter halal places only.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeHalalOnly(Builder $query): Builder
    {
        return $query->where('is_halal', true);
    }

    /**
     * Scope a query to filter places that have any of the specified tags.
     *
     * @param Builder $query
     * @param array<string> $tags
     * @return Builder
     */
    public function scopeWithTags(Builder $query, array $tags): Builder
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Scope a query to filter places by cuisine type.
     *
     * @param Builder $query
     * @param string $cuisineType
     * @return Builder
     */
    public function scopeByCuisine(Builder $query, string $cuisineType): Builder
    {
        return $query->where('cuisine_type', 'LIKE', "%{$cuisineType}%");
    }

    /**
     * Get the price label for display.
     *
     * @return string
     */
    public function getPriceLabelAttribute(): string
    {
        return match ($this->price) {
            'budget' => 'RM 10-20',
            'moderate' => 'RM 20-50',
            'expensive' => 'RM 50+',
            default => 'N/A',
        };
    }

    /**
     * Get the halal status label for display.
     *
     * @return string
     */
    public function getHalalStatusAttribute(): string
    {
        return $this->is_halal ? 'Halal' : 'Non-Halal';
    }
}
