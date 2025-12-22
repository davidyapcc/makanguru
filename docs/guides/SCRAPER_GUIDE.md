# MakanGuru Restaurant Scraper Guide

This guide explains how to use the MakanGuru restaurant scraper to populate your database with real restaurant data from OpenStreetMap.

## Table of Contents
- [Overview](#overview)
- [Installation](#installation)
- [Usage](#usage)
- [Command Options](#command-options)
- [Examples](#examples)
- [Data Sources](#data-sources)
- [Troubleshooting](#troubleshooting)
- [Architecture](#architecture)

---

## Overview

The MakanGuru scraper fetches real restaurant data from **OpenStreetMap** (via the Overpass API) and populates your `places` table. This allows you to expand your restaurant database beyond the seeded data with thousands of real Malaysian restaurants.

### Features
- ✅ Fetches real restaurant data from OpenStreetMap
- ✅ Automatic halal detection based on OSM tags
- ✅ Geospatial filtering by area and radius
- ✅ Smart price range detection
- ✅ Tag extraction (cuisine, diet, amenities)
- ✅ Duplicate prevention
- ✅ Dry-run mode for previewing results
- ✅ Progress bar for tracking imports
- ✅ PSR-12 compliant, type-safe code

---

## Installation

No additional installation required! The scraper is built into MakanGuru and uses Laravel's HTTP client.

**Prerequisites:**
- Internet connection (to access Overpass API)
- Sufficient database storage
- Valid `.env` configuration

---

## Usage

### Basic Command

```bash
php artisan makanguru:scrape
```

This will scrape restaurants from **Kuala Lumpur** (default area) within a **5km radius** and limit to **50 results**.

### With Options

```bash
php artisan makanguru:scrape \
  --area="Petaling Jaya" \
  --radius=3000 \
  --limit=100 \
  --dry-run
```

---

## Command Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--source` | string | `overpass` | Data source (`overpass` or `manual`) |
| `--area` | string | `Kuala Lumpur` | Area to scrape (see available areas below) |
| `--radius` | int | `5000` | Search radius in meters (1000-10000 recommended) |
| `--limit` | int | `50` | Maximum number of results to fetch |
| `--dry-run` | flag | `false` | Preview results without saving to database |

### Available Areas

Pre-configured Malaysian cities:
- `Kuala Lumpur`
- `Petaling Jaya`
- `Bangsar`
- `KLCC`
- `Damansara`
- `Subang Jaya`
- `Shah Alam`

---

## Examples

### 1. Preview restaurants in Bangsar (Dry Run)

```bash
php artisan makanguru:scrape --area="Bangsar" --dry-run
```

**Output:**
```
🍜 MakanGuru Restaurant Scraper

📍 Area: Bangsar
🌍 Coordinates: 3.1305, 101.6711
📏 Radius: 5000m
🔢 Limit: 50

🌐 Fetching data from OpenStreetMap (Overpass API)...
📦 Received 32 valid restaurants from Overpass API
✅ Found 32 restaurants

┌──────────────────────────────────┬──────────┬───────────┬──────────┬───────┐
│ Name                             │ Area     │ Cuisine   │ Price    │ Halal │
├──────────────────────────────────┼──────────┼───────────┼──────────┼───────┤
│ Village Park Restaurant          │ Bangsar  │ Malaysian │ moderate │   ✓   │
│ Bangsar Fish Head Corner         │ Bangsar  │ Chinese   │ moderate │   ✗   │
│ ...                              │ ...      │ ...       │ ...      │ ...   │
└──────────────────────────────────┴──────────┴───────────┴──────────┴───────┘

🔍 Dry-run mode: No data saved to database
```

### 2. Import restaurants from KLCC (Live)

```bash
php artisan makanguru:scrape --area="KLCC" --radius=2000 --limit=100
```

**Output:**
```
🍜 MakanGuru Restaurant Scraper

📍 Area: KLCC
🌍 Coordinates: 3.1578, 101.7123
📏 Radius: 2000m
🔢 Limit: 100

🌐 Fetching data from OpenStreetMap (Overpass API)...
📦 Received 87 valid restaurants from Overpass API
✅ Found 87 restaurants

┌──────────────────────────────────┬──────────┬───────────┬──────────┬───────┐
│ Name                             │ Area     │ Cuisine   │ Price    │ Halal │
├──────────────────────────────────┼──────────┼───────────┼──────────┼───────┤
│ Madam Kwan's KLCC                │ KLCC     │ Malaysian │ moderate │   ✓   │
│ Din Tai Fung Pavilion            │ KLCC     │ Chinese   │ expensive│   ✗   │
│ ...                              │ ...      │ ...       │ ...      │ ...   │
└──────────────────────────────────┴──────────┴───────────┴──────────┴───────┘
... and 77 more restaurants

 87/87 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

💾 Saved 71 restaurants to database
(16 duplicates skipped)
```

### 3. Wide area search (10km radius)

```bash
php artisan makanguru:scrape --area="Kuala Lumpur" --radius=10000 --limit=200
```

### 4. Budget restaurants in Subang Jaya

After scraping, use the AI to filter:

```bash
php artisan makanguru:ask "Show me budget halal restaurants in Subang Jaya" --persona=makcik
```

---

## Data Sources

### OpenStreetMap (Overpass API)

**Current Source:** `https://overpass-api.de/api/interpreter`

The scraper queries OSM for:
- `amenity=restaurant` - Full-service restaurants
- `amenity=cafe` - Cafes and coffee shops
- `amenity=fast_food` - Fast food outlets

**Data Quality:**
- ✅ **High coverage** in urban areas (KL, PJ, Bangsar)
- ✅ **Real coordinates** from GPS data
- ⚠️ **Variable metadata** - some places lack cuisine/halal tags
- ⚠️ **Community-maintained** - quality depends on OSM contributors

**Extracted Fields:**
```json
{
  "name": "Restaurant name from OSM",
  "description": "Auto-generated based on cuisine/amenity",
  "address": "Parsed from addr:* tags",
  "area": "City/suburb from OSM",
  "latitude": "GPS coordinate",
  "longitude": "GPS coordinate",
  "price": "Inferred from amenity type (budget|moderate|expensive)",
  "tags": ["cuisine", "halal", "cafe", "vegetarian"],
  "is_halal": "Detected from diet:halal=yes or cuisine=malay",
  "cuisine_type": "From cuisine tag (Malaysian, Chinese, etc.)",
  "opening_hours": "OSM opening_hours tag (if available)"
}
```

### Future Data Sources (Phase 6)

- **Google Places API** - Rich metadata, reviews, photos
- **Foursquare API** - Social check-ins, trending data
- **Zomato API** - Menus, ratings, delivery options
- **Manual curation** - Community submissions via web form

---

## Troubleshooting

### Issue 1: "No restaurants found"

**Symptom:**
```
✅ Found 0 restaurants
```

**Solutions:**
1. **Increase radius**: Try `--radius=10000` (10km)
2. **Try different area**: Some areas have sparse OSM data
3. **Check OSM coverage**: Visit https://www.openstreetmap.org and search for your area

### Issue 2: "Failed to fetch data from Overpass API"

**Symptom:**
```
Error fetching from Overpass API: Connection timeout
```

**Solutions:**
1. **Check internet connection**
2. **Retry after 1 minute** - Overpass API may be rate-limiting
3. **Reduce limit**: Try `--limit=20` to reduce query complexity

### Issue 3: Duplicate warnings in logs

**Symptom:**
```
[2025-12-19 10:00:00] local.WARNING: Invalid restaurant data {"name":"Unknown"}
```

**Solutions:**
This is normal behavior - the scraper skips:
- Restaurants without names
- Invalid coordinates (outside Malaysia)
- Duplicates already in database

### Issue 4: All restaurants marked as non-halal

**Symptom:**
Most restaurants show `is_halal: false`

**Explanation:**
OSM data quality varies. Many restaurants lack the `diet:halal=yes` tag. You can:
1. **Manually update OSM** (contribute back!)
2. **Edit records** in database after import
3. **Use AI inference** - Ask Gemini to detect halal status from name/cuisine

---

## Architecture

### Service Layer Pattern

The scraper follows MakanGuru's **Service Pattern** architecture:

```
┌─────────────────────────────────────────────────────────┐
│  Artisan Command Layer                                  │
│  (ScrapeRestaurantsCommand.php)                        │
│  - Handles CLI options                                  │
│  - Displays output/progress                             │
│  - Orchestrates workflow                                │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  Service Layer                                          │
│  (RestaurantScraperService.php)                        │
│  - Business logic for scraping                          │
│  - API integration (Overpass)                           │
│  - Data parsing and validation                          │
│  - Type-safe operations                                 │
└──────────────────┬──────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────┐
│  Data Layer                                             │
│  (Place Model)                                          │
│  - Eloquent ORM                                         │
│  - Type casts                                           │
│  - Query scopes                                         │
└─────────────────────────────────────────────────────────┘
```

### Key Files

```
app/
├── Console/Commands/
│   └── ScrapeRestaurantsCommand.php    # CLI command
├── Services/
│   └── RestaurantScraperService.php    # Scraping logic
└── Models/
    └── Place.php                        # Data model

tests/Unit/
└── RestaurantScraperServiceTest.php    # Unit tests (10 tests)
```

### Code Quality Standards

- ✅ **PSR-12** coding standards
- ✅ **PHP 8.4** type hints (properties, parameters, return types)
- ✅ **SOLID principles** (Single Responsibility, Dependency Injection)
- ✅ **Comprehensive DocBlocks**
- ✅ **Unit tested** (10 tests, 18+ assertions)

---

## Testing

### Run Unit Tests

```bash
php artisan test --filter=RestaurantScraperServiceTest
```

**Expected Output:**
```
PASS  Tests\Unit\RestaurantScraperServiceTest
✓ fetch from overpass returns parsed restaurants
✓ fetch from overpass handles api failure
✓ validate restaurant data with valid data
✓ validate restaurant data with missing fields
✓ validate restaurant data with invalid coordinates
✓ validate restaurant data with invalid price
✓ parse element with complete tags
✓ skips elements without names
✓ handles way elements with center coordinates

Tests:    10 passed (18 assertions)
Duration: 0.52s
```

### Manual Testing (Dry Run)

```bash
php artisan makanguru:scrape --area="Bangsar" --limit=10 --dry-run
```

Review the table output to verify:
- Restaurant names are sensible
- Areas are correctly parsed
- Coordinates are within Malaysia
- Halal detection is working

---

## Best Practices

### 1. Start with Dry Run
Always preview before importing:
```bash
php artisan makanguru:scrape --area="KLCC" --dry-run
```

### 2. Import Incrementally
Don't import everything at once. Start small:
```bash
# Day 1: Bangsar (50 restaurants)
php artisan makanguru:scrape --area="Bangsar" --limit=50

# Day 2: KLCC (100 restaurants)
php artisan makanguru:scrape --area="KLCC" --limit=100
```

### 3. Use Reasonable Radius
- **Urban areas**: 2-5km is sufficient
- **Suburban areas**: 5-10km recommended
- **Don't exceed 15km** - too much noise

### 4. Monitor Logs
Check logs for issues:
```bash
tail -f storage/logs/laravel.log | grep "Scraper"
```

### 5. Verify Database
After import, verify in Tinker:
```bash
php artisan tinker
>>> Place::count()
>>> Place::halalOnly()->count()
>>> Place::inArea('Bangsar')->pluck('name')
```

---

## Performance Notes

### API Rate Limits

**Overpass API Limits:**
- **Timeout**: 25 seconds per query
- **Rate limit**: ~2 requests per second
- **Daily quota**: No strict limit, but be respectful

**Recommendations:**
- Wait 1-2 seconds between scrapes if running multiple times
- Use `--limit` to cap results (default 50 is safe)
- Don't scrape all of Malaysia in one go

### Database Performance

**Expected Import Speed:**
- **50 restaurants**: ~5-10 seconds
- **100 restaurants**: ~15-20 seconds
- **200 restaurants**: ~30-40 seconds

Bottleneck is usually the Overpass API, not database writes.

---

## Contributing

### Adding New Data Sources

To add a new scraper (e.g., Google Places):

1. **Update Service** (`RestaurantScraperService.php`):
```php
public function fetchFromGooglePlaces(float $lat, float $lng, int $radius): array
{
    // Implementation
}
```

2. **Update Command** (`ScrapeRestaurantsCommand.php`):
```php
private function scrapeFromGooglePlaces(...): array
{
    return $this->scraperService->fetchFromGooglePlaces(...);
}
```

3. **Add Tests**:
```php
public function test_fetch_from_google_places(): void
{
    // Test implementation
}
```

### Improving Halal Detection

Current logic in `RestaurantScraperService::determineHalal()`:
- Checks `diet:halal=yes` tag
- Infers from `cuisine=malay`
- Searches name for "halal" keyword

**Enhancements:**
- Add more cuisine heuristics (e.g., `cuisine=muslim`)
- Use AI to analyze restaurant names
- Cross-reference with external halal databases

---

## License

This scraper is part of MakanGuru, licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Support

For issues or questions:
1. Check the [Troubleshooting](#troubleshooting) section
2. Review logs: `storage/logs/laravel.log`
3. Run tests: `php artisan test --filter=RestaurantScraperServiceTest`
4. Open an issue on GitHub

---

*Last Updated: 2025-12-19*
*Part of MakanGuru Phase 5: OpenStreetMap Integration*
