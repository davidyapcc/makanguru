# MakanGuru Documentation

Welcome to the MakanGuru documentation! This directory contains comprehensive guides, implementation notes, and technical documentation for the project.

---

## 📚 Documentation Structure

### Root Documentation
- **[README.md](../README.md)** - Main project overview, installation, and quick start guide
- **[CLAUDE.md](../CLAUDE.md)** - Technical documentation for AI assistants working on the project
- **[TEST_COVERAGE_SUMMARY.md](TEST_COVERAGE_SUMMARY.md)** - Comprehensive test coverage documentation (201 tests, 99.0% pass rate)

### User Guides (`docs/guides/`)
Step-by-step guides for users and developers to utilize MakanGuru's features:

- **[Docker Setup Guide](guides/DOCKER_SETUP.md)** - Complete Docker development environment guide
  - Quick start with Docker
  - Service configuration
  - Daily workflow commands
  - Troubleshooting Docker issues

- **[Rate Limiting Guide](guides/RATE_LIMITING.md)** - Comprehensive guide on chat rate limiting
  - Configuration options
  - Session-based tracking
  - Persona-specific feedback
  - Troubleshooting

- **[Scraper CLI Guide](guides/SCRAPER_GUIDE.md)** - Command-line scraper documentation
  - CLI commands and options
  - OpenStreetMap integration
  - Data validation and import

- **[Scraper Web UI Guide](guides/SCRAPER_UI_GUIDE.md)** - Web interface scraper documentation
  - Interactive web interface
  - Preview mode
  - Visual results and statistics

### Implementation Notes (`docs/implementation/`)
Historical implementation documentation and phase completion reports:

- **[Phase 5 Complete](implementation/PHASE5_COMPLETE.md)** - OpenStreetMap integration implementation summary
- **[Scraper Web UI Complete](implementation/SCRAPER_WEB_UI_COMPLETE.md)** - Scraper web interface implementation details

---

## 🎯 Quick Links

### Getting Started
1. Read [README.md](../README.md) for installation and setup
2. Configure your `.env` file with API keys
3. Run `php artisan migrate:fresh --seed` to set up the database
4. Start the development server with `php artisan serve`

### For Developers
1. Review [CLAUDE.md](../CLAUDE.md) for technical architecture
2. Check [Rate Limiting Guide](guides/RATE_LIMITING.md) for chat configuration
3. Use [Scraper Guides](guides/) for data import workflows

### For AI Assistants
- **Primary**: [CLAUDE.md](../CLAUDE.md) contains all technical context
- **Best Practices**: Follow PSR-12, SOLID principles, type safety
- **Testing**: All features have comprehensive test coverage

---

## 📖 Documentation by Feature

### Chat Interface & AI
- Configuration: [CLAUDE.md](../CLAUDE.md) - Phase 3 section
- Rate Limiting: [Rate Limiting Guide](guides/RATE_LIMITING.md)
- Personas: [README.md](../README.md) - AI Personalities section

### Data Management
- Scraping (CLI): [Scraper CLI Guide](guides/SCRAPER_GUIDE.md)
- Scraping (Web): [Scraper Web UI Guide](guides/SCRAPER_UI_GUIDE.md)
- Database: [CLAUDE.md](../CLAUDE.md) - Database Schema section

### Deployment
- Local Development: [README.md](../README.md) - Installation section
- Production Deployment: [CLAUDE.md](../CLAUDE.md) - Phase 4 section
- AWS EC2: `deployment/DEPLOYMENT.md`

---

## 🔍 Finding Documentation

### By Topic

**API Integration**
- Gemini AI: [CLAUDE.md](../CLAUDE.md) - API Integration Notes
- Groq AI: [CLAUDE.md](../CLAUDE.md) - Groq API Integration
- Rate Limiting: [guides/RATE_LIMITING.md](guides/RATE_LIMITING.md)

**Frontend**
- Livewire Components: [CLAUDE.md](../CLAUDE.md) - Phase 3
- Tailwind CSS: [CLAUDE.md](../CLAUDE.md) - Design System
- Malaysian Color Palette: [CLAUDE.md](../CLAUDE.md) - Color Palette

**Backend**
- Database Models: [CLAUDE.md](../CLAUDE.md) - Models section
- Services: [CLAUDE.md](../CLAUDE.md) - Service Architecture
- Testing: [TEST_COVERAGE_SUMMARY.md](TEST_COVERAGE_SUMMARY.md) - Comprehensive test documentation

**Data Import**
- Restaurant Scraping: [guides/SCRAPER_GUIDE.md](guides/SCRAPER_GUIDE.md)
- OpenStreetMap: [implementation/PHASE5_COMPLETE.md](implementation/PHASE5_COMPLETE.md)

### By Phase

- **Phase 1**: Foundation & Data Layer → [CLAUDE.md](../CLAUDE.md)
- **Phase 2**: AI Service Layer → [CLAUDE.md](../CLAUDE.md)
- **Phase 3**: Modern UI/UX → [CLAUDE.md](../CLAUDE.md) + [guides/RATE_LIMITING.md](guides/RATE_LIMITING.md)
- **Phase 4**: Production Deployment → [CLAUDE.md](../CLAUDE.md) + `deployment/`
- **Phase 5**: OpenStreetMap Integration → [implementation/PHASE5_COMPLETE.md](implementation/PHASE5_COMPLETE.md)

---

## 📝 Contributing to Documentation

When adding new documentation:

1. **User Guides** → `docs/guides/`
   - How-to guides
   - Configuration tutorials
   - Feature walkthroughs

2. **Implementation Notes** → `docs/implementation/`
   - Phase completion reports
   - Technical implementation details
   - Historical records

3. **API Documentation** → Update `CLAUDE.md`
   - Service interfaces
   - Database schemas
   - Architecture patterns

4. **User-Facing Docs** → Update `README.md`
   - Installation steps
   - Quick start guides
   - Feature highlights

---

## 🔗 External Resources

- **Laravel 12**: https://laravel.com/docs/12.x
- **Livewire 3**: https://livewire.laravel.com/docs/3.x
- **Tailwind CSS v4**: https://tailwindcss.com/docs
- **Google Gemini AI**: https://ai.google.dev/
- **Groq Cloud**: https://console.groq.com/
- **OpenStreetMap**: https://www.openstreetmap.org/

---

## 📊 Documentation Statistics

- **Total Markdown Files**: 10
- **User Guides**: 4
- **Implementation Notes**: 2
- **Root Documentation**: 3
- **Test Documentation**: 1
- **Total Lines**: ~6,000+ lines of documentation

---

*Last Updated: 2024-12-24*
*Maintained by: MakanGuru Team*
