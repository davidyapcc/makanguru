# Docker Setup Guide for MakanGuru

This guide covers setting up MakanGuru for local development using Docker. Docker provides a consistent development environment with production parity, eliminating "it works on my machine" issues.

---

## Table of Contents

- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Docker Services](#docker-services)
- [Detailed Setup](#detailed-setup)
- [Daily Workflow](#daily-workflow)
- [Common Commands](#common-commands)
- [Troubleshooting](#troubleshooting)
- [Advanced Configuration](#advanced-configuration)
- [Switching from SQLite to Docker MySQL](#switching-from-sqlite-to-docker-mysql)

---

## Prerequisites

### Required Software

1. **Docker Desktop** (Recommended)
   - **macOS**: [Docker Desktop for Mac](https://docs.docker.com/desktop/install/mac-install/)
   - **Windows**: [Docker Desktop for Windows](https://docs.docker.com/desktop/install/windows-install/)
   - **Linux**: [Docker Engine](https://docs.docker.com/engine/install/)

2. **Docker Compose**
   - Included with Docker Desktop
   - Linux users: Install separately via package manager

3. **Minimum System Requirements**
   - CPU: 2+ cores
   - RAM: 4GB+ (8GB recommended)
   - Disk: 10GB+ free space

### Verify Installation

```bash
# Check Docker version
docker --version
# Expected: Docker version 20.10+ or higher

# Check Docker Compose version
docker compose version
# Expected: Docker Compose version v2.0+ or higher

# Verify Docker is running
docker ps
# Expected: Empty list or existing containers (no errors)
```

---

## Quick Start

For first-time setup, follow these steps:

```bash
# 1. Clone the repository (if not already done)
git clone https://github.com/yourusername/makanguru.git
cd makanguru

# 2. Copy Docker environment file
cp .env.docker .env

# 3. Configure API keys in .env (REQUIRED)
# Edit .env and set:
#   GROQ_API_KEY=your_groq_api_key_here
#   GEMINI_API_KEY=your_actual_api_key_here (optional/fallback)

# 4. Start Docker services
docker compose up -d

# 5. Run initialization script
bash docker/init.sh

# 6. Access the application
# Open http://localhost:8080 in your browser
```

That's it! Your MakanGuru application should now be running.

---

## Docker Services

MakanGuru uses 6 Docker services:

| Service | Description | Port | Image |
|---------|-------------|------|-------|
| **mysql** | MySQL 8.0 database | 3307→3306 | mysql:8.0 |
| **redis** | Redis cache & queue backend | 6380→6379 | redis:7-alpine |
| **app** | PHP 8.4-FPM application | - | Custom (see Dockerfile) |
| **nginx** | Nginx web server | 8080→80 | nginx:alpine |
| **queue** | Laravel queue worker | - | Custom (same as app) |
| **node** | Node.js for asset building | - | node:24-alpine |

### Service Dependencies

```
nginx ──┬─→ app ──┬─→ mysql
        │         └─→ redis
        └─→ queue ─→ redis
```

---

## Detailed Setup

### Step 1: Environment Configuration

Create your `.env` file from the Docker template:

```bash
cp .env.docker .env
```

**Important Variables to Configure:**

```ini
# Application
APP_NAME=MakanGuru
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database (Docker MySQL)
DB_CONNECTION=mysql
DB_HOST=mysql          # Docker service name
DB_PORT=3306
DB_DATABASE=makanguru
DB_USERNAME=makanguru
DB_PASSWORD=makanguru_secret

# Cache & Queue (Docker Redis)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=redis       # Docker service name
REDIS_PORT=6379

# AI Configuration (REQUIRED)
GROQ_API_KEY=your_groq_api_key_here
GEMINI_API_KEY=your_gemini_api_key_here

# Chat Settings
CHAT_RATE_LIMIT_MAX=5
CHAT_RATE_LIMIT_WINDOW=60
CHAT_DEFAULT_PERSONA=makcik
CHAT_DEFAULT_MODEL=groq-openai
```

**Get API Keys:**
- Gemini: https://ai.google.dev/
- Groq: https://console.groq.com/

### Step 2: Start Docker Services

```bash
# Start all services in detached mode
docker compose up -d

# Check service status
docker compose ps

# Expected output:
# NAME                STATUS              PORTS
# makanguru-app       Up 30 seconds
# makanguru-mysql     Up 30 seconds       0.0.0.0:3307->3306/tcp
# makanguru-nginx     Up 30 seconds       0.0.0.0:8080->80/tcp
# makanguru-queue     Up 30 seconds
# makanguru-redis     Up 30 seconds       0.0.0.0:6380->6379/tcp
# (Node service only starts if --profile dev is used)
```

### Step 3: Initialize Application

Run the initialization script:

```bash
bash docker/init.sh
```

This script will:
1. ✅ Copy `.env.docker` to `.env` (if not exists)
2. ✅ Generate Laravel application key
3. ✅ Wait for MySQL to be ready
4. ✅ Publish Livewire assets
5. ✅ Run database migrations and seeders
6. ✅ Cache configuration, routes, and views
7. ✅ Install Node.js dependencies
8. ✅ Build frontend assets
9. ✅ Set proper permissions

**Expected Output:**
```
=========================================
MakanGuru Docker Initialization
=========================================

✓ .env file created
✓ Application key generated
✓ MySQL is ready
✓ Database migrations completed
✓ Application optimized
✓ Frontend assets built
✓ Permissions set

=========================================
✓ Initialization Complete!
=========================================

Access your application at: http://localhost:8080
```

### Step 4: Verify Installation

1. **Check Services are Running:**
   ```bash
   docker compose ps
   # All services should show "Up" status
   ```

2. **Access the Application:**
   - Open browser: http://localhost:8080
   - You should see the MakanGuru chat interface

3. **Test Database Connection:**
   ```bash
   docker compose exec app php artisan tinker
   >>> \DB::connection()->getPdo();
   # Should return PDO object (no errors)
   >>> App\Models\Place::count();
   # Should return 15 (seeded restaurants)
   ```

4. **Test Redis Connection:**
   ```bash
   docker compose exec app php artisan tinker
   >>> \Illuminate\Support\Facades\Redis::connection()->ping();
   # Should return "+PONG"
   ```

---

## Daily Workflow

### Starting Your Development Session

```bash
# Start backend services
docker compose up -d

# OR: Start all services including Node.js (for frontend development)
docker compose --profile dev up -d

# Watch frontend assets for changes (requires node service running)
docker compose logs -f node
```

### Stopping Your Development Session

```bash
# Stop all services (keeps data)
docker compose stop

# Stop and remove containers (keeps data)
docker compose down

# Stop and remove everything including volumes (⚠️ DESTROYS DATA)
docker compose down -v
```

### Viewing Logs

```bash
# View all service logs
docker compose logs -f

# View specific service logs
docker compose logs -f app      # Application logs
docker compose logs -f nginx    # Web server logs
docker compose logs -f mysql    # Database logs
docker compose logs -f queue    # Queue worker logs
```

---

## Common Commands

### Application Commands

```bash
# Access app container shell
docker compose exec app bash

# Run Artisan commands
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan tinker
docker compose exec app php artisan test

# Clear caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# Optimize application
docker compose exec app php artisan optimize
```

### Database Commands

```bash
# Access MySQL shell
docker compose exec mysql mysql -u makanguru -pmakanguru_secret makanguru

# Run migrations
docker compose exec app php artisan migrate

# Fresh migrations with seeding
docker compose exec app php artisan migrate:fresh --seed

# Create database backup
docker compose exec mysql mysqldump -u makanguru -pmakanguru_secret makanguru > backup.sql

# Restore database backup
docker compose exec -T mysql mysql -u makanguru -pmakanguru_secret makanguru < backup.sql
```

### Redis Commands

```bash
# Access Redis CLI
docker compose exec redis redis-cli

# Common Redis commands (once in CLI)
PING           # Test connection
KEYS *         # List all keys
FLUSHALL       # Clear all data
```

### Frontend Commands

```bash
# Install npm dependencies
docker compose run --rm node npm install

# Start development server
docker compose run --rm node npm run dev

# Build assets for production
docker compose run --rm node npm run build

# Watch for file changes
docker compose run --rm node npm run dev -- --watch
```

### MakanGuru-Specific Commands

```bash
# Test AI recommendation (Mak Cik persona)
docker compose exec app php artisan makanguru:ask "Where to get spicy food in PJ?" --persona=makcik

# Scrape restaurants from OpenStreetMap
docker compose exec app php artisan makanguru:scrape --area="KLCC" --radius=5000 --limit=100

# List available Gemini models
docker compose exec app php artisan gemini:list-models

# List available Groq models
docker compose exec app php artisan groq:list-models
```

---

## Troubleshooting

### Service Won't Start

**Symptom:** `docker compose up -d` fails or service keeps restarting

**Solution:**
```bash
# Check service logs
docker compose logs -f [service_name]

# Common issues:
# 1. Port already in use
sudo lsof -i :8080  # Check what's using port 8080
# Kill the process or change port in docker compose.yml

# 2. Insufficient resources
# Increase Docker Desktop memory/CPU in settings

# 3. Corrupted volumes
docker compose down -v  # ⚠️ Destroys data
docker compose up -d
bash docker/init.sh
```

### MySQL Connection Refused

**Symptom:** `SQLSTATE[HY000] [2002] Connection refused`

**Solution:**
```bash
# 1. Verify MySQL is running
docker compose ps mysql
# Should show "Up" status

# 2. Wait for MySQL to be fully ready
docker compose logs mysql | grep "ready for connections"
# You should see this message twice

# 3. Restart MySQL service
docker compose restart mysql

# 4. Check credentials in .env
# DB_HOST=mysql (not 127.0.0.1)
# DB_USERNAME and DB_PASSWORD match docker compose.yml
```

### Redis Connection Refused

**Symptom:** `Connection refused [tcp://redis:6379]`

**Solution:**
```bash
# 1. Verify Redis is running
docker compose ps redis

# 2. Test Redis connection
docker compose exec redis redis-cli PING
# Should return "PONG"

# 3. Check .env configuration
# REDIS_HOST=redis (not 127.0.0.1)

# 4. Restart Redis
docker compose restart redis
```

### Permission Denied Errors

**Symptom:** `Permission denied` when accessing storage or cache

**Solution:**
```bash
# Fix permissions inside container
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache

# On Linux/macOS host:
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Application Key Not Set

**Symptom:** `No application encryption key has been specified`

**Solution:**
```bash
# Generate new key
docker compose exec app php artisan key:generate

# Clear config cache
docker compose exec app php artisan config:clear
```

### Frontend Assets Not Building

**Symptom:** Blank page or missing styles

**Solution:**
```bash
# Rebuild assets
docker compose exec node npm install
docker compose exec node npm run build

# Clear browser cache
# Hard refresh: Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)

# Check Vite errors
docker compose logs -f node
```

### Database Migration Errors

**Symptom:** `Migration table not found` or migration failures

**Solution:**
```bash
# Reset database completely
docker compose exec app php artisan migrate:fresh --seed

# If that fails, reset MySQL data
docker compose down
docker volume rm makanguru_mysql_data
docker compose up -d
bash docker/init.sh
```

### Queue Worker Not Processing Jobs

**Symptom:** Jobs stuck in queue

**Solution:**
```bash
# Check queue worker logs
docker compose logs -f queue

# Restart queue worker
docker compose restart queue

# Clear failed jobs
docker compose exec app php artisan queue:flush
```

---

## Advanced Configuration

### Changing Ports

Edit `docker compose.yml`:

```yaml
services:
  nginx:
    ports:
      - "9000:80"  # Change from 8080 to 9000

  mysql:
    ports:
      - "3308:3306"  # Change from 3307 to 3308
```

Then restart services:
```bash
docker compose down
docker compose up -d
```

### Adding More Queue Workers

Edit `docker compose.yml`:

```yaml
services:
  queue:
    deploy:
      replicas: 3  # Run 3 queue workers instead of 1
```

### Customizing PHP Configuration

Edit `docker/php/php.ini`:

```ini
memory_limit = 512M              # Increase from 256M
max_execution_time = 600         # Increase from 300
upload_max_filesize = 50M        # Increase from 20M
```

Rebuild and restart:
```bash
docker compose build app
docker compose restart app
```

### Customizing MySQL Configuration

Edit `docker/mysql/my.cnf`:

```ini
innodb_buffer_pool_size = 512M   # Increase from 256M
max_connections = 200            # Increase from 100
```

Restart MySQL:
```bash
docker compose restart mysql
```

### Using Different MySQL Version

Edit `docker compose.yml`:

```yaml
services:
  mysql:
    image: mysql:8.4  # or mysql:5.7
```

Recreate container:
```bash
docker compose down
docker compose up -d
bash docker/init.sh
```

---

## Switching from SQLite to Docker MySQL

If you've been using SQLite and want to switch to Docker MySQL:

### Option 1: Fresh Start (Recommended)

```bash
# 1. Backup your .env if you have custom settings
cp .env .env.backup

# 2. Copy Docker environment
cp .env.docker .env

# 3. Restore custom settings (API keys, etc.)
# Edit .env and copy GEMINI_API_KEY, etc. from .env.backup

# 4. Start Docker services
docker compose up -d

# 5. Initialize application
bash docker/init.sh

# Done! Your app now uses MySQL
```

### Option 2: Migrate Existing Data

```bash
# 1. Export SQLite data
sqlite3 database/database.sqlite .dump > sqlite_backup.sql

# 2. Start Docker MySQL
docker compose up -d mysql

# 3. Wait for MySQL to be ready
docker compose logs mysql | grep "ready for connections"

# 4. Import data (manual conversion needed)
# SQLite SQL syntax differs from MySQL
# Recommend using fresh migrations instead
docker compose exec app php artisan migrate:fresh --seed

# 5. Manually re-add custom data via Tinker or admin panel
```

### Verify MySQL is Being Used

```bash
# Check database connection
docker compose exec app php artisan tinker
>>> config('database.default');
# Should return "mysql"

>>> \DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
# Should return "mysql"

# Check tables
docker compose exec mysql mysql -u makanguru -pmakanguru_secret makanguru -e "SHOW TABLES;"
```

---

## Performance Tips

1. **Allocate More Resources to Docker:**
   - Docker Desktop → Settings → Resources
   - Increase CPUs to 4+
   - Increase Memory to 8GB+

2. **Use Volume Caching (macOS):**
   ```yaml
   volumes:
     - ./:/var/www/html:cached  # Improves macOS performance
   ```

3. **Prune Docker Resources Regularly:**
   ```bash
   docker system prune -a  # Remove unused images
   docker volume prune     # Remove unused volumes
   ```

4. **Use Production-Optimized Images:**
   ```bash
   docker compose exec app php artisan optimize
   docker compose run --rm node npm run build
   ```

---

## Security Notes

1. **Never commit `.env` file to Git**
   - Contains sensitive API keys and passwords
   - Already in `.gitignore`

2. **Change default passwords in production:**
   ```ini
   DB_PASSWORD=use_a_strong_random_password_here
   ```

3. **Keep Docker images updated:**
   ```bash
   docker compose pull  # Pull latest images
   docker compose up -d --build  # Rebuild with updates
   ```

---

## Additional Resources

- **Docker Documentation**: https://docs.docker.com/
- **Docker Compose Documentation**: https://docs.docker.com/compose/
- **Laravel Documentation**: https://laravel.com/docs/12.x
- **MakanGuru Main README**: [../../README.md](../../README.md)
- **MakanGuru Technical Docs**: [../../CLAUDE.md](../../CLAUDE.md)

---

## Getting Help

If you encounter issues not covered in this guide:

1. **Check service logs:**
   ```bash
   docker compose logs -f [service_name]
   ```

2. **Search Docker issues:**
   - GitHub: https://github.com/docker/compose/issues

3. **Laravel-specific issues:**
   - Check `storage/logs/laravel.log`

4. **Report MakanGuru bugs:**
   - Create an issue in the project repository

---

*Last Updated: 2025-12-22*
*Maintained by: MakanGuru Team*
