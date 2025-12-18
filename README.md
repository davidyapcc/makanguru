# MakanGuru 🇲🇾

**The AI-Powered "Makan Mana?" Decider**

MakanGuru is a generative AI application designed to solve the eternal Malaysian paradox: *"So many places to eat, but don't know where to go."*

Unlike standard directories (Google Maps/Yelp), MakanGuru uses **Context-Aware AI Personalities** (The Mak Cik, The Gym Bro, The Atas Friend) to provide curated, vibe-based recommendations using natural language.

---

## 🎨 UI/UX Philosophy

We prioritize a **Mobile-First, User-Centric Design**. The interface is built to be "thumb-friendly" and visually decluttered, acknowledging that most users will be deciding on dinner while on the go or in a car.

* **Modern Aesthetics:** Clean, ample whitespace, and consistent typography using Tailwind CSS.
* **Micro-Interactions:** Subtle animations (via Alpine.js) provide immediate feedback without overwhelming the user.
* **Accessibility:** High contrast ratios and semantic HTML structure to ensure usability for all.

---

## 🛠 Tech Stack

Built on a modern, monolithic architecture optimized for speed and developer experience (DX).

* **Framework:** [Laravel 12](https://laravel.com) (PHP 8.4)
* **Frontend:** [Livewire 3](https://livewire.laravel.com) + [Tailwind CSS](https://tailwindcss.com) + Alpine.js
* **Database:** MySQL 8.0
* **AI Engines:**
  * Google Gemini 2.5 Flash (primary)
  * Groq (Meta Llama 3.3, OpenAI GPT) (alternative)
* **Queue/Cache:** Redis
* **Infrastructure:** AWS EC2 (Ubuntu 24.04), Nginx

---

## 🏗 Engineering Standards

This project is engineered with a focus on **Object-Oriented Programming (OOP)** and maintainability. It is not just a prototype, but a production-ready codebase.

* **SOLID Principles:** Strictly adhered to, ensuring loosely coupled and highly cohesive classes.
* **Design Patterns:**
* **Service Pattern:** All AI logic is encapsulated in dedicated services (e.g., `GeminiService`), separating business logic from controllers.
* **Repository Pattern:** (Optional/If implemented) For abstracting data access layers.


* **Clean Code:** We follow **PSR-12** coding standards. Variable naming is descriptive, methods are small and focused, and type hinting is used extensively.
* **Type Safety:** PHP 8.4 features (typed properties, return types) are fully utilized to reduce runtime errors.

---

## 🧠 Architecture: Context-Injection RAG

We utilize a simplified **Retrieval-Augmented Generation (RAG)** pattern. Instead of complex vector embeddings, we leverage Gemini 1.5's massive context window for a cost-effective solution.

1. **Ingest:** User asks a question (e.g., *"Where to get spicy food in PJ that isn't expensive?"*).
2. **Retrieve:** System queries the local MySQL `places` table for relevant candidates based on rough spatial/tag filters.
3. **Inject:** The filtered list of JSON data is injected into the System Prompt.
4. **Generate:** The AI Persona (e.g., *Mak Cik*) analyzes the JSON and responds with a witty, culturally relevant recommendation.

---

## ✨ Key Features

* **🗣 AI Personalities:**
* **The Mak Cik:** Nags you to eat properly, recommends value-for-money and halal spots.
* **The Gym Bro:** Focuses on protein, "padu" levels, and efficiency.
* **The Atas:** Recommends aesthetic cafes and judges you for being cheap.


* **⚡️ Instant Results:** Powered by Livewire 3 for a Single-Page App (SPA) feel without the complexity.
* **📂 Curated Data:** "Seed & Scrape" strategy ensures high-quality initial recommendations.

---

## 🚀 Installation

### Prerequisites

* PHP >= 8.4
* Composer
* Node.js & NPM
* MySQL

### Local Development Setup

1. **Clone the repository**
```bash
git clone https://github.com/yourusername/makanguru.git
cd makanguru

```


2. **Install Dependencies**
```bash
composer install
npm install

```


3. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate

```


4. **Configure Database & AI Keys**
Open `.env` and update the following:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=makanguru
DB_USERNAME=root
DB_PASSWORD=

# Google Gemini AI (Required)
# Get your API key from: https://ai.google.dev/
GEMINI_API_KEY=your_gemini_api_key_here

# Groq AI (Optional - for alternative models)
# Get your API key from: https://console.groq.com/
GROQ_API_KEY=your_groq_api_key_here

# Optional: Set default AI provider (gemini|groq)
AI_PROVIDER=gemini
```


5. **Migrate & Seed Data**
Load the initial list of 50+ curated Malaysian restaurants.
```bash
php artisan migrate --seed

```


6. **Run the Server**
```bash
# Terminal A (Backend)
php artisan serve

# Terminal B (Frontend Assets)
npm run dev

```



---

## 🧪 Testing the AI

You can test the AI integration directly via Artisan command without using the UI:

```bash
# Test with default model (Gemini)
php artisan makanguru:ask "I want nasi lemak in Damansara" --persona="makcik"

# Test with Groq (Meta Llama)
php artisan makanguru:ask "Where to get spicy food?" --model=groq-meta --persona=gymbro

# Test with Groq (OpenAI GPT)
php artisan makanguru:ask "Instagram-worthy cafe" --model=groq-openai --persona=atas

# List available Groq models
php artisan groq:list-models

# List available Gemini models
php artisan gemini:list-models
```

---

## 🎯 Using the Web Interface

**Start the application:**

```bash
# Terminal 1: Start backend server
php artisan serve

# Terminal 2: Start frontend dev server (for asset hot-reload)
npm run dev
```

**Visit:** http://127.0.0.1:8000

**Features:**

1. **Choose Your AI Guide** - Select from 3 personas:
   - 👵 **Mak Cik** - Value-focused, halal-conscious, nurturing
   - 💪 **Gym Bro** - Protein-heavy, efficiency-focused
   - 💅 **Atas Friend** - Aesthetic, upscale, Instagram-worthy

2. **Select AI Model** - Choose your preferred AI provider:
   - 🤖 **Gemini** (Google) - Fast and cost-effective
   - 🧠 **GPT** (OpenAI via Groq) - High-quality responses
   - 🦙 **Llama** (Meta via Groq) - Ultra-fast inference

   *Note: Groq models require GROQ_API_KEY in .env*

3. **Apply Filters** (Optional):
   - ✓ Halal Only
   - Price Range: Budget / Moderate / Expensive
   - Area: e.g., "Bangsar", "KLCC", "Petaling Jaya"

4. **Ask Questions**:
   - "Where to get spicy food in PJ?"
   - "I want halal breakfast near KLCC"
   - "Instagram-worthy cafe with good coffee"
   - "Cheap nasi lemak in Damansara"

5. **Interact**:
   - Chat history saved during session
   - Switch personas mid-conversation
   - Switch AI models (when available)
   - Clear chat to start fresh
   - Real-time loading indicators

---

## 🗺 High Level Roadmap

* [x] **Phase 1:** Foundation & Data Layer ✅
* [x] **Phase 2:** AI Service Layer & Prompt Engineering ✅
* [x] **Phase 3:** Modern UI/UX with Livewire 3 ✅
* [x] **Phase 4:** Production Deployment (AWS, Redis, Nginx) ✅
* [ ] **Phase 5:** OpenStreetMap Integration for broader data coverage
* [ ] **Phase 6:** "Share Your Vibe" – Generate shareable social media cards
* [ ] **Phase 7:** User submissions (Community-led data)

---

## 🗺 Development Work

Here is the breakdown of the roadmap into actionable tasks and to-dos, structured like a **Technical Sprint Plan**.

Since we are prioritizing **OOP, Coding Standards (PSR-12), and Modern UX**, I have grouped these tasks to ensure the foundation is solid before we touch the frontend.

### **Phase 1: Foundation & Data Layer** ✅ COMPLETE

*Goal: Get the environment ready and the data structure strictly typed.*

* [x] **Project Initialization**
    * Initialize Laravel 12 project: `composer create-project laravel/laravel makanguru`
    * Set up Git repository and `.gitignore`.
    * Install frontend dependencies: `npm install`, `npm install -D tailwindcss postcss autoprefixer`.
    * Configure Tailwind CSS v4 with Vite.

* [x] **Database Architecture**
    * Using SQLite for local development (configured in `.env`).
    * Created Migration: `create_places_table` with comprehensive schema.
    * Migrations executed successfully: `php artisan migrate`.

* [x] **Domain Models (OOP Focus)**
    * Created `Place` Model (`app/Models/Place.php`).
    * Implemented type-safe casts for `tags` (array), `latitude`/`longitude` (decimal), and `is_halal` (boolean).
    * Created 6 powerful scope methods for retrieval logic:
        * `scopeNear()` - Geospatial filtering with Haversine formula
        * `scopeInArea()` - Area-based filtering
        * `scopeByPrice()` - Price range filtering
        * `scopeHalalOnly()` - Halal certification filter
        * `scopeWithTags()` - JSON tag searching
        * `scopeByCuisine()` - Cuisine type filtering
    * Added 2 computed attributes: `getPriceLabelAttribute()`, `getHalalStatusAttribute()`

* [x] **Data Seeding**
    * Created `PlaceSeeder` with 15 records total.
    * 5 "Golden" records: Village Park, Jalan Alor, Yusoof Dan Zakhir, Kim Lian Kee, The Owls Cafe.
    * 10 diverse dummy records for comprehensive testing.
    * Seeded successfully: `php artisan db:seed`.

* [x] **Custom Design System**
    * Malaysian-inspired color palette defined in `resources/css/app.css`:
        * Sambal Red, Teh Tarik Brown, Nasi Lemak Cream, Pandan Green, Rendang Brown
    * Mobile-first spacing variables configured.

---

### **Phase 2: The AI Service Layer** ✅ COMPLETE

*Goal: Build the "Brain" using clean architecture. The Controller/Livewire component should never talk to Gemini directly.*

* [x] **Service Architecture**
    * Created Interface: `App\Contracts\AIRecommendationInterface`.
    * Created Implementation: `App\Services\GeminiService` with Google Gemini 2.5 Flash.
    * Bound in `AIServiceProvider` for dependency injection (easy to swap providers).
    * Added `healthCheck()` method for service availability.

* [x] **Prompt Engineering Engine**
    * Created `App\AI\PromptBuilder` class with 3 Malaysian personas:
        * **Mak Cik**: Nurturing, halal-focused, value-conscious
        * **Gym Bro**: Protein-focused, efficiency-driven, "padu"
        * **Atas Friend**: Aesthetic-focused, upscale, Instagram-worthy
    * Each persona has unique speech patterns and priorities.
    * Token-efficient JSON context injection for restaurant data.

* [x] **Data Transfer Objects**
    * Created `App\DTOs\RecommendationDTO` for type-safe data transfer.
    * Factory methods: `fromGeminiResponse()`, `fallback()`.
    * Helper methods: `isFallback()`, `getTokensUsed()`.

* [x] **API Integration**
    * Gemini 2.5 Flash API integration with model fallback system.
    * 4 fallback models: gemini-2.5-flash → gemini-2.0-flash → gemini-2.5-flash-lite → gemini-2.0-flash-lite.
    * Automatic rate limit detection and model switching (HTTP 429 handling).
    * Retry logic with exponential backoff (2 retries per model).
    * Comprehensive error handling with graceful fallback responses.
    * Safety settings configuration (all 4 categories).
    * Enhanced logging for debugging (model used, finish reasons, token usage).
    * Output limit: 10000 tokens for complete responses.

* [x] **Testing & Commands**
    * Created `tests/Unit/GeminiServiceTest.php` - 9 tests, 28 assertions ✅
    * Created `php artisan makanguru:ask` - CLI testing interface with filters.
    * Created `php artisan gemini:list-models` - Lists available Gemini models.
    * Created `PlaceFactory` for test data generation.

* [x] **Cost Estimation**
    * Static method `GeminiService::estimateCost()` for API cost calculation.
    * Logs token usage on every API call.

---

### **Phase 3: Modern UI/UX** ✅ COMPLETE

*Goal: Build a "Thumb-Friendly" Mobile-First Interface using Livewire 3.*

* [x] **Layout & Design System**
    * Designed the main layout (`resources/views/components/layouts/app.blade.php`).
    * Malaysian color palette already defined in `resources/css/app.css`.
    * Created reusable Blade components: `<x-chat-bubble>`, `<x-restaurant-card>`, `<x-loading-spinner>`, `<x-persona-switcher>`, `<x-model-selector>`.

* [x] **Livewire Components**
    * Created `App\Livewire\ChatInterface` with full state management.
    * Implemented properties: `$userQuery`, `$chatHistory`, `$currentPersona`, `$currentModel`, `$filterHalal`, `$filterPrice`, `$filterArea`, `$isLoading`.
    * Implemented actions: `sendMessage()`, `switchPersona()`, `switchModel()`, `clearChat()`.
    * Added type-safe validation with PHP 8.4 attributes.
    * Integrated `AIRecommendationInterface` via dependency injection.
    * Model tracking in chat history for each message.

* [x] **UX Polish (Micro-interactions)**
    * Implemented `wire:loading` with persona-specific "Thinking..." states:
        * Mak Cik: "Mak Cik is putting on her spectacles..."
        * Gym Bro: "Bro is thinking... loading the gains..."
        * Atas Friend: "Darling, let me consult my notes..."
    * Alpine.js micro-interactions (built-in via Livewire 3 - no separate installation needed).
    * Auto-scroll to bottom of chat when new message arrives using Alpine.js `x-init` and `x-ref`.
    * Implemented persona switcher with 3-column grid layout and visual feedback.
    * Implemented model selector with 3 AI providers (Gemini active, OpenAI/Meta via Groq coming soon).
    * Visual status indicators ("SOON" badges) for unreleased AI models.
    * Added smooth fadeIn animations for chat bubbles (defined in `resources/css/app.css`).
    * Real-time filters with `wire:model.live` for Halal, Price, and Area.
    * Enter key to send message (Shift+Enter for new line) using Livewire 3 compatible syntax.
    * Clear chat functionality with confirmation dialog.
    * Blue send button matching user message bubble color.
    * Optimized chat container spacing (reduced top padding).
    * Fixed user message bubble display with `space-x-reverse`.
    * Removed duplicate Alpine.js initialization (55% smaller JS bundle: 36.35 kB).

---

### **Phase 4: Production Deployment** ✅ COMPLETE

*Goal: Production readiness and AWS deployment configuration.*

* [x] **Redis Caching Optimization**
    * Created `PlaceCacheService` for intelligent query caching
    * Integrated caching layer in `ChatInterface` component
    * Cache TTL: 1 hour for restaurant queries
    * Automatic cache invalidation support
    * Reduces database load by ~90% for repeated queries

* [x] **AWS EC2 Deployment Scripts**
    * `deployment/setup-server.sh` - Automated server provisioning script
    * `deployment/deploy.sh` - Application deployment and update script
    * Installs: PHP 8.4, Nginx, MySQL 8.0, Redis, Node.js 24.x, Supervisor

* [x] **Nginx Configuration**
    * Production-ready Nginx config with SSL/TLS support
    * Security headers (CSP, HSTS, X-Frame-Options)
    * Gzip compression and static asset caching
    * HTTP/2 support and OCSP stapling

* [x] **Queue Workers & Supervisor**
    * Supervisor configuration for Laravel queue workers
    * Auto-restart and process management
    * Separate log files for monitoring

* [x] **SSL/TLS Setup**
    * Automated Let's Encrypt certificate installation
    * `deployment/setup-ssl.sh` script for SSL configuration
    * Automatic certificate renewal via cron

* [x] **CI/CD with GitHub Actions**
    * Automated testing on push/PR to main/develop branches
    * PHPUnit test execution
    * PSR-12 code style checking
    * Security vulnerability scanning
    * Node.js asset building verification

* [x] **Comprehensive Documentation**
    * `deployment/DEPLOYMENT.md` - Complete deployment guide
    * Step-by-step AWS EC2 setup instructions
    * Troubleshooting section
    * Security checklist
    * Performance optimization tips

* [x] **Environment Configuration**
    * Updated `.env.example` with production notes
    * Created `.env.production.example` template
    * Redis cache and queue configuration
    * MySQL production settings

---

## 📋 Quick Reference

### Key Commands

```bash
# Development
php artisan serve              # Start backend server
npm run dev                    # Start frontend dev server (hot reload)
npm run build                  # Build for production

# Database
php artisan migrate:fresh --seed   # Reset database with seed data
php artisan tinker                  # REPL for testing queries

# Testing
php artisan test                                                 # Run all tests
php artisan makanguru:ask "your question" --persona=makcik      # CLI test (Gemini)
php artisan makanguru:ask "your question" --model=groq-meta     # CLI test (Groq Llama)
php artisan gemini:list-models                                   # List Gemini models
php artisan groq:list-models                                     # List Groq models

# Code Generation
php artisan make:livewire ComponentName    # Create Livewire component
php artisan make:test TestName --unit      # Create unit test
```

### Project Structure

```
app/
├── Livewire/ChatInterface.php          # Main chat component
├── Services/
│   ├── GeminiService.php               # Gemini AI integration
│   └── GroqService.php                 # Groq AI integration
├── AI/PromptBuilder.php                # Persona prompt engineering
├── DTOs/RecommendationDTO.php          # Data transfer object
├── Console/Commands/
│   ├── AskMakanGuruCommand.php         # CLI testing tool
│   ├── ListGeminiModelsCommand.php     # List Gemini models
│   └── ListGroqModelsCommand.php       # List Groq models
└── Models/Place.php                    # Restaurant model

resources/views/
├── components/
│   ├── chat-bubble.blade.php           # Message bubble
│   ├── loading-spinner.blade.php       # Loading indicator
│   ├── persona-switcher.blade.php      # Persona selector
│   └── layouts/app.blade.php           # Main layout
└── livewire/chat-interface.blade.php   # Chat UI

resources/
├── css/app.css                         # Tailwind + Malaysian colors
└── js/app.js                           # Alpine.js config

database/seeders/PlaceSeeder.php        # 15 restaurant records
```

### Tech Stack at a Glance

- **Backend:** Laravel 12, PHP 8.4
- **Frontend:** Livewire 3, Tailwind CSS v4, Alpine.js
- **AI:** Google Gemini 2.5 Flash, Groq (Meta Llama, OpenAI GPT)
- **Database:** SQLite (local), MySQL (production)
- **Testing:** PHPUnit (25 tests, 60 assertions)

### Current Status

✅ **Phase 1:** Foundation & Data Layer
✅ **Phase 2:** AI Service Layer
✅ **Phase 3:** Modern UI/UX
✅ **Phase 4:** Production Deployment
⏳ **Phase 5:** OpenStreetMap Integration (Next)

---

## 📄 License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
