# MakanGuru - Database Documentation

Quick reference guide for the MakanGuru database schema and usage.

## 📋 Quick Links

- **[DATABASE_SCHEMA.md](./DATABASE_SCHEMA.md)** - Complete schema documentation with field descriptions, indexes, and usage examples
- **[ERD.md](./ERD.md)** - Entity Relationship Diagrams and data flow visualizations
- **[Migrations](./migrations/)** - Database migration files

## 🗄️ Database Overview

**Current Version**: 2.0 (with Google Maps integration)

**Tables**: 1 (places)

**Total Columns**: 30

**Indexes**: 7 (optimized for common queries)

## 🚀 Quick Start

### View Schema
```bash
# SQLite (local)
sqlite3 database/database.sqlite ".schema places"

# MySQL (production)
mysql -u makanguru -p -e "DESCRIBE places;" makanguru
```

### Run Migrations
```bash
# Run all pending migrations
php artisan migrate

# Reset and re-run all migrations
php artisan migrate:fresh

# Reset, migrate, and seed
php artisan migrate:fresh --seed
```

### Common Queries
```php
// Find halal places in Bangsar
Place::halalOnly()->inArea('Bangsar')->get();

// Find places within 5km
Place::near(3.1578, 101.7123, 5)->get();

// High-rated places with delivery
Place::minRating(4.0)
    ->withServices(delivery: true)
    ->operational()
    ->get();
```

## 📊 Schema Summary

### Core Fields
- **Identity**: id, name, description, address
- **Location**: area, latitude, longitude
- **Category**: price, tags, is_halal, cuisine_type
- **Hours**: opening_hours

### Google Maps Fields (New in v2.0)
- **Integration**: google_place_id, google_maps_url
- **Ratings**: google_rating, google_rating_count, google_price_level
- **Media**: google_photos, google_reviews
- **Status**: business_status
- **Contact**: phone_number, website
- **Services**: takeout_available, delivery_available, dine_in_available, reservations_accepted
- **Accessibility**: wheelchair_accessible

## 🔍 Query Scopes

| Scope | Description | Example |
|-------|-------------|---------|
| `near($lat, $lng, $km)` | Find places within radius | `Place::near(3.15, 101.71, 5)->get()` |
| `inArea($area)` | Filter by area name | `Place::inArea('Bangsar')->get()` |
| `byPrice($price)` | Filter by price range | `Place::byPrice('budget')->get()` |
| `halalOnly()` | Halal places only | `Place::halalOnly()->get()` |
| `withTags($tags)` | Filter by tags | `Place::withTags(['nasi lemak'])->get()` |
| `byCuisine($type)` | Filter by cuisine | `Place::byCuisine('Chinese')->get()` |
| `minRating($min)` | Min Google rating | `Place::minRating(4.0)->get()` |
| `operational()` | Open businesses only | `Place::operational()->get()` |
| `withServices()` | Service options | `Place::withServices(delivery: true)->get()` |

## 🗂️ Indexes

```
✓ Primary Key: id
✓ Unique: google_place_id
✓ Composite: (area, price)
✓ Single: is_halal, google_rating, business_status
```

## 📈 Performance

- **Cache Strategy**: Redis caching with 1-hour TTL
- **Query Optimization**: Indexes on frequently filtered columns
- **Expected Load Reduction**: ~90% via caching

## 🔄 Migration History

1. **2025_12_17_181313** - Create places table (v1.0)
   - Core fields, location, categorization

2. **2025_12_18_182320** - Add Google Maps fields (v2.0)
   - Google integration, service options, accessibility

## 🎯 Future Enhancements (v3.0)

- User authentication system
- User-generated reviews
- Bookmarks/favorites
- Visit history tracking
- Structured opening hours table
- Photo management system

## 📖 Documentation Files

```
database/
├── README.md                    ← You are here
├── DATABASE_SCHEMA.md           ← Full schema reference
├── ERD.md                       ← Visual diagrams
├── migrations/
│   ├── 2025_12_17_*.php        ← Initial table
│   └── 2025_12_18_*.php        ← Google Maps fields
├── seeders/
│   ├── DatabaseSeeder.php
│   └── PlaceSeeder.php          ← Sample data
└── factories/
    └── PlaceFactory.php         ← Test data generator
```

## 🛠️ Development Commands

```bash
# Database
php artisan migrate              # Run migrations
php artisan db:seed             # Seed database
php artisan migrate:fresh --seed # Reset and seed

# Inspection
php artisan tinker              # REPL for testing
php artisan migrate:status      # Check migration status

# Testing
php artisan test                # Run tests
Place::count()                  # Verify data (in tinker)
```

## 📞 Support

For detailed information, refer to:
- [DATABASE_SCHEMA.md](./DATABASE_SCHEMA.md) - Complete field descriptions
- [ERD.md](./ERD.md) - Visual schema representations
- [CLAUDE.md](../CLAUDE.md) - Project documentation

---

**Version**: 2.0 (Google Maps Integration)
**Last Updated**: 2025-12-19
