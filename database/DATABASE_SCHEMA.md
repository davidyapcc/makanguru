# MakanGuru - Database Schema Documentation

This document provides comprehensive documentation of the MakanGuru database schema, including table structures, indexes, relationships, and usage examples.

## Table of Contents

- [Overview](#overview)
- [Tables](#tables)
  - [places](#places-table)
- [Indexes](#indexes)
- [Model Scopes](#model-scopes)
- [Usage Examples](#usage-examples)
- [Google Maps Integration](#google-maps-integration)
- [Future Considerations](#future-considerations)

---

## Overview

**Database System**: MySQL 8.0 (Production), SQLite (Local Development)

**Character Set**: utf8mb4

**Collation**: utf8mb4_unicode_ci

**ORM**: Laravel Eloquent

**Timezone**: Asia/Kuala_Lumpur (UTC+8)

---

## Tables

### `places` Table

The central table storing restaurant and food place information, including both manually curated data and Google Maps/Places API integration.

#### Schema

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| `google_place_id` | VARCHAR(255) | YES | NULL | Google Places API unique identifier (unique) |
| `name` | VARCHAR(255) | NO | - | Restaurant/place name |
| `description` | TEXT | YES | NULL | Witty, personality-driven description |
| `address` | VARCHAR(255) | NO | - | Full street address |
| `google_maps_url` | VARCHAR(255) | YES | NULL | Google Maps direct link |
| `google_rating` | DECIMAL(2,1) | YES | NULL | Google rating (0.0 - 5.0) |
| `google_rating_count` | INT | YES | NULL | Number of Google reviews |
| `google_price_level` | VARCHAR(255) | YES | NULL | Google price level ($, $$, $$$, $$$$) |
| `area` | VARCHAR(255) | NO | - | Location area (e.g., "Bangsar", "KLCC") |
| `latitude` | DECIMAL(10,7) | NO | - | GPS latitude coordinate |
| `longitude` | DECIMAL(10,7) | NO | - | GPS longitude coordinate |
| `price` | ENUM | NO | - | Price range: 'budget', 'moderate', 'expensive' |
| `tags` | JSON | NO | - | Searchable tags (e.g., ["nasi lemak", "halal", "breakfast"]) |
| `is_halal` | BOOLEAN | NO | FALSE | Halal certification status |
| `cuisine_type` | VARCHAR(255) | YES | NULL | Cuisine type (e.g., "Malaysian", "Chinese") |
| `opening_hours` | VARCHAR(255) | YES | NULL | Human-readable opening hours |
| `phone_number` | VARCHAR(255) | YES | NULL | Contact phone number |
| `website` | VARCHAR(255) | YES | NULL | Official website URL |
| `google_photos` | JSON | YES | NULL | Array of Google photo URLs |
| `google_reviews` | JSON | YES | NULL | Sample Google reviews JSON |
| `business_status` | VARCHAR(255) | YES | NULL | Status: OPERATIONAL, CLOSED_TEMPORARILY, CLOSED_PERMANENTLY |
| `wheelchair_accessible` | BOOLEAN | NO | FALSE | Wheelchair accessibility |
| `takeout_available` | BOOLEAN | NO | FALSE | Takeout service available |
| `delivery_available` | BOOLEAN | NO | FALSE | Delivery service available |
| `dine_in_available` | BOOLEAN | NO | TRUE | Dine-in service available |
| `reservations_accepted` | BOOLEAN | NO | FALSE | Accepts reservations |
| `created_at` | TIMESTAMP | YES | NULL | Record creation timestamp |
| `updated_at` | TIMESTAMP | YES | NULL | Record last update timestamp |

#### Field Details

##### Core Fields

**`id`** (BIGINT UNSIGNED)
- Primary key, auto-incrementing
- Used for internal referencing

**`name`** (VARCHAR(255), NOT NULL)
- Restaurant or food place name
- Example: "Village Park Restaurant", "Jalan Alor Food Street"

**`description`** (TEXT, NULLABLE)
- Optional witty, personality-driven description
- Written in the voice of AI personas (Mak Cik, Gym Bro, Atas)
- Example: "The legendary nasi lemak that made aunties wake up at 5am just to queue!"

**`address`** (VARCHAR(255), NOT NULL)
- Complete street address
- Example: "Jalan 5/27a, Seksyen 1, 46000 Petaling Jaya, Selangor"

**`area`** (VARCHAR(255), NOT NULL, INDEXED)
- General location area for filtering
- Examples: "Bangsar", "KLCC", "Petaling Jaya", "Damansara"
- Used for broad location-based searches

**`latitude`** (DECIMAL(10,7), NOT NULL)
- GPS latitude coordinate
- Precision: 7 decimal places (~1.1cm accuracy)
- Example: 3.1578000 (KLCC)
- Used for geospatial queries

**`longitude`** (DECIMAL(10,7), NOT NULL)
- GPS longitude coordinate
- Precision: 7 decimal places (~1.1cm accuracy)
- Example: 101.7123000 (KLCC)
- Used for geospatial queries

**`price`** (ENUM, NOT NULL, INDEXED)
- Internal price categorization
- Values: `budget`, `moderate`, `expensive`
- Mapped to human-readable labels in model:
  - `budget` → "RM 10-20"
  - `moderate` → "RM 20-50"
  - `expensive` → "RM 50+"

**`tags`** (JSON, NOT NULL)
- Searchable array of keywords
- Examples:
  ```json
  ["nasi lemak", "halal", "breakfast", "spicy"]
  ["western", "cafe", "instagram-worthy", "wifi"]
  ["chinese", "dim sum", "family-friendly"]
  ```
- Used for content-based filtering

**`is_halal`** (BOOLEAN, NOT NULL, INDEXED, DEFAULT: FALSE)
- Halal certification status
- Critical filter for Muslim users
- Default: FALSE (must explicitly set to TRUE)

**`cuisine_type`** (VARCHAR(255), NULLABLE)
- Primary cuisine category
- Examples: "Malaysian", "Chinese", "Indian", "Western", "Japanese", "Fusion"

**`opening_hours`** (VARCHAR(255), NULLABLE)
- Human-readable operating hours
- Example: "Mon-Sun: 7am-3pm", "Daily 24 hours", "Tue-Sun: 11am-10pm (Closed Mon)"

##### Google Maps Integration Fields

**`google_place_id`** (VARCHAR(255), NULLABLE, UNIQUE, INDEXED)
- Google Places API unique identifier
- Format: ChIJ... (encoded string)
- Example: "ChIJN1t_tDeuEmsRUsoyG83frY4"
- Ensures no duplicate places from Google API
- Used for API data synchronization

**`google_maps_url`** (VARCHAR(255), NULLABLE)
- Direct link to Google Maps listing
- Format: `https://maps.google.com/?cid=XXXXX` or shortened URL
- Example: "https://goo.gl/maps/abc123"
- Allows users to open in Google Maps app

**`google_rating`** (DECIMAL(2,1), NULLABLE, INDEXED)
- Google user rating
- Range: 0.0 - 5.0
- Example: 4.5
- Used for filtering high-rated places

**`google_rating_count`** (INT, NULLABLE)
- Number of Google reviews
- Example: 1250
- Indicates rating reliability

**`google_price_level`** (VARCHAR(255), NULLABLE)
- Google's price indicator
- Values: "$", "$$", "$$$", "$$$$"
- Complements internal `price` enum

**`phone_number`** (VARCHAR(255), NULLABLE)
- Contact phone number
- Format: "+60 3-1234 5678" or "03-1234 5678"
- Used for click-to-call functionality

**`website`** (VARCHAR(255), NULLABLE)
- Official website URL
- Example: "https://www.restaurant.com.my"
- Validated URL format

**`google_photos`** (JSON, NULLABLE)
- Array of Google photo URLs
- Structure:
  ```json
  [
    {
      "url": "https://lh3.googleusercontent.com/...",
      "width": 1920,
      "height": 1080,
      "attribution": "Photo by John Doe"
    }
  ]
  ```
- Maximum 5-10 photos recommended
- Used for place gallery display

**`google_reviews`** (JSON, NULLABLE)
- Sample of Google reviews
- Structure:
  ```json
  [
    {
      "author": "John Doe",
      "rating": 5,
      "text": "Amazing nasi lemak! Best in KL.",
      "time": "2024-12-01T10:30:00Z"
    }
  ]
  ```
- Store 3-5 most helpful reviews
- Used for social proof

**`business_status`** (VARCHAR(255), NULLABLE, INDEXED)
- Current operational status from Google
- Values:
  - `OPERATIONAL` - Currently open for business
  - `CLOSED_TEMPORARILY` - Temporarily closed (renovation, holiday)
  - `CLOSED_PERMANENTLY` - Permanently closed
- Used to filter out closed businesses

##### Service Options Fields

**`wheelchair_accessible`** (BOOLEAN, NOT NULL, DEFAULT: FALSE)
- Wheelchair accessibility
- Important for accessibility filtering

**`takeout_available`** (BOOLEAN, NOT NULL, DEFAULT: FALSE)
- Offers takeout/takeaway service

**`delivery_available`** (BOOLEAN, NOT NULL, DEFAULT: FALSE)
- Offers delivery service
- May include third-party delivery (Grab, Foodpanda)

**`dine_in_available`** (BOOLEAN, NOT NULL, DEFAULT: TRUE)
- Offers dine-in service
- Default TRUE (most restaurants)

**`reservations_accepted`** (BOOLEAN, NOT NULL, DEFAULT: FALSE)
- Accepts table reservations
- Useful for planning ahead

---

## Indexes

Indexes are optimized for common query patterns to ensure fast search performance.

### Primary Index

```sql
PRIMARY KEY (`id`)
```

### Composite Indexes

```sql
INDEX `places_area_price_index` (`area`, `price`)
```
- Optimizes queries filtering by location and price range
- Example: "Show me budget places in Bangsar"

### Single Column Indexes

```sql
INDEX `places_is_halal_index` (`is_halal`)
INDEX `places_google_place_id_index` (`google_place_id`)
INDEX `places_google_rating_index` (`google_rating`)
INDEX `places_business_status_index` (`business_status`)
```

### Unique Constraints

```sql
UNIQUE KEY `places_google_place_id_unique` (`google_place_id`)
```
- Prevents duplicate Google Places entries

---

## Model Scopes

Laravel Eloquent query scopes for common filtering operations.

### Geographic Scopes

#### `scopeNear($query, $latitude, $longitude, $radiusKm = 10)`

Find places within a radius using Haversine formula.

**Parameters:**
- `$latitude` (float): Center point latitude
- `$longitude` (float): Center point longitude
- `$radiusKm` (float): Search radius in kilometers (default: 10)

**Returns:** Places ordered by distance (closest first)

**Example:**
```php
// Find places within 5km of KLCC
$places = Place::near(3.1578, 101.7123, 5)->get();
```

**Implementation:**
- Uses Haversine formula: `d = 2R * arcsin(sqrt(sin²(Δφ/2) + cos(φ1) * cos(φ2) * sin²(Δλ/2)))`
- R = Earth's radius (6371 km)
- φ = latitude, λ = longitude
- Returns places with `distance` attribute in km

#### `scopeInArea($query, $area)`

Filter places by area name (LIKE search).

**Parameters:**
- `$area` (string): Area name or partial match

**Example:**
```php
Place::inArea('Bangsar')->get();
Place::inArea('PJ')->get(); // Matches "Petaling Jaya"
```

### Price & Category Scopes

#### `scopeByPrice($query, $price)`

Filter by price range enum.

**Parameters:**
- `$price` (string): One of 'budget', 'moderate', 'expensive'

**Example:**
```php
Place::byPrice('budget')->get();
```

#### `scopeHalalOnly($query)`

Filter halal-certified places only.

**Example:**
```php
Place::halalOnly()->get();
```

#### `scopeByCuisine($query, $cuisineType)`

Filter by cuisine type (LIKE search).

**Parameters:**
- `$cuisineType` (string): Cuisine name or partial match

**Example:**
```php
Place::byCuisine('Malaysian')->get();
Place::byCuisine('Chinese')->get();
```

### Tag-Based Scope

#### `scopeWithTags($query, array $tags)`

Filter places that have ANY of the specified tags.

**Parameters:**
- `$tags` (array): Array of tag strings

**Example:**
```php
Place::withTags(['nasi lemak', 'breakfast'])->get();
Place::withTags(['spicy', 'halal'])->get();
```

### Google Integration Scopes

#### `scopeMinRating($query, $minRating)`

Filter by minimum Google rating.

**Parameters:**
- `$minRating` (float): Minimum rating (0.0 - 5.0)

**Example:**
```php
Place::minRating(4.0)->get(); // 4 stars and above
```

#### `scopeOperational($query)`

Filter operational places only (exclude closed businesses).

**Example:**
```php
Place::operational()->get();
```

#### `scopeWithServices($query, $takeout = false, $delivery = false, $dineIn = false)`

Filter by available services.

**Parameters:**
- `$takeout` (bool): Filter takeout-available places
- `$delivery` (bool): Filter delivery-available places
- `$dineIn` (bool): Filter dine-in-available places

**Example:**
```php
// Places with delivery
Place::withServices(delivery: true)->get();

// Places with both takeout and dine-in
Place::withServices(takeout: true, dineIn: true)->get();
```

---

## Usage Examples

### Basic Queries

```php
// Get all places
$places = Place::all();

// Get place by ID
$place = Place::find(1);

// Search by name
$places = Place::where('name', 'LIKE', '%Village Park%')->get();
```

### Complex Filtering

```php
// Halal budget places in Bangsar
$places = Place::halalOnly()
    ->byPrice('budget')
    ->inArea('Bangsar')
    ->get();

// High-rated Chinese restaurants within 3km of coordinates
$places = Place::near(3.1470, 101.6950, 3)
    ->byCuisine('Chinese')
    ->minRating(4.0)
    ->operational()
    ->get();

// Halal places with nasi lemak tag, rated 4+ stars
$places = Place::withTags(['nasi lemak'])
    ->halalOnly()
    ->minRating(4.0)
    ->get();

// Expensive restaurants with delivery in KLCC area
$places = Place::byPrice('expensive')
    ->inArea('KLCC')
    ->withServices(delivery: true)
    ->operational()
    ->get();
```

### Geospatial Queries

```php
// Find nearest 5 places to user's location
$nearest = Place::near($userLat, $userLng, 5)
    ->operational()
    ->limit(5)
    ->get();

// Find halal places within 10km, ordered by rating
$places = Place::near($lat, $lng, 10)
    ->halalOnly()
    ->operational()
    ->orderBy('google_rating', 'desc')
    ->get();
```

### Accessing Attributes

```php
$place = Place::find(1);

// Computed attributes (from model accessors)
echo $place->price_label;      // "RM 10-20"
echo $place->halal_status;     // "Halal" or "Non-Halal"

// Google data
echo $place->google_rating;    // 4.5
echo $place->google_maps_url;  // "https://goo.gl/maps/..."

// Service checks
if ($place->delivery_available) {
    echo "Delivery available!";
}

// JSON fields (auto-casted to arrays)
$tags = $place->tags;          // ['nasi lemak', 'halal', 'breakfast']
$photos = $place->google_photos; // [['url' => '...'], ...]
```

### Creating/Updating Places

```php
// Create new place
$place = Place::create([
    'name' => 'New Restaurant',
    'address' => '123 Jalan Bangsar',
    'area' => 'Bangsar',
    'latitude' => 3.1300,
    'longitude' => 101.6700,
    'price' => 'moderate',
    'tags' => ['western', 'cafe'],
    'is_halal' => true,
    'cuisine_type' => 'Western',
    'opening_hours' => 'Mon-Sun: 8am-10pm',
]);

// Update with Google data
$place->update([
    'google_place_id' => 'ChIJ...',
    'google_rating' => 4.5,
    'google_rating_count' => 230,
    'google_maps_url' => 'https://goo.gl/maps/abc',
    'business_status' => 'OPERATIONAL',
    'delivery_available' => true,
]);
```

---

## Google Maps Integration

### Google Places API Fields Mapping

When importing data from Google Places API, map the response fields as follows:

```php
[
    'google_place_id' => $apiResponse['place_id'],
    'name' => $apiResponse['name'],
    'address' => $apiResponse['formatted_address'],
    'latitude' => $apiResponse['geometry']['location']['lat'],
    'longitude' => $apiResponse['geometry']['location']['lng'],
    'google_rating' => $apiResponse['rating'] ?? null,
    'google_rating_count' => $apiResponse['user_ratings_total'] ?? null,
    'google_price_level' => $this->mapPriceLevel($apiResponse['price_level'] ?? null),
    'phone_number' => $apiResponse['formatted_phone_number'] ?? null,
    'website' => $apiResponse['website'] ?? null,
    'opening_hours' => $apiResponse['opening_hours']['weekday_text'] ?? null,
    'business_status' => $apiResponse['business_status'] ?? null,
    'google_photos' => $this->extractPhotos($apiResponse['photos'] ?? []),
    'wheelchair_accessible' => $apiResponse['wheelchair_accessible_entrance'] ?? false,
    'dine_in_available' => $apiResponse['dine_in'] ?? true,
    'takeout_available' => $apiResponse['takeout'] ?? false,
    'delivery_available' => $apiResponse['delivery'] ?? false,
    'reservations_accepted' => $apiResponse['reservable'] ?? false,
]
```

### Google Price Level Mapping

```php
private function mapPriceLevel(?int $level): ?string
{
    return match($level) {
        0 => '$',
        1 => '$',
        2 => '$$',
        3 => '$$$',
        4 => '$$$$',
        default => null,
    };
}
```

### Syncing Strategy

1. **Initial Import**: Bulk import from Google Places API
2. **Periodic Updates**: Refresh ratings, status, photos every 7-30 days
3. **On-Demand**: Refresh individual places when users report issues
4. **Conflict Resolution**: Google data takes precedence for ratings/status, manual data for descriptions/tags

---

## Future Considerations

### Planned Enhancements

1. **User Reviews Table**
   - Store MakanGuru-specific user reviews
   - Link to places via foreign key
   - Include persona-specific review style

2. **Place Photos Table**
   - Separate table for photos (both Google and user-uploaded)
   - Better management of image assets
   - Support for multiple photo sources

3. **Business Hours Table**
   - Structured opening hours (separate table)
   - Support for complex schedules
   - Holiday hours override

4. **Favorites/Bookmarks**
   - User-specific place bookmarks
   - Many-to-many relationship via pivot table

5. **Check-ins/Visits**
   - Track user visits to places
   - Generate personalized recommendations

### Performance Optimizations

1. **Full-Text Search Index**
   ```sql
   FULLTEXT INDEX `places_search_index` (`name`, `description`, `cuisine_type`)
   ```

2. **Spatial Indexes**
   ```sql
   SPATIAL INDEX `places_location_index` (`latitude`, `longitude`)
   ```
   - Requires POINT column type
   - Faster geospatial queries

3. **Materialized Views**
   - Pre-computed popular queries
   - Cache frequently accessed combinations

---

## Migrations

### Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Reset and re-run all migrations
php artisan migrate:fresh

# Reset, migrate, and seed
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback

# Show migration status
php artisan migrate:status
```

### Migration Files

1. **`2025_12_17_181313_create_places_table.php`**
   - Creates initial `places` table
   - Core fields and indexes

2. **`2025_12_18_182320_add_google_maps_fields_to_places_table.php`**
   - Adds Google Maps/Places API integration fields
   - Service options fields
   - Additional indexes

---

## Data Validation Rules

### Laravel Validation Example

```php
$rules = [
    'name' => 'required|string|max:255',
    'address' => 'required|string|max:255',
    'area' => 'required|string|max:255',
    'latitude' => 'required|numeric|between:-90,90',
    'longitude' => 'required|numeric|between:-180,180',
    'price' => 'required|in:budget,moderate,expensive',
    'tags' => 'required|array',
    'tags.*' => 'string|max:50',
    'is_halal' => 'boolean',
    'cuisine_type' => 'nullable|string|max:255',
    'google_place_id' => 'nullable|string|unique:places',
    'google_rating' => 'nullable|numeric|between:0,5',
    'google_rating_count' => 'nullable|integer|min:0',
    'phone_number' => 'nullable|string|max:255',
    'website' => 'nullable|url|max:255',
    'business_status' => 'nullable|in:OPERATIONAL,CLOSED_TEMPORARILY,CLOSED_PERMANENTLY',
];
```

---

## Summary Statistics

| Metric | Value |
|--------|-------|
| **Total Columns** | 30 |
| **Indexes** | 7 |
| **JSON Fields** | 3 (tags, google_photos, google_reviews) |
| **Boolean Fields** | 6 (is_halal, wheelchair_accessible, takeout_available, delivery_available, dine_in_available, reservations_accepted) |
| **Nullable Fields** | 18 |
| **Required Fields** | 7 (name, address, area, latitude, longitude, price, tags) |
| **Query Scopes** | 10 |

---

**Last Updated**: 2025-12-19
**Schema Version**: 2.0 (with Google Maps integration)
**Database**: MySQL 8.0 / SQLite 3.x
