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
- ✅ **Batch processing for hundreds/thousands of restaurants**
- ✅ **Multi-area scraping in a single command**
- ✅ Automatic halal detection based on OSM tags
- ✅ Geospatial filtering by area and radius
- ✅ Smart price range detection
- ✅ Tag extraction (cuisine, diet, amenities)
- ✅ **Intelligent duplicate removal**
- ✅ **Transaction-based batch inserts for performance**
- ✅ Dry-run mode for previewing results
- ✅ **Detailed progress tracking and statistics**
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
| `--area` | string/array | `[]` | Area(s) to scrape. Use `--area=all` for all cities, or specify multiple areas |
| `--radius` | int | `5000` | Search radius in meters per area (1000-15000 recommended) |
| `--limit` | int | `50` | Maximum number of results to fetch **per area** |
| `--batch-size` | int | `100` | Number of records to insert per database batch |
| `--dry-run` | flag | `false` | Preview results without saving to database |
| `--show-progress` | flag | `false` | Display detailed progress information during scraping |
| `--no-duplicates` | flag | `false` | Remove duplicate entries before saving to database |

### Available Areas

**Total: 48 pre-configured locations across Klang Valley**

All locations are centrally configured in `config/locations.php` for easy maintenance and consistency across the application.

**Central Kuala Lumpur (9 areas):**
- `Kuala Lumpur`, `KLCC`, `Bangsar`, `Bukit Bintang`
- `Cheras`, `Sentul`, `Kepong`, `Setapak`, `Wangsa Maju`

**Petaling District (9 areas):**
- `Petaling Jaya`, `Damansara`, `Subang Jaya`, `Sunway`
- `Puchong`, `Seri Kembangan`, `Kota Damansara`, `Bandar Utama`, `Sri Petaling`

**Shah Alam & Klang (4 areas):**
- `Shah Alam`, `Klang`, `Bandar Bukit Tinggi`, `Setia Alam`

**Ampang & Selayang (3 areas):**
- `Ampang`, `Selayang`, `Batu Caves`

**Kajang & South (3 areas):**
- `Kajang`, `Bangi`, `Semenyih`

**Cyberjaya & Putrajaya (2 areas):**
- `Cyberjaya`, `Putrajaya`

**Gombak & Rawang (2 areas):**
- `Rawang`, `Gombak`

**Popular Neighborhoods (10 areas):**
- `Mont Kiara`, `Hartamas`, `Desa Park City`, `Taman Tun Dr Ismail` (TTDI)
- `Sri Hartamas`, `Publika`, `Mid Valley`, `The Gardens`
- `Pavilion`, `Suria KLCC`

**Other Areas (6 areas):**
- `USJ`, `Ara Damansara`, `Old Klang Road`
- `Cheras Leisure Mall`, `Taman Connaught`, `Seremban`

**Special Keywords:**
- `all` - Scrapes all 48 pre-configured locations in one command

  ⚠️ **WARNING**: Using `--area=all` will attempt to scrape thousands of restaurants!
  - Estimated: 48 areas × 50 limit = 2,400 restaurants (default settings)
  - Estimated: 48 areas × 200 limit = 9,600 restaurants (max recommended)
  - Runtime: 20-40 minutes for `--area=all`
  - Always use `--dry-run` first to preview coverage

---

## Examples

### Basic Examples

#### 1. Scrape a Single Area (KLCC)

```bash
php artisan makanguru:scrape --area="KLCC"
```

This will fetch up to 50 restaurants within 5km of KLCC and save them to the database.

#### 2. Preview restaurants in Bangsar (Dry Run)

```bash
php artisan makanguru:scrape --area="Bangsar" --dry-run
```

**Output:**
```
🍜 MakanGuru Restaurant Scraper - Batch Mode
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 Configuration:
   Areas: Bangsar
   Locations: 1
   Radius: 5000m per location
   Limit: 50 restaurants per location
   Batch Size: 100 records per insert
   Estimated Max: 50 restaurants
   Mode: 🔍 DRY RUN (no database changes)

🌐 Fetching from OpenStreetMap (Overpass API)...

📍 Fetching from: Bangsar

✅ Total restaurants fetched: 48

┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┳━━━━━━━━━━━┳━━━━━━━━━━━┳━━━━━━━━━┳━━━━━━━┓
┃ Name                           ┃ Area      ┃ Cuisine   ┃ Price   ┃ Halal ┃
┡━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━╇━━━━━━━━━━━╇━━━━━━━━━━━╇━━━━━━━━━╇━━━━━━━┩
│ Nasi Lemak Wanjo               │ Bangsar   │ Malaysian │ budget  │ ✓     │
│ Wondermama Bangsar             │ Bangsar   │ Malaysian │ moderate│ ✗     │
│ ...                            │ ...       │ ...       │ ...     │ ...   │
└────────────────────────────────┴───────────┴───────────┴─────────┴───────┘

🔍 Dry-run mode: No data saved to database
```

---

### Batch Processing Examples

#### 3. Scrape Multiple Specific Areas

```bash
php artisan makanguru:scrape \
  --area="KLCC" \
  --area="Bangsar" \
  --area="Damansara" \
  --limit=100 \
  --show-progress
```

**What it does:**
- Scrapes 3 areas: KLCC, Bangsar, and Damansara
- Fetches up to 100 restaurants per area (max 300 total)
- Shows detailed progress for each location
- 2-second delay between API requests to respect rate limits

**Output with `--show-progress`:**
```
🍜 MakanGuru Restaurant Scraper - Batch Mode
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 Configuration:
   Areas: KLCC, Bangsar, Damansara
   Locations: 3
   Radius: 5000m per location
   Limit: 100 restaurants per location
   Batch Size: 100 records per insert
   Estimated Max: 300 restaurants

🌐 Fetching from OpenStreetMap (Overpass API)...

   ✓ KLCC: 97 restaurants
   ✓ Bangsar: 85 restaurants
   ✓ Damansara: 92 restaurants

📊 Batch Summary:
   Total locations: 3
   Successful: 3
   Failed: 0
   Restaurants found: 274

✅ Total restaurants fetched: 274

[Preview table...]

💾 Saving to database...

📊 Save Summary:
   Total: 274
   Saved: 268
   Skipped (duplicates/invalid): 6
   Failed: 0

✅ Successfully saved 268 restaurant(s) to database!
```

#### 4. Scrape Popular Food Areas (Recommended)

```bash
php artisan makanguru:scrape \
  --area="Bangsar" \
  --area="Mont Kiara" \
  --area="KLCC" \
  --area="Publika" \
  --area="Mid Valley" \
  --area="Sunway" \
  --limit=100 \
  --batch-size=150 \
  --no-duplicates \
  --show-progress
```

**What it does:**
- Scrapes 6 popular foodie neighborhoods
- 100 restaurants per area × 6 areas = **up to 600 restaurants**
- Focused on areas with high restaurant density
- Shows detailed progress

**Estimated Runtime:** 3-5 minutes

#### 5. Scrape ALL 50 Locations (⚠️ Use with Caution!)

```bash
php artisan makanguru:scrape \
  --area=all \
  --limit=50 \
  --radius=5000 \
  --batch-size=250 \
  --no-duplicates \
  --show-progress
```

**What it does:**
- Scrapes **all 50 pre-configured Klang Valley locations**
- 50 restaurants per area × 50 areas = **up to 2,500 restaurants**
- 5km radius per location (default coverage)
- Saves in batches of 250 records (optimized for very large datasets)
- Automatically removes duplicates before saving
- Shows detailed progress

**Estimated Runtime:** 20-30 minutes (depends on API response time)

⚠️ **Important Notes:**
- Always run with `--dry-run` first to estimate data volume
- Monitor API rate limits (Overpass API has fair use policies)
- Ensure sufficient database storage (~500MB for 2,500 restaurants)
- Best run during off-peak hours

#### 6. Maximum Coverage (10,000+ Restaurants)

```bash
# EXTREMELY LARGE IMPORT - Use only if you need comprehensive coverage
php artisan makanguru:scrape \
  --area=all \
  --limit=200 \
  --radius=10000 \
  --batch-size=250 \
  --no-duplicates \
  --show-progress
```

**What it does:**
- Maximum coverage: 200 restaurants × 50 areas = **up to 10,000 restaurants**
- 10km radius per location (covers entire metropolitan areas)
- Very large batch size (250) for fastest database inserts
- Automatically removes duplicates
- Full progress tracking

**Estimated Runtime:** 40-60 minutes

**Performance Tips:**
- Use `--batch-size=250` or higher for 10,000+ restaurants
- Enable `--no-duplicates` to avoid database duplicate checks
- Use `--show-progress` to monitor long-running jobs
- Run during off-peak hours to avoid API rate limits
- Ensure 1GB+ database storage available

#### 7. Dry Run Before Large Import (Always Recommended!)

```bash
# First: Preview what you'll get
php artisan makanguru:scrape \
  --area=all \
  --limit=200 \
  --radius=10000 \
  --dry-run

# Then: Run the actual import if satisfied
php artisan makanguru:scrape \
  --area=all \
  --limit=200 \
  --radius=10000 \
  --batch-size=250 \
  --no-duplicates \
  --show-progress
```

---

### Old Format Examples (Single Area)

#### Legacy: Preview restaurants in Bangsar (Dry Run)

```bash
php artisan makanguru:scrape --area="Bangsar" --dry-run
```

**Output:**
```
🍜 MakanGuru Restaurant Scraper - Batch Mode

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

## Performance Optimization & Best Practices

### Batch Processing Performance

The batch scraper is optimized for handling hundreds to thousands of restaurants efficiently.

#### Recommended Configurations

**Small Import (50-200 restaurants):**
```bash
php artisan makanguru:scrape \
  --area="KLCC" \
  --limit=200 \
  --batch-size=100
```
- Runtime: ~30 seconds
- Default batch size works well

**Medium Import (200-500 restaurants):**
```bash
php artisan makanguru:scrape \
  --area="KLCC" \
  --area="Bangsar" \
  --area="Damansara" \
  --limit=200 \
  --batch-size=150 \
  --no-duplicates \
  --show-progress
```
- Runtime: ~2-3 minutes
- Increase batch size to 150
- Enable duplicate removal
- Monitor with progress bar

**Large Import (500-1000 restaurants):**
```bash
php artisan makanguru:scrape \
  --area=all \
  --limit=150 \
  --radius=7000 \
  --batch-size=200 \
  --no-duplicates \
  --show-progress
```
- Runtime: ~5-8 minutes
- Batch size 200+ recommended
- Always use `--no-duplicates`
- Always use `--show-progress`

**Very Large Import (1000+ restaurants):**
```bash
php artisan makanguru:scrape \
  --area=all \
  --limit=200 \
  --radius=10000 \
  --batch-size=250 \
  --no-duplicates \
  --show-progress
```
- Runtime: ~10-15 minutes
- Maximum batch size (250+)
- Consider running during off-peak hours
- Monitor database disk space

### Performance Tips

1. **Batch Size Optimization**
   - Default: `100` (good for most cases)
   - Medium datasets (500+): `150-200`
   - Large datasets (1000+): `200-250`
   - **Don't exceed 300** - diminishing returns and memory issues

2. **Duplicate Handling**
   - Use `--no-duplicates` for large imports
   - Removes duplicates in memory before database insertion
   - Faster than database duplicate checks
   - Reduces database I/O by ~15-20%

3. **Progress Tracking**
   - Enable `--show-progress` for jobs taking >2 minutes
   - Helps identify stuck/slow locations
   - Minimal performance overhead (~2-3%)

4. **Memory Management**
   - PHP memory limit: 512MB recommended for 1000+ restaurants
   - Update `php.ini`: `memory_limit = 512M`
   - Or use runtime flag: `php -d memory_limit=512M artisan makanguru:scrape`

5. **API Rate Limiting**
   - Built-in 2-second delay between location requests
   - Respects Overpass API fair use policy
   - **Do not scrape more than 10 cities per hour**
   - Use `--dry-run` first for large jobs

### Database Optimization

**Before Large Imports:**
```bash
# Disable foreign key checks (MySQL only)
# Speeds up bulk inserts by ~30%
php artisan db:mysql "SET foreign_key_checks=0;"

# Run your scrape
php artisan makanguru:scrape --area=all --limit=200 --batch-size=250

# Re-enable foreign key checks
php artisan db:mysql "SET foreign_key_checks=1;"
```

**Transaction Safety:**
- Each batch is wrapped in a database transaction
- If a batch fails, it rolls back without corrupting data
- Failed restaurants are logged to `storage/logs/laravel.log`

### Monitoring & Logging

**Check Progress:**
```bash
# Monitor in real-time
tail -f storage/logs/laravel.log | grep "Overpass\|Batch"

# Count total restaurants
php artisan tinker
>>> Place::count()
```

**Log Locations:**
- Fetch errors: `storage/logs/laravel.log` (search for "Overpass API")
- Save errors: `storage/logs/laravel.log` (search for "Failed to save restaurant")
- Batch failures: `storage/logs/laravel.log` (search for "Batch transaction failed")

### Best Practices

1. **Always dry-run first for unfamiliar areas**
   ```bash
   php artisan makanguru:scrape --area="New Area" --dry-run
   ```

2. **Scrape incrementally for new cities**
   ```bash
   # Start small
   php artisan makanguru:scrape --area="New City" --limit=50

   # Expand if data quality is good
   php artisan makanguru:scrape --area="New City" --limit=200
   ```

3. **Schedule regular updates**
   ```bash
   # In app/Console/Kernel.php
   $schedule->command('makanguru:scrape --area=all --limit=50')
            ->weekly()
            ->sundays()
            ->at('03:00');
   ```

4. **Backup before large imports**
   ```bash
   php artisan db:backup  # If you have backup package installed
   # Or manual MySQL dump
   mysqldump -u root -p makanguru > backup_$(date +%Y%m%d).sql
   ```

5. **Clean up old duplicates periodically**
   ```sql
   -- Find duplicate restaurants by name + coordinates
   SELECT name, latitude, longitude, COUNT(*) as count
   FROM places
   GROUP BY name, latitude, longitude
   HAVING count > 1;
   ```

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

### Adding New Locations

All location data is centralized in `config/locations.php`. To add a new area:

1. **Update coordinates array**:
```php
// config/locations.php
'coordinates' => [
    // ... existing locations
    'New Area Name' => ['lat' => 3.1234, 'lng' => 101.5678],
],
```

2. **(Optional) Add to seeder configuration**:
```php
// config/locations.php
'seeder' => [
    // ... existing configs
    ['name' => 'New Area Name', 'radius' => 2000, 'limit' => 10],
],
```

3. **(Optional) Add to regional groupings**:
```php
// config/locations.php
'regions' => [
    'Your Region' => [
        // ... existing areas
        'New Area Name',
    ],
],
```

**Benefits of centralized config:**
- Changes automatically apply to CLI, Web UI, and Seeder
- No code modifications needed
- Type-safe configuration
- Easy to maintain and scale

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
