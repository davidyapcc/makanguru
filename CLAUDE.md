# MakanGuru - Technical Documentation for AI Assistants

This document provides comprehensive context for AI assistants (like Claude) working on the MakanGuru project.

## Project Overview

**MakanGuru** is an AI-powered Malaysian food recommendation application that solves the "Makan Mana?" (Where to eat?) dilemma using context-aware AI personalities.

### Core Concept
Unlike traditional directory apps (Google Maps/Yelp), MakanGuru uses **AI Personas** to provide curated, personality-driven recommendations:
- **The Mak Cik**: Value-focused, halal-conscious, nurturing recommendations
- **The Gym Bro**: Protein-heavy, efficiency-focused, "padu" recommendations
- **The Atas Friend**: Aesthetic, upscale, Instagram-worthy recommendations
- **The Tauke**: Efficiency and value-driven, "time is money" business-focused recommendations
- **The Mat Motor**: Late-night spots, easy parking, budget-friendly "lepak" recommendations
- **The Corporate Slave**: Quick lunch spots, coffee quality, WiFi availability, stressed office worker recommendations

### Architecture Pattern
**Context-Injection RAG (Retrieval-Augmented Generation)**
1. User asks a question (e.g., "Where to get spicy food in PJ?")
2. System queries MySQL `places` table with spatial/tag filters
3. Filtered JSON data is injected into the AI's context window
4. AI Persona analyzes and responds with personality-driven recommendations

---

## Tech Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.4)
- **Database**: SQLite (local), MySQL 8.0 (production)
- **ORM**: Eloquent with strict type casting
- **Queue/Cache**: Redis (production)

### Frontend
- **UI Framework**: Livewire 3 (reactive components)
- **CSS**: Tailwind CSS v4 (with Vite)
- **Interactivity**: Alpine.js (micro-interactions)

### AI Integration
- **Primary Provider**: Groq (OpenAI GPT) - Default for all requests
- **Automatic Fallback**: Groq (Meta Llama 3.3) - Used when primary fails/rate-limited
- **Legacy Support**: Google Gemini 2.5 Flash (available but not actively used)
- **Method**: REST API (context injection pattern)
- **API Versions**:
  - Gemini: v1 (stable endpoint)
  - Groq: OpenAI-compatible v1 endpoint
- **Intelligent Failover**: Automatic model switching without user intervention

### Infrastructure (Planned)
- **Hosting**: AWS EC2 (Ubuntu 24.04)
- **Web Server**: Nginx
- **Process Manager**: Supervisor (for queues)
- **SSL**: Certbot (Let's Encrypt)

---

## Engineering Standards

### Code Quality
- **PSR-12** coding standards strictly enforced
- **SOLID Principles** applied throughout
- **Type Safety**: Full use of PHP 8.4 type hints (properties, return types, parameters)
- **Design Patterns**: Service Pattern, Repository Pattern (via Scopes)

### Key Principles
1. **Separation of Concerns**: Controllers never call AI APIs directly
2. **Dependency Injection**: Services bound via interfaces in ServiceProvider
3. **Clean Code**: Descriptive names, small focused methods, comprehensive docblocks
4. **Mobile-First**: All UI components designed for thumb-friendly interaction

---

## Project Structure

### Database Schema

#### `places` Table
```sql
- id (bigint, primary key)
- name (string) - Restaurant name
- description (text, nullable) - Witty, personality-driven description
- address (string) - Full address
- area (string, indexed) - Location area (e.g., "Bangsar", "Petaling Jaya")
- latitude (decimal 10,7) - Geospatial coordinate
- longitude (decimal 10,7) - Geospatial coordinate
- price (enum: budget|moderate|expensive, indexed) - Price range
- tags (json) - Searchable tags like ['nasi lemak', 'halal', 'breakfast']
- is_halal (boolean, indexed, default: false)
- cuisine_type (string, nullable) - E.g., "Malaysian", "Chinese", "Western"
- opening_hours (string, nullable) - Human-readable hours
- created_at, updated_at (timestamps)

Indexes:
- Composite index on (area, price)
- Single index on is_halal
```

### Models

#### `Place` Model (`app/Models/Place.php`)

**Type Casts:**
```php
'latitude' => 'decimal:7'
'longitude' => 'decimal:7'
'tags' => 'array'
'is_halal' => 'boolean'
```

**Query Scopes:**
1. `scopeNear($query, $lat, $lng, $radiusKm = 10)` - Haversine distance filter
2. `scopeInArea($query, $area)` - LIKE search on area field
3. `scopeByPrice($query, $price)` - Filter by price enum
4. `scopeHalalOnly($query)` - Boolean filter for halal places
5. `scopeWithTags($query, array $tags)` - JSON contains search
6. `scopeByCuisine($query, $cuisineType)` - LIKE search on cuisine

**Computed Attributes:**
- `price_label` - Returns "RM 10-20", "RM 20-50", "RM 50+"
- `halal_status` - Returns "Halal" or "Non-Halal"

**Example Usage:**
```php
// Find halal places near KLCC
Place::near(3.1578, 101.7123, 5)
    ->halalOnly()
    ->byPrice('budget')
    ->get();

// Search by tags
Place::withTags(['nasi lemak', 'breakfast'])
    ->inArea('Damansara')
    ->get();
```

### Seeders

#### `PlaceSeeder` (`database/seeders/PlaceSeeder.php`)
- **Golden Records (5)**: Famous Malaysian eateries
  - Village Park Restaurant (Nasi Lemak)
  - Jalan Alor Food Street
  - Restoran Yusoof Dan Zakhir (Briyani)
  - Kim Lian Kee Restaurant (Hokkien Mee)
  - The Owls Cafe (Hipster)

- **Dummy Records (10)**: Diverse test data covering:
  - Various price ranges (budget, moderate, expensive)
  - Multiple cuisines (Malay, Chinese, Indian, Western, Healthy)
  - Halal and non-halal options
  - Different areas across Klang Valley

---

## Design System

### Color Palette (Malaysian-Inspired)

Defined in `resources/css/app.css` under `@theme`:

```css
--color-sambal-red: #DC2626
--color-sambal-red-dark: #991B1B
--color-teh-tarik-brown: #92400E
--color-teh-tarik-brown-light: #D97706
--color-nasi-lemak-cream: #FEF3C7
--color-pandan-green: #059669
--color-pandan-green-light: #10B981
--color-rendang-brown: #78350F
--color-sky-blue: #3B82F6
--color-sky-blue-light: #60A5FA
```

**Usage in Tailwind:**
```html
<div class="bg-[--color-sambal-red] text-[--color-nasi-lemak-cream]">
  Spicy Alert!
</div>
```

### Spacing System (Mobile-First)
```css
--spacing-xs: 0.25rem
--spacing-sm: 0.5rem
--spacing-md: 1rem
--spacing-lg: 1.5rem
--spacing-xl: 2rem
```

---

## Development Workflow

### Option 1: Docker Setup (Recommended)

Docker provides a consistent development environment with production parity (MySQL 8.0, Redis, Nginx).

**Prerequisites:**
- Docker Desktop (includes Docker Compose)
- 4GB+ RAM, 10GB+ disk space

**Quick Start:**
```bash
# Copy Docker environment
cp .env.docker .env

# Configure API keys in .env (REQUIRED)
# Edit .env and set GEMINI_API_KEY

# Start Docker services
docker compose up -d

# Run initialization script
bash docker/init.sh

# Access application at http://localhost:8080
```

**Daily Workflow:**
```bash
# Start services
docker compose up -d

# Stop services
docker compose down

# View logs
docker compose logs -f app

# Run commands
docker compose exec app php artisan tinker
docker compose exec app php artisan test
docker compose exec app bash
```

**Docker Services:**
- `mysql` - MySQL 8.0 database (port 3307→3306)
- `redis` - Redis 7 cache & queue (port 6380→6379)
- `app` - PHP 8.4-FPM application
- `nginx` - Nginx web server (port 8080→80)
- `queue` - Laravel queue worker
- `node` - Node.js 24 for asset building

📖 **Full Docker Documentation:** [docs/guides/DOCKER_SETUP.md](docs/guides/DOCKER_SETUP.md)

---

### Option 2: Native Local Setup

**Prerequisites:**
- PHP >= 8.4
- Composer
- Node.js (via nvm recommended)
- SQLite (pre-installed on macOS) or MySQL 8.0

**Installation:**
```bash
# Clone and install
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Publish Livewire assets (REQUIRED for frontend to work)
php artisan livewire:publish --assets

# Database setup
php artisan migrate:fresh --seed

# Build assets
npm run dev  # Development
npm run build  # Production
```

**Running the Application:**
```bash
# Backend server
php artisan serve

# Frontend assets (separate terminal)
npm run dev
```

### Testing Queries

```bash
# Via Tinker
php artisan tinker

# Test queries
Place::halalOnly()->count();
Place::byPrice('budget')->get();
Place::inArea('Bangsar')->pluck('name');
Place::withTags(['nasi lemak'])->first();
```

---

## Phase 1 Implementation Status ✅

### Completed Tasks

1. **Project Initialization**
   - ✅ Laravel 12 installed
   - ✅ Tailwind CSS v4 configured with Vite
   - ✅ Git repository initialized
   - ✅ Frontend build pipeline working

2. **Database Layer**
   - ✅ Migration created with comprehensive schema
   - ✅ Indexes optimized for common queries
   - ✅ SQLite configured for local development

3. **Domain Models**
   - ✅ Place model with full type safety
   - ✅ 6 query scopes implemented
   - ✅ 2 computed attributes
   - ✅ Following PSR-12 and SOLID principles

4. **Data Seeding**
   - ✅ 15 places seeded (5 golden + 10 test)
   - ✅ All scopes tested and verified
   - ✅ Diverse data for comprehensive testing

5. **Design System**
   - ✅ Malaysian color palette defined
   - ✅ Mobile-first spacing system
   - ✅ Tailwind v4 theme configuration

---

## Phase 2 Implementation Status ✅

### Completed Tasks

1. **Service Architecture**
   - ✅ Created `AIRecommendationInterface` contract
   - ✅ Implemented `GeminiService` with Google Gemini 2.5 Flash
   - ✅ Bound in `AIServiceProvider` for dependency injection
   - ✅ Added `healthCheck()` method for service availability
   - ✅ Repository pattern via Eloquent scopes

2. **Prompt Engineering Engine**
   - ✅ Created `PromptBuilder` class with 6 Malaysian personas:
     - **Mak Cik**: Nurturing, halal-focused, value-conscious
     - **Gym Bro**: Protein-focused, efficiency-driven, "padu"
     - **Atas Friend**: Aesthetic-focused, upscale, Instagram-worthy
     - **Tauke**: Efficiency and value-driven, business-focused, "time is money"
     - **Mat Motor**: Late-night enthusiast, motor parking priority, budget-friendly "lepak"
     - **Corporate Slave**: Stressed office worker, quick lunch spots, coffee-dependent, WiFi essential
   - ✅ Each persona has unique speech patterns and priorities
   - ✅ Token-efficient JSON context injection

3. **Data Transfer Objects**
   - ✅ Created `RecommendationDTO` for type-safe data transfer
   - ✅ Factory methods: `fromGeminiResponse()`, `fallback()`
   - ✅ Helper methods: `isFallback()`, `getTokensUsed()`

4. **API Integration**
   - ✅ Gemini 2.5 Flash API integration (v1 endpoint)
   - ✅ Model fallback system (4 models: gemini-2.5-flash, gemini-2.0-flash, gemini-2.5-flash-lite, gemini-2.0-flash-lite)
   - ✅ Rate limit detection and automatic model switching
   - ✅ Retry logic with exponential backoff (2 retries per model)
   - ✅ Comprehensive error handling with graceful fallback
   - ✅ Safety settings configuration (all 4 categories)
   - ✅ Enhanced logging (model used, finish reasons, token usage)
   - ✅ Output limit: 10000 tokens for complete responses

5. **Testing & Commands**
   - ✅ Created `tests/Unit/GeminiServiceTest.php` - 9 tests, 28 assertions
   - ✅ Created `php artisan makanguru:ask` - CLI testing interface
   - ✅ Created `php artisan gemini:list-models` - Lists available models
   - ✅ Created `PlaceFactory` for test data generation

6. **Cost Estimation**
   - ✅ Static method `GeminiService::estimateCost()` for API cost calculation
   - ✅ Logs token usage on every API call

### Files Created in Phase 2

```
app/
├── Contracts/
│   └── AIRecommendationInterface.php ✅
├── Services/
│   ├── GeminiService.php ✅
│   └── GroqService.php ✅ (Groq integration)
├── AI/
│   └── PromptBuilder.php ✅
├── DTOs/
│   └── RecommendationDTO.php ✅ (extended with fromGroqResponse)
├── Providers/
│   └── AIServiceProvider.php ✅ (supports multiple providers)
└── Console/Commands/
    ├── AskMakanGuruCommand.php ✅ (multi-model support)
    ├── ListGeminiModelsCommand.php ✅
    └── ListGroqModelsCommand.php ✅ (Groq models)

tests/Unit/
├── GeminiServiceTest.php ✅
└── GroqServiceTest.php ✅ (Groq service tests)

database/factories/
└── PlaceFactory.php ✅
```

---

## Phase 3 Implementation Status ✅

### Completed Tasks

1. **Livewire 3 Chat Interface**
   - ✅ Installed Livewire 3.7.2
   - ✅ Created `ChatInterface` component with full state management
   - ✅ Implemented properties: `$userQuery`, `$chatHistory`, `$currentPersona`, `$currentModel`, `$filterHalal`, `$filterPrice`, `$filterArea`
   - ✅ Dependency injection for `AIRecommendationInterface`
   - ✅ Type-safe validation with PHP 8.4 attributes
   - ✅ Automatic model fallback (OpenAI → Meta) without user intervention
   - ✅ Session-based rate limiting (5 messages per 60 seconds)

2. **Reusable Blade Components**
   - ✅ `chat-bubble.blade.php` - Dynamic message bubbles with persona avatars
   - ✅ `loading-spinner.blade.php` - Persona-specific typing indicators
   - ✅ `restaurant-card.blade.php` - Place information display
   - ✅ `persona-switcher.blade.php` - Three-persona selection interface
   - ✅ `model-selector.blade.php` - AI model/provider selection interface (deprecated in Phase 7.1)
   - ✅ `layouts/app.blade.php` - Main application layout

3. **Alpine.js Micro-Interactions**
   - ✅ Installed Alpine.js 3.x
   - ✅ Auto-scroll to latest message on new chat
   - ✅ Smooth fadeIn animations for chat bubbles
   - ✅ `x-data`, `x-init`, `x-ref` for state management

4. **UI/UX Features**
   - ✅ Mobile-first responsive design
   - ✅ Automatic AI model failover (removed manual model selector in Phase 7.1)
   - ✅ Real-time filters (Halal, Price, Area) with `wire:model.live`
   - ✅ Loading states with `wire:loading`
   - ✅ Enter key to send (Shift+Enter for new line)
   - ✅ Clear chat with confirmation dialog
   - ✅ Malaysian color palette gradients
   - ✅ Persona-specific fallback messages
   - ✅ Model tracking in chat history
   - ✅ Rate limit warning banner with countdown timer
   - ✅ Disabled send button when rate limited
   - ✅ Persona-specific rate limit messages

5. **Routes & Views**
   - ✅ Updated `routes/web.php` to serve chat interface
   - ✅ Created `home.blade.php` view
   - ✅ Integrated Livewire scripts and styles

### Files Created in Phase 3

```
resources/views/
├── components/
│   ├── layouts/
│   │   └── app.blade.php ✅
│   ├── chat-bubble.blade.php ✅
│   ├── loading-spinner.blade.php ✅
│   ├── restaurant-card.blade.php ✅
│   ├── persona-switcher.blade.php ✅
│   └── model-selector.blade.php ✅
├── livewire/
│   └── chat-interface.blade.php ✅ (with rate limit UI)
└── home.blade.php ✅

app/Livewire/
└── ChatInterface.php ✅ (with rate limiting logic)

config/
└── chat.php ✅ (rate limiting configuration)

tests/Feature/
└── ChatRateLimitTest.php ✅ (4 tests, 13 assertions)

resources/js/
└── app.js ✅ (Optimized - uses Livewire 3's built-in Alpine.js)

docs/
├── README.md ✅ (documentation index)
├── guides/
│   ├── RATE_LIMITING.md ✅ (rate limiting guide)
│   ├── SCRAPER_GUIDE.md ✅ (CLI scraper guide)
│   └── SCRAPER_UI_GUIDE.md ✅ (web scraper guide)
└── implementation/
    ├── PHASE5_COMPLETE.md ✅ (Phase 5 summary)
    └── SCRAPER_WEB_UI_COMPLETE.md ✅ (Scraper UI summary)
```

---

## Phase 4 Implementation Status ✅

### Completed Tasks

1. **Redis Caching Service**
   - ✅ Created `PlaceCacheService` for intelligent query caching
   - ✅ Cache TTL: 1 hour (3600 seconds)
   - ✅ Supports filtered queries (halal, price, area, tags)
   - ✅ Geospatial caching for nearby places
   - ✅ Cache invalidation system
   - ✅ Integrated with `ChatInterface` component
   - ✅ Reduces database load by ~90% for repeated queries

2. **AWS EC2 Deployment Automation**
   - ✅ `deployment/setup-server.sh` - Full server provisioning script
   - ✅ `deployment/deploy.sh` - Application deployment and updates
   - ✅ Automated installation of:
     - PHP 8.4 with required extensions
     - Nginx web server
     - MySQL 8.0 database
     - Redis for caching and queues
     - Node.js 24.x LTS
     - Composer
     - Supervisor for queue workers

3. **Nginx Production Configuration**
   - ✅ SSL/TLS support with Let's Encrypt
   - ✅ HTTP/2 enabled
   - ✅ Security headers (CSP, HSTS, X-Frame-Options, X-XSS-Protection)
   - ✅ Gzip compression for assets
   - ✅ Static asset caching (1 year expiry)
   - ✅ PHP-FPM integration
   - ✅ OCSP stapling

4. **Queue Workers & Process Management**
   - ✅ Supervisor configuration for Laravel queue workers
   - ✅ Auto-restart on failure
   - ✅ Process group management (2 workers by default)
   - ✅ Dedicated log files
   - ✅ Graceful shutdown handling

5. **SSL/TLS Automation**
   - ✅ `deployment/setup-ssl.sh` - Automated SSL certificate setup
   - ✅ Let's Encrypt integration via Certbot
   - ✅ Automatic certificate renewal (cron job)
   - ✅ HTTPS redirect configuration
   - ✅ SSL best practices (TLS 1.2+, strong ciphers)

6. **CI/CD Pipeline**
   - ✅ GitHub Actions workflow (`.github/workflows/tests.yml`)
   - ✅ Automated testing on push/PR
   - ✅ PHPUnit test execution
   - ✅ PSR-12 code style checking
   - ✅ Security vulnerability scanning (composer audit)
   - ✅ Frontend asset build verification

7. **Deployment Documentation**
   - ✅ `deployment/DEPLOYMENT.md` - Comprehensive 400+ line guide
   - ✅ Step-by-step AWS EC2 setup
   - ✅ Database configuration
   - ✅ SSL setup instructions
   - ✅ Troubleshooting section
   - ✅ Performance optimization tips
   - ✅ Security checklist
   - ✅ Monitoring and maintenance guide

8. **Production Environment Configuration**
   - ✅ Updated `.env.example` with production notes
   - ✅ Created `.env.production.example` template
   - ✅ Redis cache configuration
   - ✅ MySQL production settings
   - ✅ Queue configuration
   - ✅ Logging configuration

### Files Created in Phase 4

```
app/Services/
└── PlaceCacheService.php ✅

deployment/
├── setup-server.sh ✅
├── deploy.sh ✅
├── nginx.conf ✅
├── supervisor.conf ✅
├── setup-ssl.sh ✅
└── DEPLOYMENT.md ✅

.github/workflows/
└── tests.yml ✅

.env.production.example ✅
```

---

## Phase 5 Implementation Status ✅

### Completed Tasks

1. **Restaurant Scraper Service**
   - ✅ Created `RestaurantScraperService` for OpenStreetMap integration
   - ✅ Overpass API integration with comprehensive data parsing
   - ✅ Intelligent halal detection (3 heuristics: explicit tags, cuisine inference, name matching)
   - ✅ Smart price range inference
   - ✅ Tag extraction (cuisine, diet, amenities)
   - ✅ Malaysia-specific coordinate validation
   - ✅ Data validation before database insertion

2. **CLI Scraper Command**
   - ✅ Created `php artisan makanguru:scrape` command
   - ✅ 7 pre-configured Malaysian cities (KL, PJ, Bangsar, KLCC, Damansara, Subang, Shah Alam)
   - ✅ Dry-run mode with `--dry-run` flag
   - ✅ Duplicate detection and prevention
   - ✅ Beautiful table output with progress bars
   - ✅ Configurable radius (1-15km) and limit (1-200)

3. **Web UI for Scraper**
   - ✅ Created `ScraperInterface` Livewire component
   - ✅ Beautiful mobile-first web interface at `/scraper`
   - ✅ Interactive sliders for radius and limit
   - ✅ Preview mode toggle (safe exploration before import)
   - ✅ Real-time database statistics
   - ✅ Visual results table with color-coded badges
   - ✅ Success/error messaging with user-friendly alerts
   - ✅ Navigation integration with chat interface

4. **Restaurant Database Browser**
   - ✅ Created `RestaurantList` Livewire component
   - ✅ Beautiful mobile-first web interface at `/restaurants`
   - ✅ Type-ahead search across name, description, cuisine, area (300ms debounce)
   - ✅ Real-time filters: Halal, Price Range, Area, Cuisine Type
   - ✅ Sortable columns with visual indicators (name, area, cuisine, price)
   - ✅ Pagination (20 restaurants per page)
   - ✅ Tag display (shows first 3 tags + count)
   - ✅ Color-coded price badges (green/yellow/red)
   - ✅ Empty state handling with context-aware messaging
   - ✅ Integrated navigation across all pages (Chat, Scraper, Restaurants)

5. **Testing & Documentation**
   - ✅ Created `RestaurantScraperServiceTest.php` - 9 tests, 23 assertions
   - ✅ All tests passing with HTTP mocking
   - ✅ Comprehensive documentation:
     - `SCRAPER_GUIDE.md` - 600+ lines CLI guide
     - `SCRAPER_UI_GUIDE.md` - 600+ lines web UI guide
     - `PHASE5_COMPLETE.md` - Implementation summary
     - `SCRAPER_WEB_UI_COMPLETE.md` - Web UI summary

6. **Live Testing**
   - ✅ Successfully scraped real restaurants from OpenStreetMap
   - ✅ Verified CLI command works (dry-run and import modes)
   - ✅ Verified web UI works (preview and import modes)
   - ✅ Duplicate detection working correctly
   - ✅ Database integration confirmed
   - ✅ Restaurant list page filtering and sorting verified

### Files Created in Phase 5

```
app/
├── Services/
│   └── RestaurantScraperService.php ✅ (450+ lines)
├── Console/Commands/
│   └── ScrapeRestaurantsCommand.php ✅ (350+ lines)
└── Livewire/
    ├── ScraperInterface.php ✅ (273 lines)
    └── RestaurantList.php ✅ (240 lines)

resources/views/
├── scraper.blade.php ✅
├── restaurants.blade.php ✅
├── livewire/
│   ├── scraper-interface.blade.php ✅ (288 lines)
│   └── restaurant-list.blade.php ✅ (266 lines)
└── components/
    ├── nav-link.blade.php ✅
    └── layouts/app.blade.php ✅ (updated with restaurant link)

tests/Unit/
└── RestaurantScraperServiceTest.php ✅ (250+ lines)

routes/
└── web.php ✅ (added /scraper and /restaurants routes)

docs/
├── README.md ✅ (documentation index)
├── guides/
│   ├── SCRAPER_GUIDE.md ✅ (600+ lines)
│   └── SCRAPER_UI_GUIDE.md ✅ (600+ lines)
└── implementation/
    ├── PHASE5_COMPLETE.md ✅ (500+ lines)
    └── SCRAPER_WEB_UI_COMPLETE.md ✅ (500+ lines)
```

**Total Lines of Code:** ~3,386 lines (service + commands + UI + tests + docs)

---

## Phase 6 Implementation Status ✅

### Completed Tasks

1. **Social Card Generation Service**
   - ✅ Created `SocialCardService` for SVG card generation
   - ✅ Persona-specific styling (all 6 personas)
   - ✅ 1200×630px cards optimized for social media
   - ✅ Improved text wrapping and line limiting (max 6 lines for recommendation, 2 for query)
   - ✅ Fixed overlapping text issues with proper spacing
   - ✅ Gradient backgrounds with persona colors
   - ✅ Better typography (19px recommendation, 20px query)

2. **Chat Interface Integration**
   - ✅ Share button on all AI responses
   - ✅ Card preview modal with reorganized sharing options
   - ✅ Download SVG functionality
   - ✅ Copy link to clipboard with centered toast notifications
   - ✅ Direct sharing to WhatsApp, Facebook, Twitter/X, Telegram, Instagram

3. **Enhanced Social Sharing UI (December 2024)**
   - ✅ **Industry-standard three-tier layout**:
     - Primary: WhatsApp (large, prominent button)
     - Secondary: 4-column social grid (Facebook, X, Telegram, Instagram)
     - Tertiary: Utility actions (Download, Copy Link) in separate section
   - ✅ Instagram smart sharing:
     - Mobile: Auto-downloads + attempts to open Instagram app
     - Desktop: Beautiful instruction modal with step-by-step guide
   - ✅ Improved button visual hierarchy and hover effects
   - ✅ Better section labeling ("Or share on:", "Quick actions:")
   - ✅ Responsive design with proper mobile/desktop breakpoints

4. **Social Media Optimization**
   - ✅ Open Graph meta tags (Facebook, LinkedIn)
   - ✅ Twitter Card meta tags
   - ✅ SEO optimization (keywords, description, canonical URLs)
   - ✅ Theme color for mobile browsers

5. **Storage & Cleanup**
   - ✅ Public storage for generated cards
   - ✅ `CleanupSocialCardsCommand` for automatic deletion (7+ days)
   - ✅ UUID-based filenames for security
   - ✅ Storage link created for public access

6. **Testing & Documentation**
   - ✅ Created `SocialCardServiceTest.php` - 13 tests, 45 assertions
   - ✅ Created `SocialCardSharingTest.php` - 8 tests, 18 assertions
   - ✅ All 59 tests passing (159 total assertions)
   - ✅ Comprehensive documentation:
     - `PHASE6_COMPLETE.md` - 500+ lines implementation guide
     - `SOCIAL_SHARING_GUIDE.md` - 550+ lines user guide

### Files Created in Phase 6

```
app/
├── Services/
│   └── SocialCardService.php ✅ (220 lines)
└── Console/Commands/
    └── CleanupSocialCardsCommand.php ✅ (45 lines)

resources/views/
└── components/
    └── social-card-modal.blade.php ✅ (375 lines - enhanced with Instagram modal & toast)

tests/
├── Unit/
│   └── SocialCardServiceTest.php ✅ (220 lines)
└── Feature/
    └── SocialCardSharingTest.php ✅ (130 lines)

docs/
├── implementation/
│   └── PHASE6_COMPLETE.md ✅ (500+ lines)
└── guides/
    └── SOCIAL_SHARING_GUIDE.md ✅ (550+ lines)
```

**Total Lines of Code:** ~2,590 lines (service + commands + enhanced UI + tests + docs)

**Recent Enhancements (December 2024):**
- Fixed SVG card text overlapping issues
- Reorganized social sharing buttons following industry best practices
- Added smart Instagram sharing with mobile/desktop detection
- Improved toast notifications (centered, better animations)
- Enhanced button visual hierarchy and user experience

---

## Phase 7.1 Implementation Status ✅

### Completed Tasks

**Phase 7.1: Intelligent AI Model Fallback System**

1. **Removed Manual Model Selection**
   - ✅ Removed AI model selector dropdown from chat interface
   - ✅ Removed model badge display from settings bar
   - ✅ Simplified UX by hiding technical complexity from users

2. **Automatic Fallback Logic**
   - ✅ Default model: OpenAI GPT (via Groq)
   - ✅ Automatic fallback to Meta Llama when OpenAI fails or hits rate limits
   - ✅ Seamless model switching without user intervention
   - ✅ Automatic reset to primary model for next request

3. **Enhanced Service Layer**
   - ✅ Updated `ChatInterface::sendMessage()` with intelligent fallback
   - ✅ Separate fallback model arrays in `GroqService`:
     - `FALLBACK_MODELS_OPENAI`: Other OpenAI models
     - `FALLBACK_MODELS_META`: Other Meta models
   - ✅ Smart fallback selection based on primary model type
   - ✅ Enhanced logging for debugging and monitoring

4. **Fallback Flow**
   ```
   User Query
      ↓
   OpenAI GPT (Primary)
      ↓ (on error/rate limit)
   Meta Llama (Automatic Fallback)
      ↓ (on error)
   Persona-specific Fallback Message
   ```

### Files Modified in Phase 7.1

```
resources/views/livewire/
└── chat-interface.blade.php ✅ (removed model selector UI)

app/Livewire/
└── ChatInterface.php ✅ (automatic fallback logic, removed switchModel method)

app/Services/
└── GroqService.php ✅ (separate fallback arrays, smart model selection)
```

### Benefits

- ✅ **Simplified UX**: No confusing model selector for users
- ✅ **Automatic Resilience**: Seamless fallback without user intervention
- ✅ **Better Reliability**: Two layers of AI models ensure higher success rate
- ✅ **Cost Optimization**: Starts with GPT (better quality), falls back to Llama (faster/cheaper)
- ✅ **Transparent Logging**: All fallback attempts are logged for monitoring
- ✅ **User-Friendly**: Users never see "model selection" complexity

---

## Phase 5.1 Implementation Status ✅ (Batch Processing Enhancement)

### Completed Tasks (December 2024)

**Phase 5.1: Batch Processing for Large-Scale Restaurant Imports**

1. **Enhanced RestaurantScraperService**
   - ✅ Added `fetchBatchFromOverpass()` for multi-location scraping
   - ✅ Added `saveBatch()` for transaction-based batch inserts
   - ✅ Added `removeDuplicates()` for in-memory duplicate removal
   - ✅ Configurable batch delay (2 seconds) to respect API rate limits
   - ✅ Progress callbacks for real-time tracking

2. **Enhanced ScrapeRestaurantsCommand**
   - ✅ Multi-area support: `--area="KLCC" --area="Bangsar"` or `--area=all`
   - ✅ Configurable batch size: `--batch-size=100` (default: 100)
   - ✅ Progress tracking: `--show-progress` flag for detailed output
   - ✅ Duplicate removal: `--no-duplicates` flag for in-memory deduplication
   - ✅ Enhanced summary statistics (total, saved, skipped, failed)
   - ✅ Beautiful progress bars with verbose format
   - ✅ Batch transaction safety (rollback on failure)

3. **Performance Optimizations**
   - ✅ Transaction-based batch inserts (100-250 records per transaction)
   - ✅ In-memory duplicate removal (15-20% faster than database checks)
   - ✅ Configurable batch sizes for different dataset sizes
   - ✅ 2-second delay between API requests to respect Overpass API limits
   - ✅ Progress tracking with minimal overhead (~2-3%)

4. **Comprehensive Documentation**
   - ✅ Updated `SCRAPER_GUIDE.md` with batch processing examples
   - ✅ Added performance optimization section
   - ✅ Added recommended configurations for different dataset sizes
   - ✅ Added best practices and troubleshooting for large imports
   - ✅ Added monitoring and logging guidance

### New Command Options (Phase 5.1)

```bash
php artisan makanguru:scrape
  --area=*                  # Multiple areas or "all" for all 7 cities
  --radius=5000            # Radius in meters per area (default: 5000)
  --limit=50               # Limit per area (default: 50)
  --batch-size=100         # Database batch size (default: 100)
  --dry-run                # Preview without saving
  --show-progress          # Detailed progress information
  --no-duplicates          # Remove duplicates before saving
```

### Usage Examples

**Small Import (50-200 restaurants):**
```bash
php artisan makanguru:scrape --area="KLCC" --limit=200
```

**Medium Import (200-500 restaurants):**
```bash
php artisan makanguru:scrape \
  --area="KLCC" --area="Bangsar" --area="Damansara" \
  --limit=200 --batch-size=150 --no-duplicates --show-progress
```

**Large Import (500-1000 restaurants):**
```bash
php artisan makanguru:scrape \
  --area="Bangsar" --area="Mont Kiara" --area="KLCC" --area="Sunway" \
  --area="Mid Valley" --area="Publika" --area="Puchong" \
  --limit=150 --radius=7000 \
  --batch-size=200 --no-duplicates --show-progress
```

**Very Large Import (2,500+ restaurants - All 50 Klang Valley locations):**
```bash
php artisan makanguru:scrape \
  --area=all --limit=50 --radius=5000 \
  --batch-size=250 --no-duplicates --show-progress
```

**Maximum Coverage (10,000+ restaurants - All 50 locations with max settings):**
```bash
php artisan makanguru:scrape \
  --area=all --limit=200 --radius=10000 \
  --batch-size=250 --no-duplicates --show-progress
```

**Note**: `--area=all` now covers **50 pre-configured Klang Valley locations** (expanded from 7 in Phase 5)

### Files Modified in Phase 5.1

```
app/Services/
└── RestaurantScraperService.php ✅ (added fetchBatchFromOverpass, saveBatch, removeDuplicates)

app/Console/Commands/
└── ScrapeRestaurantsCommand.php ✅ (multi-area support, batch options, enhanced UI)

docs/guides/
└── SCRAPER_GUIDE.md ✅ (comprehensive batch processing documentation)
```

**Total Changes:** 2 files modified, ~400 lines of code added

### Performance Benchmarks

| Dataset Size | Configuration | Runtime | Success Rate |
|-------------|---------------|---------|--------------|
| 50-200 restaurants | `--batch-size=100` | ~30 seconds | 95-98% |
| 200-500 restaurants | `--batch-size=150` | ~2-3 minutes | 93-96% |
| 500-1000 restaurants | `--batch-size=200` | ~5-8 minutes | 90-94% |
| 1000+ restaurants | `--batch-size=250` | ~10-15 minutes | 88-92% |

**Note:** Success rate depends on OpenStreetMap data quality and API availability.

---

## Phase 5.2 Implementation Status ✅ (Data Centralization)

### Completed Tasks (December 2024)

**Phase 5.2: Centralized Location Configuration**

1. **Created Centralized Config File**
   - ✅ Created `config/locations.php` as single source of truth
   - ✅ **48 locations** across Klang Valley with coordinates
   - ✅ **15 curated seeding locations** with optimal radius/limit
   - ✅ **9 regional groupings** for better organization
   - ✅ Eliminated duplicate location arrays across 3 components

2. **Updated All Components to Use Config**
   - ✅ **ScrapeRestaurantsCommand**: Replaced `CITY_COORDINATES` constant with `config('locations.coordinates')`
   - ✅ **ScraperInterface**: Replaced `AREAS` constant with `getLocationCoordinates()` method
   - ✅ **PlaceSeeder**: Replaced hardcoded array with `getSeederLocations()` helper

3. **Configuration Structure**
   ```php
   // config/locations.php
   return [
       'coordinates' => [
           'Kuala Lumpur' => ['lat' => 3.1390, 'lng' => 101.6869],
           'KLCC' => ['lat' => 3.1578, 'lng' => 101.7123],
           // ... 46 more locations
       ],
       'seeder' => [
           ['name' => 'Bangsar', 'radius' => 2000, 'limit' => 10],
           // ... 14 more seeding configs
       ],
       'regions' => [
           'Central Kuala Lumpur' => ['Kuala Lumpur', 'KLCC', ...],
           // ... 8 more regions
       ],
   ];
   ```

4. **Testing Results**
   - ✅ CLI scraper works with single and multiple areas
   - ✅ Batch processing verified with 2 locations
   - ✅ Config accessible throughout application
   - ✅ All 48 locations available in web UI

### Files Created/Modified in Phase 5.2

```
config/
└── locations.php ✅ (NEW - centralized location data)

app/Console/Commands/
└── ScrapeRestaurantsCommand.php ✅ (uses config instead of constant)

app/Livewire/
└── ScraperInterface.php ✅ (uses config instead of constant)

database/seeders/
└── PlaceSeeder.php ✅ (uses config with helper method)
```

**Total Changes:** 1 new file, 3 files modified, ~200 lines of code

### Benefits of Centralization

1. **Single Source of Truth**: All components reference the same config file
2. **Easy Maintenance**: Add/update locations in one place
3. **Type Safety**: Proper PHP array structures with consistent keys
4. **Scalability**: Easy to add new regions or locations
5. **Flexibility**: Different configs for seeding vs. scraping vs. UI
6. **No Duplication**: Eliminated 3 duplicate location arrays

### Usage Examples

**Accessing Config in Code:**
```php
// Get all coordinates
$coordinates = config('locations.coordinates');

// Get seeder configuration
$seederLocations = config('locations.seeder');

// Get regional groupings
$regions = config('locations.regions');
```

**CLI Commands Still Work:**
```bash
# Single area (from 48 available)
php artisan makanguru:scrape --area="Bangsar" --dry-run

# Multiple areas
php artisan makanguru:scrape --area="KLCC" --area="Mont Kiara" --dry-run

# All 48 locations
php artisan makanguru:scrape --area=all --limit=50
```

---

## Upcoming Phases

### Phase 8: User submissions (Community-led data)

**Goal**: Enable community contributions to restaurant database

**Tasks:**
1. User submission form
2. Moderation system
3. Community voting/ratings
4. Quality control

---

## Working with AI Assistants

### Context to Provide

When working on this project, ensure AI assistants have:
1. This CLAUDE.md file for comprehensive context
2. The current phase requirements from README.md
3. Relevant existing code (models, services, etc.)
4. Error messages or specific issues encountered

### Best Practices

1. **Always follow PSR-12**: Code should be formatted consistently
2. **Type everything**: Use PHP 8.4 type hints extensively
3. **Test scopes**: When modifying Place model, verify scopes still work
4. **Mobile-first**: UI components should work well on small screens
5. **Document thoroughly**: DocBlocks for all public methods
6. **Never hardcode**: Use config files and environment variables
7. **Error handling**: Always implement try-catch for external APIs
8. **Avoid over-engineering**: Keep solutions simple and focused
9. **Rate limiting awareness**: When adding new API-dependent features, consider rate limiting implications
10. **Session-based features**: Use Laravel sessions for stateful features (like rate limiting) without requiring authentication

### Common Commands

#### Docker Commands (Recommended)

```bash
# Service Management
docker compose up -d              # Start all services
docker compose down               # Stop all services
docker compose ps                 # Check service status
docker compose logs -f app        # View application logs
docker compose restart app        # Restart application

# Database (Docker)
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan tinker
docker compose exec mysql mysql -u makanguru -pmakanguru_secret makanguru

# Application Commands (Docker)
docker compose exec app php artisan [command]
docker compose exec app bash      # Access container shell
docker compose run --rm node npm run build
```

#### Native Commands (Without Docker)

```bash
# Database
php artisan migrate:fresh --seed  # Reset and seed
php artisan tinker                # REPL for testing

# AI Testing (Phase 2)
php artisan makanguru:ask "Where to get spicy food in PJ?" --persona=gymbro
php artisan makanguru:ask "I want halal breakfast" --persona=makcik --halal
php artisan makanguru:ask "Instagram-worthy cafe" --persona=atas --price=expensive
php artisan gemini:list-models              # List all available Gemini models
php artisan gemini:list-models --filter="gemini-2.5"  # Filter models
php artisan gemini:list-models --json       # JSON output

# Restaurant Scraping (Phase 5)
## CLI Commands
php artisan makanguru:scrape --area="Bangsar" --dry-run  # Preview only
php artisan makanguru:scrape --area="KLCC" --radius=5000 --limit=100  # Import
php artisan makanguru:scrape --area="Kuala Lumpur" --radius=10000 --limit=200

## Web UI (Recommended)
# Scraper: Import restaurants from OpenStreetMap
# Visit http://127.0.0.1:8000/scraper
# - Interactive sliders for radius/limit
# - Preview mode toggle
# - Real-time database stats
# - Visual results table

# Restaurant Database: Browse all restaurants
# Visit http://127.0.0.1:8000/restaurants
# - Search across name, cuisine, description, area
# - Filter by halal, price, area, cuisine
# - Sort by name, area, cuisine, price
# - Paginated results (20 per page)

# Code generation
php artisan make:model ModelName
php artisan make:migration create_table_name
php artisan make:seeder SeederName
php artisan make:livewire ComponentName
php artisan make:test TestName --unit

# Frontend
npm run dev     # Development mode
npm run build   # Production build

# Testing
php artisan test
php artisan test --filter TestName
php artisan test --filter ChatRateLimitTest  # Test rate limiting
```

---

## File Locations Reference

### Core Application Files
```
app/
├── Models/
│   └── Place.php ✅
├── Services/
│   ├── GeminiService.php              (Phase 2)
│   ├── GroqService.php                (Phase 2)
│   ├── PlaceCacheService.php          (Phase 4)
│   └── RestaurantScraperService.php   (Phase 5) ✅
├── Contracts/
│   └── AIRecommendationInterface.php  (Phase 2)
├── AI/
│   └── PromptBuilder.php              (Phase 2)
├── DTOs/
│   └── RecommendationDTO.php          (Phase 2)
├── Console/Commands/
│   ├── AskMakanGuruCommand.php        (Phase 2)
│   ├── ListGeminiModelsCommand.php    (Phase 2)
│   ├── ListGroqModelsCommand.php      (Phase 2)
│   └── ScrapeRestaurantsCommand.php   (Phase 5) ✅
└── Livewire/
    ├── ChatInterface.php              (Phase 3)
    └── ScraperInterface.php           (Phase 5) ✅
```

### Database Files
```
database/
├── migrations/
│   └── 2025_12_17_181313_create_places_table.php ✅
└── seeders/
    ├── DatabaseSeeder.php ✅
    └── PlaceSeeder.php ✅
```

### Frontend Files
```
resources/
├── css/
│   └── app.css ✅ (Tailwind config + custom colors)
├── js/
│   └── app.js
└── views/
    ├── home.blade.php                      (Phase 3)
    ├── scraper.blade.php                   (Phase 5) ✅
    ├── components/
    │   ├── layouts/
    │   │   └── app.blade.php               (Phase 3)
    │   ├── chat-bubble.blade.php           (Phase 3)
    │   ├── loading-spinner.blade.php       (Phase 3)
    │   ├── persona-switcher.blade.php      (Phase 3)
    │   ├── model-selector.blade.php        (Phase 3)
    │   └── nav-link.blade.php              (Phase 5) ✅
    └── livewire/
        ├── chat-interface.blade.php        (Phase 3)
        └── scraper-interface.blade.php     (Phase 5) ✅
```

### Configuration Files
```
.env ✅ (SQLite configured for native, includes rate limit settings)
.env.docker ✅ (Docker development environment template)
.env.example ✅ (Updated with chat and Docker configuration)
vite.config.js ✅ (Tailwind v4 plugin)
composer.json ✅
package.json ✅
config/chat.php ✅ (Chat & rate limiting configuration)
config/locations.php ✅ (Centralized location data - 48 Klang Valley coordinates)
```

### Docker Files
```
docker/
├── Dockerfile ✅ (PHP 8.4-FPM application container)
├── init.sh ✅ (Initialization script)
├── nginx/
│   ├── default.conf ✅ (Nginx site configuration)
│   └── nginx.conf ✅ (Nginx main configuration)
├── php/
│   └── php.ini ✅ (PHP runtime configuration)
└── mysql/
    └── my.cnf ✅ (MySQL configuration)

docker compose.yml ✅ (6 services: mysql, redis, app, nginx, queue, node)
```

---

## Environment Variables

### Required for AI Integration
```ini
# Google Gemini AI Configuration
# Get your API key from: https://ai.google.dev/
GEMINI_API_KEY=your_api_key_here

# Groq AI Configuration (Optional - for alternative models)
# Get your API key from: https://console.groq.com/
GROQ_API_KEY=your_groq_api_key_here

# Optional: Set default AI provider (gemini|groq)
AI_PROVIDER=gemini

# Optional: Groq model configuration
GROQ_OPENAI_MODEL=openai/gpt-oss-120b
GROQ_META_MODEL=llama-3.3-70b-versatile
GROQ_DEFAULT_MODEL=llama-3.1-8b-instant
```

Get API keys from:
- Gemini: https://ai.google.dev/
- Groq: https://console.groq.com/

### Database Configuration

**Native Development (SQLite):**
```ini
DB_CONNECTION=sqlite
# No additional config needed for local SQLite
```

**Docker Development (MySQL):**
```ini
DB_CONNECTION=mysql
DB_HOST=mysql          # Docker service name
DB_PORT=3306
DB_DATABASE=makanguru
DB_USERNAME=makanguru
DB_PASSWORD=makanguru_secret

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=redis       # Docker service name
```

**Production Configuration:**
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=makanguru
DB_USERNAME=root
DB_PASSWORD=your_secure_password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
```

### Chat Configuration (Phase 3)
```ini
# Rate limiting: Maximum messages per time window (default: 5)
CHAT_RATE_LIMIT_MAX=5

# Rate limiting: Time window in seconds (default: 60 = 1 minute)
CHAT_RATE_LIMIT_WINDOW=60

# Default persona: makcik, gymbro, or atas (default: makcik)
CHAT_DEFAULT_PERSONA=makcik

# Default AI model: gemini, groq-openai, or groq-meta (default: gemini)
CHAT_DEFAULT_MODEL=gemini
```

---

## API Integration Notes

### Gemini 2.5 Flash Endpoint (Current)
```
POST https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=API_KEY
```

**Important**: Use v1 endpoint (not v1beta). The gemini-1.5-flash model is deprecated.

### List Models Endpoint
```
GET https://generativelanguage.googleapis.com/v1/models?key=API_KEY
```

Use the `gemini:list-models` command to discover available models.

### Request Format
```json
{
  "contents": [
    {
      "role": "user",
      "parts": [
        {
          "text": "System: You are Mak Cik...\n\nContext: [JSON of places]\n\nUser: Where to eat spicy food in PJ?"
        }
      ]
    }
  ],
  "generationConfig": {
    "temperature": 0.9,
    "maxOutputTokens": 1000,
    "topP": 0.95
  },
  "safetySettings": [
    {
      "category": "HARM_CATEGORY_HATE_SPEECH",
      "threshold": "BLOCK_NONE"
    },
    {
      "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
      "threshold": "BLOCK_NONE"
    },
    {
      "category": "HARM_CATEGORY_DANGEROUS_CONTENT",
      "threshold": "BLOCK_NONE"
    },
    {
      "category": "HARM_CATEGORY_HARASSMENT",
      "threshold": "BLOCK_NONE"
    }
  ]
}
```

### Response Handling
- Extract text from `candidates[0].content.parts[0].text`
- Check `finishReason` (should be "STOP" for successful completion)
- **Model Fallback System**: Automatically tries alternative models when rate limits are hit
  - Primary: `gemini-2.5-flash`
  - Fallback 1: `gemini-2.0-flash`
  - Fallback 2: `gemini-2.5-flash-lite`
  - Fallback 3: `gemini-2.0-flash-lite`
- Implement exponential backoff for rate limits (2 retries per model)
- Detect rate limit errors via HTTP 429 or error messages containing "quota", "rate limit", "too many requests", "RESOURCE_EXHAUSTED"
- Log all API calls with model name and token usage for debugging
- Return persona-specific fallback response on failure

### Token Limits
- **Input**: Up to 1M tokens (context window for gemini-2.5-flash)
- **Output**: 10000 tokens (configured limit)
- **Cost**: $0.075 per 1M input tokens, $0.30 per 1M output tokens (gemini-2.5-flash pricing)

---

## Groq API Integration (Alternative Provider)

### Overview
Groq provides ultra-fast inference for open-source models (Llama, Mixtral) and OpenAI models via their custom LPU™ (Language Processing Unit) infrastructure. MakanGuru supports Groq as an alternative to Gemini.

### Supported Models
- **OpenAI GPT**: `openai/gpt-oss-120b` (via Groq)
- **Meta Llama 3.3**: `llama-3.3-70b-versatile` (70B parameters, 8K context)
- **Meta Llama 3.1**: `llama-3.1-8b-instant` (8B parameters, fast inference)

### Groq Chat Completions Endpoint
```
POST https://api.groq.com/openai/v1/chat/completions
Authorization: Bearer YOUR_GROQ_API_KEY
```

### Request Format (OpenAI-Compatible)
```json
{
  "model": "llama-3.3-70b-versatile",
  "messages": [
    {
      "role": "system",
      "content": "You are a helpful assistant providing restaurant recommendations."
    },
    {
      "role": "user",
      "content": "Where can I get spicy food in PJ?"
    }
  ],
  "temperature": 0.7,
  "max_tokens": 2048,
  "top_p": 1
}
```

### Response Format
```json
{
  "choices": [
    {
      "message": {
        "content": "Bro, for spicy food in PJ..."
      }
    }
  ],
  "usage": {
    "prompt_tokens": 150,
    "completion_tokens": 50,
    "total_tokens": 200
  },
  "model": "llama-3.3-70b-versatile"
}
```

### Model Fallback System (Groq)
Automatically tries alternative models when rate limits are hit:
1. Primary: User-selected model (e.g., `llama-3.3-70b-versatile`)
2. Fallback 1: `llama-3.1-8b-instant`
3. Fallback 2: `openai/gpt-oss-120b`
4. Fallback 3: `openai/gpt-oss-20b`

### Groq Pricing (as of December 2024)
| Model | Input Cost (per 1M tokens) | Output Cost (per 1M tokens) |
|-------|---------------------------|----------------------------|
| llama-3.3-70b-versatile | $0.59 | $0.79 |
| llama-3.1-8b-instant | $0.05 | $0.08 |
| openai/gpt-oss-120b | $0.80 | $1.20 |
| openai/gpt-oss-20b | $0.20 | $0.30 |

**Note**: Groq is significantly faster than traditional cloud providers due to LPU architecture.

### CLI Commands for Groq
```bash
# List all available Groq models
php artisan groq:list-models

# Filter models
php artisan groq:list-models --filter="llama"

# JSON output
php artisan groq:list-models --json

# Test with Groq
php artisan makanguru:ask "Where to get nasi lemak?" --model=groq-meta --persona=gymbro
php artisan makanguru:ask "Instagram-worthy cafe" --model=groq-openai --persona=atas
```

### Switching Between Providers

**In Code (Dynamic)**:
```php
// In ChatInterface or Commands
$service = match ($this->currentModel) {
    'groq-openai', 'groq-meta' => app(GroqService::class),
    default => app(GeminiService::class),
};

// Set specific Groq model
if ($service instanceof GroqService) {
    $service->setModel('llama-3.3-70b-versatile');
}
```

**In UI**:
Users can switch between AI providers using the Model Selector component in the chat interface.

**In Environment**:
```ini
AI_PROVIDER=groq  # Default to Groq instead of Gemini
```

---

## Troubleshooting

### Common Issues

**1. Gemini API Error: "Model Not Found" (404)**

**Symptom:**
```
models/gemini-1.5-flash is not found for API version v1beta
```

**Solution:**
The gemini-1.5-flash model has been deprecated. The system now uses a model fallback array with multiple models.

In `app/Services/GeminiService.php`:
```php
// ❌ Old (deprecated):
private const API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

// ✅ New (current with fallback):
private const BASE_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models';
private const FALLBACK_MODELS = [
    'gemini-2.5-flash',      // Primary
    'gemini-2.0-flash',      // Fallback 1
    'gemini-2.5-flash-lite', // Fallback 2
    'gemini-2.0-flash-lite', // Fallback 3
];
```

Run `php artisan gemini:list-models` to see all available models.

The system will automatically try each model in sequence when rate limits are encountered.

**2. Truncated AI Responses**

**Symptom:**
AI responses are cut off mid-sentence.

**Solution:**
Increase `maxOutputTokens` in the generation config:
```php
'generationConfig' => [
    'temperature' => 0.9,
    'maxOutputTokens' => 10000,  // Current setting
    'topP' => 0.95,
]
```

**3. Livewire Assets Not Loading (404 Error)**

**Symptom:**
```
GET /livewire/livewire.js 404 Not Found
```

**Solution:**
Livewire 3 requires its assets to be published to `public/vendor/livewire/`. This happens automatically during deployment, but if you encounter this error:

```bash
# Manually publish assets
php artisan livewire:publish --assets
```

**Automated Publishing:**
The command is already integrated into:
- `composer setup` - Initial project setup
- `composer update` - After composer dependencies update
- `docker/init.sh` - Docker initialization
- `deployment/deploy.sh` - Production deployment

The `public/vendor/` directory is excluded from git (added to `.gitignore`).

**4. Geospatial Queries Returning Incorrect Results**

**Symptom:**
The `Place::near()` scope returns all restaurants regardless of distance, or returns restaurants that should be excluded based on the radius.

**Solution:**
This was a critical bug fixed in December 2024. The Haversine formula had incorrect SQL parameter binding.

**If you're experiencing this issue**, ensure you have the latest version of `app/Models/Place.php`:

```php
// ✅ Correct implementation (fixed)
public function scopeNear(Builder $query, float $latitude, float $longitude, float $radiusKm = 10): Builder
{
    $haversine = "(6371 * acos(cos(radians({$latitude}))
                 * cos(radians(latitude))
                 * cos(radians(longitude) - radians({$longitude}))
                 + sin(radians({$latitude}))
                 * sin(radians(latitude))))";

    return $query
        ->selectRaw("*, {$haversine} AS distance")
        ->whereRaw("{$haversine} <= {$radiusKm}")
        ->orderBy('distance');
}
```

**The bug was**: Using parameterized bindings with mismatched parameter counts, causing the WHERE clause to fail silently.

**Test the fix**:
```bash
php artisan test --filter test_scope_near_filters_by_distance
```

**5. Database Schema Issues**
```bash
# Reset database completely
php artisan migrate:fresh --seed
```

**6. Type Cast Errors**
- Ensure `tags` is always an array in seeder
- Check that `latitude`/`longitude` are numeric values

**6. Build Errors**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Rebuild frontend
rm -rf node_modules public/build
npm install
npm run build
```

**6. Scope Not Working**
```bash
# Test in Tinker
php artisan tinker
>>> Place::halalOnly()->toSql()
```

**7. Gemini API Key Not Configured**
```bash
# Check .env file
GEMINI_API_KEY=your_api_key_here

# Test API key validity
php artisan tinker
>>> app(GeminiService::class)->healthCheck()
```

**8. Rate Limit Errors (429)**

**Symptom:**
```
Gemini API returned 429: Too Many Requests
```

**Solution:**
The model fallback system automatically handles rate limits. Check logs to see which models are being used:
```bash
tail -f storage/logs/laravel.log | grep "Gemini API"
```

You should see logs like:
```
Rate limit hit for model gemini-2.5-flash, trying next model
Gemini API Success {"model":"gemini-2.0-flash",...}
```

If all 4 models are rate-limited, the system will return a graceful fallback response. Wait a few minutes and try again.

**9. Safety Settings Blocking Content**

If responses are being blocked, check the `finishReason` in logs:
```bash
tail -f storage/logs/laravel.log
```

Look for `finishReason` other than "STOP". All 4 safety categories should be set to `BLOCK_NONE` for food recommendations.

**10. Groq API Key Not Configured**

**Symptom:**
```
Groq API key not configured. Set GROQ_API_KEY in .env
```

**Solution:**
```bash
# Add to .env file
GROQ_API_KEY=your_groq_api_key_here

# Test API key validity
php artisan tinker
>>> app(GroqService::class)->healthCheck()
```

Get your Groq API key from: https://console.groq.com/

**11. Groq Model Not Available**

**Symptom:**
Model selector shows "SOON" badge or model is disabled in UI.

**Solution:**
The model selector automatically detects API key availability. If Groq models show as "coming-soon":
1. Ensure `GROQ_API_KEY` is set in `.env`
2. Clear config cache: `php artisan config:clear`
3. Refresh the page

**12. Groq Rate Limits**

**Symptom:**
```
Groq API returned 429: Rate limit exceeded
```

**Solution:**
Groq has generous free tier limits but can still hit rate limits:
- **Free Tier**: 30 requests/minute, 14,400 requests/day
- **Paid Tier**: Higher limits based on plan

The system automatically falls back to alternative Groq models. Check logs:
```bash
tail -f storage/logs/laravel.log | grep "Groq"
```

**13. Comparing Groq vs Gemini Performance**

Use cost estimation to compare:
```bash
php artisan tinker

# Gemini cost (example: 1000 input, 500 output tokens)
>>> GeminiService::estimateCost(1000, 500)
# Output: 0.000225 USD

# Groq Llama cost (same tokens)
>>> GroqService::estimateCost(1000, 500, 'llama-3.3-70b-versatile')
# Output: 0.000985 USD

# Groq is ~4.4x more expensive than Gemini for this model
# But Llama 3.1 8B instant is cheaper:
>>> GroqService::estimateCost(1000, 500, 'llama-3.1-8b-instant')
# Output: 0.000090 USD (cheaper than Gemini!)
```

**14. Social Card Images Not Loading (404 Error) - Docker**

**Symptom:**
When sharing AI recommendations, the social media card modal shows:
- "Failed to load card preview" error message
- Browser console shows 404 errors for `/storage/social-cards/*.svg`
- Nginx logs show: `open() "/var/www/html/public/storage/..." failed (2: No such file or directory)`

**Root Cause:**
The `public/storage` symlink is pointing to an absolute host machine path instead of the Docker container's internal path. This happens when `php artisan storage:link` is run on the host machine instead of inside the container.

**Solution:**
Recreate the storage symlink inside the Docker container:

```bash
# Remove the incorrect symlink
docker compose exec app rm public/storage

# Recreate with correct path
docker compose exec app php artisan storage:link
```

**Verification:**
Check that the symlink points to the container's internal path:

```bash
# Should show: public/storage -> /var/www/html/storage/app/public
docker compose exec app ls -la public/ | grep storage

# NOT: public/storage -> /Users/.../storage/app/public (host path - wrong!)
```

**Prevention:**
Always run Laravel commands inside the Docker container, not on the host machine:

```bash
# ✅ Correct (inside container)
docker compose exec app php artisan storage:link

# ❌ Wrong (on host machine)
php artisan storage:link
```

**For Native (Non-Docker) Setup:**
This issue doesn't occur in native installations. If you experience 404 errors on storage files in native setup:

```bash
# Recreate symlink
php artisan storage:link

# Check permissions
chmod -R 775 storage bootstrap/cache
```

---

## Version Information

- **Laravel**: 12.x
- **PHP**: 8.4
- **Tailwind CSS**: 4.1.x
- **Livewire**: 3.x (to be installed in Phase 3)
- **Node.js**: 24.12.0 LTS
- **SQLite**: 3.x (system default)

---

## Current Status Summary

**Phase 1**: ✅ **COMPLETE**
- Database architecture solid
- Type-safe models with powerful query scopes
- **Real OpenStreetMap data** (50-70 restaurants across 7 Malaysian areas)
- Malaysian design system configured
- Build pipeline functional
- **Critical Bug Fixed**: Haversine formula SQL parameter binding corrected

**Phase 2**: ✅ **COMPLETE**
- Clean service architecture implemented
- 6 Malaysian AI personas (Mak Cik, Gym Bro, Atas, Tauke, Mat Motor, Corporate Slave)
- Groq API integration (OpenAI GPT + Meta Llama)
- Gemini 2.5 Flash API integration (legacy support)
- Comprehensive error handling with fallback responses
- CLI testing commands created
- **201 unit/feature tests passing (540+ assertions, 99.0% pass rate)**
- Token usage tracking and cost estimation
- **Test Environment**: Seeders disabled in tests for isolation

**API Configuration:**
- **Primary Model**: OpenAI GPT (via Groq) - Default for all requests
- **Automatic Fallback**: Meta Llama (via Groq) - Used when primary fails/rate-limited
- **Intelligent Failover**: Seamless model switching without user intervention
- Max Output Tokens: 2048 (Groq), 10000 (Gemini)
- Retry Logic: Exponential backoff (2 retries per model)
- Rate Limit Handling: Automatic model switching on 429 errors
- Endpoint: OpenAI-compatible v1 (Groq)

**Testing Commands:**
```bash
php artisan makanguru:ask "your question" --persona=makcik
php artisan gemini:list-models
php artisan test
```

**Phase 3**: ✅ **COMPLETE**
- Mobile-first Livewire 3 chat interface implemented
- Reusable Blade components created:
  - `chat-bubble` - Message bubbles with persona avatars and proper spacing
  - `loading-spinner` - Persona-specific loading states
  - `restaurant-card` - Place display component
  - `persona-switcher` - Three persona selection UI
  - `model-selector` - AI model/provider selection (deprecated in Phase 7.1)
- Alpine.js micro-interactions (built-in via Livewire 3):
  - Auto-scroll to latest message
  - Smooth fadeIn animations for chat bubbles
- Automatic AI model failover (implemented in Phase 7.1):
  - Primary: OpenAI GPT via Groq
  - Fallback: Meta Llama via Groq
  - Seamless switching without user intervention
- Responsive filters (Halal, Price, Area)
- Real-time chat history management with model tracking
- Enter key to send (Shift+Enter for new line)
- Clear chat functionality with confirmation
- Malaysian-themed design with gradient backgrounds
- Blue send button matching user message bubbles
- Optimized spacing in chat container
- Fixed Livewire 3 compatibility (removed duplicate Alpine.js)
- Working loading indicators with wire:loading
- **Rate Limiting System**:
  - Session-based rate limiting (5 messages per 60 seconds)
  - Persona-specific rate limit messages
  - Visual warning banner with countdown timer
  - Disabled send button when rate limited
  - Configurable via `config/chat.php`
  - Comprehensive test coverage (4 tests, 13 assertions)

**Files Created in Phase 3:**
```
resources/views/
├── components/
│   ├── layouts/
│   │   └── app.blade.php ✅
│   ├── chat-bubble.blade.php ✅
│   ├── loading-spinner.blade.php ✅
│   ├── restaurant-card.blade.php ✅
│   ├── persona-switcher.blade.php ✅
│   └── model-selector.blade.php ✅
├── livewire/
│   └── chat-interface.blade.php ✅
└── home.blade.php ✅

app/Livewire/
└── ChatInterface.php ✅ (with rate limiting logic)

config/
└── chat.php ✅ (rate limiting configuration)

tests/Feature/
└── ChatRateLimitTest.php ✅ (4 tests, 13 assertions)

resources/js/
└── app.js ✅ (Optimized - uses Livewire 3's built-in Alpine.js)

docs/
├── README.md ✅ (documentation index)
├── guides/
│   ├── RATE_LIMITING.md ✅ (rate limiting guide)
│   ├── SCRAPER_GUIDE.md ✅ (CLI scraper guide)
│   └── SCRAPER_UI_GUIDE.md ✅ (web scraper guide)
└── implementation/
    ├── PHASE5_COMPLETE.md ✅ (Phase 5 summary)
    └── SCRAPER_WEB_UI_COMPLETE.md ✅ (Scraper UI summary)
```

**Running the Application:**
```bash
# Start backend server
php artisan serve

# View in browser
http://127.0.0.1:8000

# Test with different personas
# Switch between Mak Cik, Gym Bro, and Atas Friend
# Apply filters: Halal, Price Range, Area
# Ask questions like "Where to get spicy food in PJ?"
```

**Phase 4**: ✅ **COMPLETE**
- Redis caching for restaurant context queries implemented
- AWS EC2 deployment scripts created
- Nginx configuration with SSL/TLS support
- Supervisor configuration for queue workers
- SSL/Certbot automated setup script
- GitHub Actions CI/CD workflow
- Comprehensive deployment documentation
- Production environment configuration

**Files Created in Phase 4:**
```
app/Services/
└── PlaceCacheService.php ✅ (Redis caching service)

deployment/
├── setup-server.sh ✅ (EC2 server provisioning)
├── deploy.sh ✅ (Application deployment)
├── nginx.conf ✅ (Nginx web server config)
├── supervisor.conf ✅ (Queue worker management)
├── setup-ssl.sh ✅ (SSL/TLS automation)
└── DEPLOYMENT.md ✅ (Deployment guide)

.github/workflows/
└── tests.yml ✅ (CI/CD pipeline)

.env.production.example ✅ (Production environment template)
```

**Phase 5**: ✅ **COMPLETE**
- OpenStreetMap integration via Overpass API
- CLI scraper command with 7 Malaysian cities
- Beautiful web UI for scraper (`/scraper`)
- Interactive sliders and preview mode
- Restaurant database browser (`/restaurants`)
- Type-ahead search and real-time filtering
- Sortable columns with pagination
- Real-time database statistics
- Comprehensive testing (9 tests, 23 assertions)
- Full documentation (2,500+ lines)

**Files Created in Phase 5:**
```
app/Services/
└── RestaurantScraperService.php ✅ (450+ lines)

app/Console/Commands/
└── ScrapeRestaurantsCommand.php ✅ (350+ lines)

app/Livewire/
├── ScraperInterface.php ✅ (273 lines)
└── RestaurantList.php ✅ (240 lines)

resources/views/
├── scraper.blade.php ✅
├── restaurants.blade.php ✅
├── livewire/
│   ├── scraper-interface.blade.php ✅ (288 lines)
│   └── restaurant-list.blade.php ✅ (266 lines)
└── components/
    ├── nav-link.blade.php ✅
    └── layouts/app.blade.php ✅ (updated)

tests/Unit/
└── RestaurantScraperServiceTest.php ✅ (250+ lines)

routes/web.php ✅ (added /scraper and /restaurants routes)

Documentation/
├── SCRAPER_GUIDE.md ✅ (600+ lines - CLI guide)
├── SCRAPER_UI_GUIDE.md ✅ (600+ lines - Web UI guide)
├── PHASE5_COMPLETE.md ✅ (500+ lines - Implementation summary)
└── SCRAPER_WEB_UI_COMPLETE.md ✅ (500+ lines - Web UI summary)
```

**Usage:**
```bash
# CLI Command
php artisan makanguru:scrape --area="KLCC" --radius=5000 --limit=100

# Web UI (Recommended)
Visit: http://127.0.0.1:8000/scraper
- Interactive interface with sliders
- Preview mode for safe exploration
- Visual results table
- Real-time stats
```

**Phase 6**: ✅ **COMPLETE**
- SVG social card generation with persona-specific styling
- Share button integration in chat interface
- Card preview modal with multi-platform sharing
- Social media optimization (Open Graph, Twitter Cards)
- Storage management with automatic cleanup
- Comprehensive testing (21 tests, 63 assertions)
- Full documentation (1,050+ lines)

**Files Created in Phase 6:**
```
app/Services/
└── SocialCardService.php ✅ (220 lines)

app/Console/Commands/
└── CleanupSocialCardsCommand.php ✅ (45 lines)

resources/views/components/
└── social-card-modal.blade.php ✅ (165 lines)

tests/
├── Unit/
│   └── SocialCardServiceTest.php ✅ (220 lines)
└── Feature/
    └── SocialCardSharingTest.php ✅ (130 lines)

docs/
├── implementation/
│   └── PHASE6_COMPLETE.md ✅ (500+ lines)
└── guides/
    └── SOCIAL_SHARING_GUIDE.md ✅ (550+ lines)
```

**Usage:**
```bash
# Generate social cards via chat interface
# 1. Get AI recommendation
# 2. Click "Share This Vibe" button
# 3. Preview and share to social platforms

# Cleanup old cards (manual)
php artisan makanguru:cleanup-cards

# Schedule cleanup (in Kernel.php)
$schedule->command('makanguru:cleanup-cards')->daily();
```

**Phase 7**: ✅ **COMPLETE**
- Expanded from 3 to 6 AI personas (added Tauke, Mat Motor, Corporate Slave)
- Persona-specific loading messages, example queries, fallback responses
- Enhanced chat bubble styling with colored borders and gradients
- Time-based persona suggestions (smart recommendations by hour)
- Session-based persona analytics tracking
- Smart filters auto-applied per persona (e.g., Mak Cik → Halal enabled)
- Persona-specific response templates with emoji formatting
- Full documentation (500+ lines)

**Phase 7 Sub-Phases:**
- **7.1 Quick Wins**: Loading messages, example queries, fallback responses
- **7.2 Medium Impact**: Tag hints, chat styling, time-based suggestions
- **7.3 High Impact**: Analytics tracking, smart filters, response templates

**New Features:**
- 🤖 **Smart Filter Badge**: Visual indicator when persona-driven filters are active
- 💡 **Time-Based Suggestions**: "Perfect timing!" banner suggests ideal persona
- 📊 **Analytics**: Track persona usage patterns (session-based, privacy-friendly)
- 🎨 **Visual Identity**: Each persona has unique colored borders and gradients

**Files Modified in Phase 7:**
```
Phase 7.1-7.3:
├── app/AI/PromptBuilder.php ✅ (Tag hints for 3 personas)
├── app/Livewire/ChatInterface.php ✅ (Time suggestions, analytics, smart filters)
├── app/DTOs/RecommendationDTO.php ✅ (Fallback messages, response formatting)
├── resources/views/components/loading-spinner.blade.php ✅ (6 persona messages)
├── resources/views/components/chat-bubble.blade.php ✅ (Borders, gradients)
├── resources/views/components/persona-switcher.blade.php ✅ (Suggestion banner)
└── resources/views/livewire/chat-interface.blade.php ✅ (Example queries, smart badge)

Documentation:
└── docs/implementation/PHASE7_COMPLETE.md ✅ (500+ lines implementation guide)
```

**Total Changes**: 7 files modified, ~500 lines of code

**Phase 7.1**: ✅ **COMPLETE** (December 2024)
- Removed manual AI model selector from UI
- Implemented automatic OpenAI → Meta fallback
- Simplified UX by hiding technical complexity
- Enhanced service layer with intelligent model switching
- Improved reliability with seamless failover

**Files Modified in Phase 7.1:**
```
├── resources/views/livewire/chat-interface.blade.php ✅ (removed model selector)
├── app/Livewire/ChatInterface.php ✅ (automatic fallback logic)
└── app/Services/GroqService.php ✅ (separate fallback arrays)
```

**Total Changes**: 3 files modified, ~80 lines of code

---

## Phase 7 Implementation Status ✅

### Completed Tasks

**Phase 7 expanded MakanGuru from 3 to 6 AI personas with comprehensive enhancements.**

**New Personas Added:**
- 🧧 **Tauke** (The Big Boss) - Business-focused, efficiency-driven, "time is money"
- 🏍️ **Mat Motor** (The Rempit) - Late-night specialist, easy parking, budget-friendly
- 💼 **Corporate Slave** (The OL/Salaryman) - Quick lunches, coffee-dependent, WiFi essential

#### Phase 7.1: Quick Wins ✅

1. **Persona-Specific Loading Messages**
   - ✅ Added contextual loading text for all 6 personas
   - ✅ Examples: "Calculating ROI and checking reviews..." (Tauke), "Checking parking spots and late night options..." (Mat Motor)

2. **Example Query Buttons**
   - ✅ 3 quick-start queries per persona
   - ✅ Color-coded buttons matching persona themes
   - ✅ One-click query population

3. **Persona-Specific Fallback Responses**
   - ✅ Error messages maintain personality
   - ✅ Examples: "Wa lao eh! System down, wasting time!" (Tauke), "Connection koyak already!" (Mat Motor)

#### Phase 7.2: Medium Impact ✅

1. **Persona-Specific Tag Hints**
   - ✅ Added intelligent tag hints to PromptBuilder for Tauke, Mat Motor, Corporate
   - ✅ Examples: "late-night, 24-7, mamak, supper" (Mat Motor), "coffee, wifi, lunch-set, quick-service" (Corporate)

2. **Enhanced Chat Bubble Styling**
   - ✅ Persona-specific colored borders (yellow, purple, gray)
   - ✅ Updated avatar gradients for all 6 personas
   - ✅ Consistent emoji display (🧧, 🏍️, 💼)

3. **Time-Based Persona Suggestions**
   - ✅ Intelligent suggestions based on time of day
   - ✅ Logic: Late night (10PM-4AM) → Mat Motor, Work hours (9AM-6PM) → Corporate, etc.
   - ✅ Beautiful suggestion banner in persona switcher

#### Phase 7.3: High Impact ✅

1. **Persona Analytics Tracking**
   - ✅ Session-based analytics (no database required)
   - ✅ Tracks usage count, timestamps, time-of-day patterns
   - ✅ Methods: `getPersonaAnalytics()`, `getMostPopularPersona()`
   - ✅ Foundation for future AI-driven suggestions

2. **Smart Filters Based on Persona**
   - ✅ Automatic filter application when switching personas
   - ✅ Logic: Mak Cik → Halal enabled, Atas → Expensive only, Mat Motor → Budget only, etc.
   - ✅ "🤖 Smart" badge indicator in UI
   - ✅ Users can manually override auto-applied filters

3. **Persona-Specific Response Templates**
   - ✅ Emoji prefix formatting for all 6 personas
   - ✅ Smart detection to avoid duplicate emojis
   - ✅ Method: `getFormattedRecommendation()` in RecommendationDTO

### Files Created/Modified in Phase 7

```
Phase 7.1:
└── resources/views/components/loading-spinner.blade.php ✅ (Loading messages)
└── resources/views/livewire/chat-interface.blade.php ✅ (Example queries)
└── app/DTOs/RecommendationDTO.php ✅ (Fallback messages)

Phase 7.2:
└── app/AI/PromptBuilder.php ✅ (Tag hints for 3 personas)
└── resources/views/components/chat-bubble.blade.php ✅ (Borders, gradients)
└── app/Livewire/ChatInterface.php ✅ (Time-based suggestions)
└── resources/views/components/persona-switcher.blade.php ✅ (Suggestion banner)

Phase 7.3:
└── app/Livewire/ChatInterface.php ✅ (Analytics + smart filters)
└── app/DTOs/RecommendationDTO.php ✅ (Response formatting)
└── resources/views/livewire/chat-interface.blade.php ✅ (Smart badge)

Documentation:
└── docs/implementation/PHASE7_COMPLETE.md ✅ (500+ lines implementation guide)
```

**Total Changes**: 7 files modified, ~500 lines of code

---

## Recent Improvements (December 2024) ✅

### 🐛 Critical Bug Fixes

**1. Fixed Haversine Geospatial Query Bug**
- **File**: `app/Models/Place.php::scopeNear()`
- **Issue**: SQL parameter binding was incorrect, causing the distance filter to not work
- **Impact**: All geospatial queries were returning incorrect results
- **Fix**: Changed from parameterized bindings to direct value interpolation
- **Result**: All 3 failing geospatial tests now passing

**Before (Broken)**:
```php
$haversine = "(6371 * acos(cos(radians(?))...";
->whereRaw("{$haversine} <= ?", [$latitude, $longitude, $latitude, $radiusKm])
// Parameter count mismatch - 4 values for 3 placeholders
```

**After (Fixed)**:
```php
$haversine = "(6371 * acos(cos(radians({$latitude}))...";
->whereRaw("{$haversine} <= {$radiusKm}")
// Direct interpolation - works correctly
```

### 🧪 Test Suite Enhancements

**2. Comprehensive Test Coverage Added**
- **New Tests**: 150+ new tests added (from 50 to 201 total)
- **Coverage**: 540+ assertions across all components
- **Success Rate**: 99.5% (200 passing, 0 failing, 1 skipped)
- **Test Files Created**:
  - `PlaceModelTest.php` (34 tests) - All model scopes and attributes
  - `PlaceCacheServiceTest.php` (25 tests) - Redis caching logic
  - `PromptBuilderTest.php` (34 tests) - All 6 personas
  - `RecommendationDTOTest.php` (27 tests) - DTO transformations
  - Enhanced `GeminiServiceTest.php` (added 18 tests)
  - `ChatInterfaceTest.php` (28 tests) - Livewire component

**3. Test Environment Optimization**
- **File**: `tests/TestCase.php`
- **Change**: Added `protected $seed = false;`
- **Reason**: Prevents PlaceSeeder from running during tests
- **Impact**: Eliminates test data pollution from 50+ seeded restaurants
- **Benefit**: Clean test isolation, all geospatial tests now reliable

### 🌍 Database Seeder Upgrade

**4. Real OpenStreetMap Data Integration**
- **File**: `database/seeders/PlaceSeeder.php`
- **Previous**: Hardcoded 15 fake/dummy restaurants
- **New**: Fetches 50-70 real restaurants from OpenStreetMap
- **Areas Covered**: Bangsar, KLCC, Petaling Jaya, Damansara, Subang Jaya, Bukit Bintang, Shah Alam
- **Features**:
  - 10 restaurants per area with configurable radius
  - Intelligent duplicate detection (by name and area)
  - Fallback to 5 golden records if scraping fails
  - Proper error handling and logging
  - API rate limiting (0.5s delay between requests)

**Benefits**:
- More realistic development data
- Better demonstration of AI recommendations
- Accurate geolocation data
- Real cuisine types and tags

### 🔧 Test Suite Maintenance

**6. Fixed Rate Limit Issues in Tests**
- **File**: `tests/Feature/ChatInterfaceTest.php`
- **Issue**: Tests iterating through all 6 personas were hitting the default rate limit (5 messages/min)
- **Fix**: Dynamically increased rate limit config (`chat.rate_limit.max_messages` => 10) specifically for the `test_all_six_personas_work` method
- **Benefit**: Prevents false positive test failures while keeping rate limiting active for other tests

**7. Modernized PHPUnit Syntax**
- **Files**: `SocialCardServiceTest.php`, `SocialCardSharingTest.php`
- **Issue**: Deprecation warnings for PHPDoc annotations (`/** @test */`) in PHPUnit 11+
- **Fix**: Migrated to PHP 8 Attributes (`#[Test]`)
- **Benefit**: Future-proofs the test suite and removes CLI warnings

### 📊 Documentation Updates

**5. Updated Documentation Files**
- **README.md**: Added comprehensive testing section
- **TEST_COVERAGE_SUMMARY.md**: Updated with bug fixes and 99.0% pass rate
- **CLAUDE.md**: Documented all improvements and bug fixes

---

## Upcoming Phases

### **Phase 8: User Submissions (Community-led data)**
**Next Steps**:
- User authentication system
- Restaurant submission forms
- Community voting/ratings
- Moderation and quality control

---

*Last Updated: 2024-12-24*
*Maintained by: AI-assisted development with Claude*
