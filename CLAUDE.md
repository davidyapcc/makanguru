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
- **Primary Provider**: Google Gemini 2.5 Flash (latest stable model)
- **Alternative Providers**:
  - Groq (OpenAI GPT via Groq Cloud)
  - Groq (Meta Llama via Groq Cloud)
- **Method**: REST API (context injection pattern)
- **API Versions**:
  - Gemini: v1 (stable endpoint)
  - Groq: OpenAI-compatible v1 endpoint
- **Fallback**: Multi-model fallback with graceful degradation

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
   - ✅ Model switching functionality with `switchModel()` method

2. **Reusable Blade Components**
   - ✅ `chat-bubble.blade.php` - Dynamic message bubbles with persona avatars
   - ✅ `loading-spinner.blade.php` - Persona-specific typing indicators
   - ✅ `restaurant-card.blade.php` - Place information display
   - ✅ `persona-switcher.blade.php` - Three-persona selection interface
   - ✅ `model-selector.blade.php` - AI model/provider selection interface
   - ✅ `layouts/app.blade.php` - Main application layout

3. **Alpine.js Micro-Interactions**
   - ✅ Installed Alpine.js 3.x
   - ✅ Auto-scroll to latest message on new chat
   - ✅ Smooth fadeIn animations for chat bubbles
   - ✅ `x-data`, `x-init`, `x-ref` for state management

4. **UI/UX Features**
   - ✅ Mobile-first responsive design
   - ✅ Model selector with 3 AI providers (Gemini active, OpenAI/Meta via Groq coming soon)
   - ✅ Real-time filters (Halal, Price, Area) with `wire:model.live`
   - ✅ Loading states with `wire:loading`
   - ✅ Enter key to send (Shift+Enter for new line)
   - ✅ Clear chat with confirmation dialog
   - ✅ Malaysian color palette gradients
   - ✅ Persona-specific fallback messages
   - ✅ Model tracking in chat history

5. **Routes & Views**
   - ✅ Updated `routes/web.php` to serve chat interface
   - ✅ Created `home.blade.php` view
   - ✅ Integrated Livewire scripts and styles

### Files Created in Phase 3

See "Current Status Summary" section below for complete file listing.

---

## Upcoming Phases

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
- Model Fallback System: 4 models (gemini-2.5-flash → gemini-2.0-flash → gemini-2.5-flash-lite → gemini-2.0-flash-lite)
- Primary Model: gemini-2.5-flash (latest stable)
- Endpoint: v1 (not v1beta)
- Max Output Tokens: 10000
- Retry Logic: Exponential backoff (2 retries per model)
- Rate Limit Handling: Automatic model switching on 429 errors
- Safety Settings: All 4 categories configured

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
  - `model-selector` - AI model/provider selection (Gemini, OpenAI via Groq, Meta via Groq)
- Alpine.js micro-interactions (built-in via Livewire 3):
  - Auto-scroll to latest message
  - Smooth fadeIn animations for chat bubbles
- Model selection with future Groq integration support:
  - Currently active: Google Gemini
  - Coming soon: OpenAI GPT via Groq, Meta Llama via Groq
  - Visual status indicators and disabled state for unreleased models
- Responsive filters (Halal, Price, Area)
- Real-time chat history management with model tracking
- Enter key to send (Shift+Enter for new line)
- Clear chat functionality with confirmation
- Malaysian-themed design with gradient backgrounds
- Blue send button matching user message bubbles
- Optimized spacing in chat container
- Fixed Livewire 3 compatibility (removed duplicate Alpine.js)
- Working loading indicators with wire:loading

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
└── ChatInterface.php ✅

resources/js/
└── app.js ✅ (Optimized - uses Livewire 3's built-in Alpine.js)
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

**Next Steps**: Begin Phase 4 - Production Deployment
- Redis caching for context queries
- EC2 setup with Nginx
- SSL configuration
- Queue workers with Supervisor
- Optional: CI/CD with GitHub Actions

---

*Last Updated: 2025-12-18*
*Maintained by: AI-assisted development with Claude*
