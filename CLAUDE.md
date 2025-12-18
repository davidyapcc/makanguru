# MakanGuru - Technical Documentation for AI Assistants

This document provides comprehensive context for AI assistants (like Claude) working on the MakanGuru project.

## Project Overview

**MakanGuru** is an AI-powered Malaysian food recommendation application that solves the "Makan Mana?" (Where to eat?) dilemma using context-aware AI personalities.

### Core Concept
Unlike traditional directory apps (Google Maps/Yelp), MakanGuru uses **AI Personas** to provide curated, personality-driven recommendations:
- **The Mak Cik**: Value-focused, halal-conscious, nurturing recommendations
- **The Gym Bro**: Protein-heavy, efficiency-focused, "padu" recommendations
- **The Atas Friend**: Aesthetic, upscale, Instagram-worthy recommendations

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
- **Provider**: Google Gemini 2.5 Flash (latest stable model)
- **Method**: REST API (context injection pattern)
- **API Version**: v1 (stable endpoint)
- **Fallback**: Error handling with graceful degradation

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

### Local Setup

**Prerequisites:**
- PHP >= 8.4
- Composer
- Node.js (via nvm recommended)
- SQLite (pre-installed on macOS)

**Installation:**
```bash
# Clone and install
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate:fresh --seed

# Build assets
npm run dev  # Development
npm run build  # Production
```

### Running the Application

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
   - ✅ Created `PromptBuilder` class with 3 Malaysian personas:
     - **Mak Cik**: Nurturing, halal-focused, value-conscious
     - **Gym Bro**: Protein-focused, efficiency-driven, "padu"
     - **Atas Friend**: Aesthetic-focused, upscale, Instagram-worthy
   - ✅ Each persona has unique speech patterns and priorities
   - ✅ Token-efficient JSON context injection

3. **Data Transfer Objects**
   - ✅ Created `RecommendationDTO` for type-safe data transfer
   - ✅ Factory methods: `fromGeminiResponse()`, `fallback()`
   - ✅ Helper methods: `isFallback()`, `getTokensUsed()`

4. **API Integration**
   - ✅ Gemini 2.5 Flash API integration (v1 endpoint)
   - ✅ Retry logic with exponential backoff
   - ✅ Comprehensive error handling with graceful fallback
   - ✅ Safety settings configuration (all 4 categories)
   - ✅ Enhanced logging (finish reasons, token usage)
   - ✅ Output limit: 1000 tokens for complete responses

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
│   └── GeminiService.php ✅
├── AI/
│   └── PromptBuilder.php ✅
├── DTOs/
│   └── RecommendationDTO.php ✅
├── Providers/
│   └── AIServiceProvider.php ✅
└── Console/Commands/
    ├── AskMakanGuruCommand.php ✅
    └── ListGeminiModelsCommand.php ✅

tests/Unit/
└── GeminiServiceTest.php ✅

database/factories/
└── PlaceFactory.php ✅
```

---

## Upcoming Phases

### Phase 3: UI/UX Layer

**Goal**: Mobile-first Livewire interface

**Tasks:**
1. Create Livewire `ChatInterface` component
2. Build reusable Blade components (chat bubbles, cards, spinners)
3. Implement persona switcher UI
4. Add micro-interactions with Alpine.js
5. Implement loading states with `wire:loading`

### Phase 4: Production Deployment

**Goal**: AWS deployment with optimization

**Tasks:**
1. Redis caching for context queries
2. EC2 setup with Nginx
3. SSL configuration
4. Queue workers with Supervisor
5. Optional: CI/CD with GitHub Actions

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

### Common Commands

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
```

---

## File Locations Reference

### Core Application Files
```
app/
├── Models/
│   └── Place.php ✅
├── Services/          (Phase 2)
│   └── GeminiService.php
├── Contracts/         (Phase 2)
│   └── AIRecommendationInterface.php
├── AI/                (Phase 2)
│   └── PromptBuilder.php
├── DTOs/              (Phase 2)
│   └── RecommendationDTO.php
└── Livewire/          (Phase 3)
    └── ChatInterface.php
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
    ├── components/
    │   └── layouts/
    │       └── app.blade.php (Phase 3)
    └── livewire/
        └── chat-interface.blade.php (Phase 3)
```

### Configuration Files
```
.env ✅ (SQLite configured)
vite.config.js ✅ (Tailwind v4 plugin)
composer.json ✅
package.json ✅
```

---

## Environment Variables

### Required for Phase 2
```ini
# Add to .env
GEMINI_API_KEY=your_api_key_here
```

Get API key from: https://ai.google.dev/

### Database Configuration (Current)
```ini
DB_CONNECTION=sqlite
# No additional config needed for local SQLite
```

### Production Configuration (Phase 4)
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=makanguru
DB_USERNAME=root
DB_PASSWORD=your_password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
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
- Implement exponential backoff for rate limits (2 retries max)
- Log all API calls with token usage for debugging
- Return persona-specific fallback response on failure

### Token Limits
- **Input**: Up to 1M tokens (context window)
- **Output**: 1000 tokens (configured limit)
- **Cost**: $0.075 per 1M input tokens, $0.30 per 1M output tokens

---

## Troubleshooting

### Common Issues

**1. Gemini API Error: "Model Not Found" (404)**

**Symptom:**
```
models/gemini-1.5-flash is not found for API version v1beta
```

**Solution:**
The gemini-1.5-flash model has been deprecated. Use gemini-2.5-flash instead with the v1 endpoint.

In `app/Services/GeminiService.php`:
```php
// ❌ Old (deprecated):
private const API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

// ✅ New (current):
private const API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
```

Run `php artisan gemini:list-models` to see all available models.

**2. Truncated AI Responses**

**Symptom:**
AI responses are cut off mid-sentence.

**Solution:**
Increase `maxOutputTokens` in the generation config:
```php
'generationConfig' => [
    'temperature' => 0.9,
    'maxOutputTokens' => 1000,  // Increased from 500
    'topP' => 0.95,
]
```

**3. Database Schema Issues**
```bash
# Reset database completely
php artisan migrate:fresh --seed
```

**4. Type Cast Errors**
- Ensure `tags` is always an array in seeder
- Check that `latitude`/`longitude` are numeric values

**5. Build Errors**
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

**8. Safety Settings Blocking Content**

If responses are being blocked, check the `finishReason` in logs:
```bash
tail -f storage/logs/laravel.log
```

Look for `finishReason` other than "STOP". All 4 safety categories should be set to `BLOCK_NONE` for food recommendations.

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
- 15 quality seed records
- Malaysian design system configured
- Build pipeline functional

**Phase 2**: ✅ **COMPLETE**
- Clean service architecture implemented
- 3 Malaysian AI personas (Mak Cik, Gym Bro, Atas)
- Gemini 2.5 Flash API integration
- Comprehensive error handling with fallback responses
- CLI testing commands created
- 9 unit tests passing (28 assertions)
- Token usage tracking and cost estimation

**API Configuration:**
- Model: gemini-2.5-flash (latest stable)
- Endpoint: v1 (not v1beta)
- Max Output Tokens: 1000
- Retry Logic: Exponential backoff (2 max retries)
- Safety Settings: All 4 categories configured

**Testing Commands:**
```bash
php artisan makanguru:ask "your question" --persona=makcik
php artisan gemini:list-models
php artisan test
```

**Next Steps**: Begin Phase 3 - Modern UI/UX Layer
- Build Livewire 3 chat interface
- Create reusable Blade components
- Implement persona switcher UI
- Add micro-interactions with Alpine.js
- Mobile-first responsive design

---

*Last Updated: 2025-12-18*
*Maintained by: AI-assisted development with Claude*
