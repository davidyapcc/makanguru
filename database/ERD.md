# MakanGuru - Entity Relationship Diagram (ERD)

This document provides a visual representation of the MakanGuru database schema using Mermaid diagrams.

## Current Schema (v2.0)

```mermaid
erDiagram
    PLACES {
        bigint id PK "Primary Key"
        varchar google_place_id UK "Google Place ID (Unique)"
        varchar name "Restaurant Name"
        text description "Personality Description"
        varchar address "Full Address"
        varchar google_maps_url "Google Maps Link"
        decimal google_rating "Rating 0.0-5.0"
        int google_rating_count "Number of Reviews"
        varchar google_price_level "Price Level ($-$$$$)"
        varchar area "Location Area"
        decimal latitude "GPS Latitude"
        decimal longitude "GPS Longitude"
        enum price "budget|moderate|expensive"
        json tags "Searchable Tags Array"
        boolean is_halal "Halal Certification"
        varchar cuisine_type "Cuisine Category"
        varchar opening_hours "Operating Hours"
        varchar phone_number "Contact Number"
        varchar website "Official Website"
        json google_photos "Photo URLs Array"
        json google_reviews "Sample Reviews Array"
        varchar business_status "OPERATIONAL|CLOSED_*"
        boolean wheelchair_accessible "Accessibility"
        boolean takeout_available "Takeout Service"
        boolean delivery_available "Delivery Service"
        boolean dine_in_available "Dine-in Service"
        boolean reservations_accepted "Reservations"
        timestamp created_at "Record Created"
        timestamp updated_at "Record Updated"
    }
```

## Table Relationships (Future)

The following diagram shows planned future relationships:

```mermaid
erDiagram
    PLACES ||--o{ REVIEWS : "has many"
    PLACES ||--o{ PHOTOS : "has many"
    PLACES ||--o{ OPENING_HOURS : "has many"
    PLACES }o--o{ USERS : "bookmarked by"
    PLACES }o--o{ USERS : "visited by"

    PLACES {
        bigint id PK
        varchar google_place_id UK
        varchar name
        text description
        varchar address
        varchar area
        decimal latitude
        decimal longitude
        enum price
        json tags
        boolean is_halal
    }

    REVIEWS {
        bigint id PK
        bigint place_id FK
        bigint user_id FK
        varchar persona "makcik|gymbro|atas"
        int rating "1-5 stars"
        text content
        timestamp created_at
    }

    PHOTOS {
        bigint id PK
        bigint place_id FK
        bigint user_id FK
        varchar url
        varchar source "google|user"
        int width
        int height
        timestamp created_at
    }

    OPENING_HOURS {
        bigint id PK
        bigint place_id FK
        int day_of_week "0=Sun, 6=Sat"
        time open_time
        time close_time
        boolean is_closed
    }

    USERS {
        bigint id PK
        varchar name
        varchar email
        varchar preferred_persona
        timestamp created_at
    }

    BOOKMARKS {
        bigint id PK
        bigint user_id FK
        bigint place_id FK
        timestamp created_at
    }

    VISITS {
        bigint id PK
        bigint user_id FK
        bigint place_id FK
        timestamp visited_at
    }
```

## Indexes Visualization

### Current Indexes

```
Primary Key:
┌────────────┐
│ id (PK)    │
└────────────┘

Unique Key:
┌──────────────────────┐
│ google_place_id (UK) │
└──────────────────────┘

Composite Index:
┌─────────┬─────────┐
│ area    │ price   │
└─────────┴─────────┘

Single Indexes:
┌──────────────────┐
│ is_halal         │
└──────────────────┘

┌──────────────────┐
│ google_place_id  │
└──────────────────┘

┌──────────────────┐
│ google_rating    │
└──────────────────┘

┌──────────────────┐
│ business_status  │
└──────────────────┘
```

## Data Flow Diagram

### Google Maps Integration Flow

```mermaid
flowchart LR
    A[Google Places API] -->|Fetch Data| B[Import Service]
    B -->|Transform| C{Data Validation}
    C -->|Valid| D[Place Model]
    C -->|Invalid| E[Error Log]
    D -->|Upsert| F[(Places Table)]
    F -->|Cache| G[Redis Cache]
    G -->|Serve| H[ChatInterface]
    H -->|Display| I[User Interface]

    style A fill:#4285f4,color:#fff
    style F fill:#00758f,color:#fff
    style G fill:#dc382d,color:#fff
    style I fill:#34a853,color:#fff
```

### User Query Flow

```mermaid
flowchart TD
    A[User Query] -->|Filter| B{Check Cache}
    B -->|Hit| C[Return Cached Data]
    B -->|Miss| D[Query Database]
    D -->|Apply Scopes| E{Filters Applied}
    E -->|Geographic| F[Near Scope]
    E -->|Category| G[Price/Halal/Cuisine Scopes]
    E -->|Tags| H[WithTags Scope]
    E -->|Google Data| I[Rating/Status Scopes]
    F --> J[Execute Query]
    G --> J
    H --> J
    I --> J
    J -->|Results| K[Cache Results]
    K -->|Transform| L[AI Context Injection]
    L -->|Generate| M[AI Response]
    M -->|Display| N[User Interface]

    style A fill:#34a853,color:#fff
    style D fill:#00758f,color:#fff
    style K fill:#dc382d,color:#fff
    style M fill:#fbbc04,color:#000
```

## Schema Evolution

### Version 1.0 (Initial)
- Core place information
- Geographic coordinates
- Basic filtering (price, halal, area)
- Tags system

### Version 2.0 (Current - with Google Maps)
- ✅ Google Place ID integration
- ✅ Google ratings and reviews
- ✅ Business status tracking
- ✅ Service options (takeout, delivery, dine-in)
- ✅ Accessibility features
- ✅ Contact information (phone, website)
- ✅ Photo galleries

### Version 3.0 (Planned)
- 🔲 User authentication system
- 🔲 User-generated reviews
- 🔲 Bookmarks/favorites
- 🔲 Visit history tracking
- 🔲 Structured opening hours
- 🔲 Photo management system

## Field Groups Visualization

```
┌─────────────────────────────────────────────────────┐
│                    PLACES TABLE                      │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │  CORE IDENTITY                              │    │
│  │  • id, name, description, address          │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │  LOCATION                                   │    │
│  │  • area, latitude, longitude               │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │  CATEGORIZATION                             │    │
│  │  • price, tags, is_halal, cuisine_type     │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │  GOOGLE INTEGRATION                         │    │
│  │  • google_place_id, google_rating          │    │
│  │  • google_maps_url, google_photos          │    │
│  │  • google_reviews, business_status         │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │  CONTACT & HOURS                            │    │
│  │  • opening_hours, phone_number, website    │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │  SERVICE OPTIONS                            │    │
│  │  • takeout_available, delivery_available   │    │
│  │  • dine_in_available, reservations         │    │
│  │  • wheelchair_accessible                   │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │  METADATA                                   │    │
│  │  • created_at, updated_at                  │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
└─────────────────────────────────────────────────────┘
```

## Query Complexity Analysis

### Simple Queries (Fast - Uses Single Index)
```sql
-- Price filter
WHERE price = 'budget'

-- Halal filter
WHERE is_halal = TRUE

-- Rating filter
WHERE google_rating >= 4.0

-- Business status
WHERE business_status = 'OPERATIONAL'
```

### Medium Queries (Moderate - Uses Composite Index)
```sql
-- Area + Price
WHERE area LIKE '%Bangsar%' AND price = 'budget'
```

### Complex Queries (Slower - Multiple Scopes)
```sql
-- Geospatial + Multiple Filters
WHERE (6371 * acos(...)) <= 5  -- Haversine
  AND is_halal = TRUE
  AND price = 'budget'
  AND google_rating >= 4.0
  AND business_status = 'OPERATIONAL'
```

### JSON Queries (Slowest - Full Scan Required)
```sql
-- Tag search
WHERE JSON_CONTAINS(tags, '"nasi lemak"')
```

**Optimization**: Redis caching reduces 90% of repeated queries

---

## Database Statistics

| Metric | Current | Target (Production) |
|--------|---------|---------------------|
| Total Tables | 1 | 6 (with user system) |
| Total Columns | 30 | ~80 |
| Indexes | 7 | ~15 |
| Average Row Size | ~2 KB | ~3 KB |
| Expected Records | 50-100 | 5,000-10,000 |
| Storage Required | < 1 MB | ~30 MB |
| Query Response Time | < 100ms | < 50ms (with Redis) |

---

**Last Updated**: 2025-12-19
**Schema Version**: 2.0 (Google Maps Integration)
**ERD Tool**: Mermaid.js
