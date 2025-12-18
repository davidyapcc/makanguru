# MakanGuru - Deployment Files

This directory contains all production deployment scripts and configurations for deploying MakanGuru to AWS EC2.

## Quick Start

For first-time deployment, follow these steps in order:

1. **Provision EC2 instance** (Ubuntu 24.04 LTS)
2. **Run server setup**: `sudo bash setup-server.sh`
3. **Clone repository** to `/var/www/makanguru`
4. **Configure environment**: Copy `.env.production.example` to `.env`
5. **Deploy application**: `sudo bash deploy.sh`
6. **Configure Nginx**: Copy `nginx.conf` to `/etc/nginx/sites-available/`
7. **Set up SSL**: `sudo bash setup-ssl.sh your-domain.com`
8. **Configure queue workers**: Copy `supervisor.conf` to `/etc/supervisor/conf.d/`

For detailed instructions, see [DEPLOYMENT.md](./DEPLOYMENT.md)

## Files Overview

### Server Setup

**`setup-server.sh`** - Initial server provisioning
- Installs PHP 8.4, Nginx, MySQL 8.0, Redis, Node.js, Supervisor
- Configures PHP-FPM and system packages
- Sets up firewall rules
- **Usage**: `sudo bash setup-server.sh`

### Application Deployment

**`deploy.sh`** - Deploy/update application
- Pulls latest code from git
- Installs dependencies (Composer, NPM)
- Builds frontend assets
- Runs database migrations
- Clears and caches configs
- Restarts services
- **Usage**: `sudo bash deployment/deploy.sh`

### Web Server Configuration

**`nginx.conf`** - Nginx web server configuration
- SSL/TLS with Let's Encrypt
- HTTP/2 support
- Security headers (CSP, HSTS, etc.)
- Gzip compression
- Static asset caching
- PHP-FPM integration
- **Installation**:
  ```bash
  sudo cp nginx.conf /etc/nginx/sites-available/makanguru
  sudo ln -s /etc/nginx/sites-available/makanguru /etc/nginx/sites-enabled/
  sudo nginx -t && sudo systemctl reload nginx
  ```

### SSL Certificate Setup

**`setup-ssl.sh`** - Automated SSL/TLS certificate installation
- Installs Certbot
- Obtains Let's Encrypt certificate
- Configures automatic renewal
- Updates Nginx configuration
- **Usage**: `sudo bash setup-ssl.sh your-domain.com`

### Queue Workers

**`supervisor.conf`** - Laravel queue worker management
- Manages background queue workers
- Auto-restart on failure
- Process monitoring
- **Installation**:
  ```bash
  sudo cp supervisor.conf /etc/supervisor/conf.d/makanguru.conf
  sudo supervisorctl reread && sudo supervisorctl update
  sudo supervisorctl start makanguru-worker:*
  ```

### Documentation

**`DEPLOYMENT.md`** - Complete deployment guide
- Step-by-step instructions
- AWS EC2 setup
- Database configuration
- Troubleshooting
- Performance optimization
- Security checklist

## Architecture

```
┌─────────────────┐
│   CloudFlare    │ (Optional CDN)
│   or Route53    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  AWS EC2 (t3)   │
│ Ubuntu 24.04    │
├─────────────────┤
│     Nginx       │ ← SSL/TLS (Let's Encrypt)
│   (Port 443)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   PHP-FPM 8.4   │
│   Laravel 12    │
├─────────────────┤
│  Livewire 3 UI  │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌────────┐ ┌────────┐
│ MySQL  │ │ Redis  │
│  8.0   │ │ Cache  │
└────────┘ └────┬───┘
                │
                ▼
         ┌──────────────┐
         │  Supervisor  │
         │Queue Workers │
         └──────────────┘
```

## Environment Variables

Key environment variables for production:

```ini
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=makanguru

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# AI Services
GEMINI_API_KEY=your_api_key
GROQ_API_KEY=your_groq_key
```

See `.env.production.example` for complete configuration.

## CI/CD

GitHub Actions workflow (`.github/workflows/tests.yml`) runs automatically on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop`

Workflow includes:
- PHPUnit tests
- PSR-12 code style checking
- Security vulnerability scanning
- Frontend asset build verification

## Performance Features

### Redis Caching
- Restaurant queries cached for 1 hour
- Reduces database load by ~90%
- Automatic cache invalidation

### Nginx Optimizations
- Gzip compression
- Static asset caching (1 year)
- HTTP/2 enabled
- OCSP stapling

### PHP Optimizations
- OPcache enabled
- 512MB memory limit
- Optimized buffer sizes

## Monitoring

### Logs
```bash
# Application
tail -f /var/www/makanguru/storage/logs/laravel.log

# Nginx
tail -f /var/log/nginx/makanguru-access.log
tail -f /var/log/nginx/makanguru-error.log

# Queue Workers
tail -f /var/www/makanguru/storage/logs/worker.log
```

### Service Status
```bash
# Check all services
sudo systemctl status php8.4-fpm
sudo systemctl status nginx
sudo systemctl status mysql
sudo systemctl status redis-server
sudo supervisorctl status
```

## Maintenance

### Update Application
```bash
cd /var/www/makanguru
sudo git pull origin main
sudo bash deployment/deploy.sh
```

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Restart Services
```bash
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
sudo supervisorctl restart makanguru-worker:*
```

## Security Checklist

- [ ] SSH key-based authentication enabled
- [ ] Password authentication disabled
- [ ] UFW firewall configured
- [ ] MySQL root remote login disabled
- [ ] Strong database passwords
- [ ] SSL/TLS certificate installed
- [ ] APP_DEBUG=false in production
- [ ] Security headers enabled
- [ ] Regular backups scheduled
- [ ] System updates automated

## Support

For issues or questions:
1. Check [DEPLOYMENT.md](./DEPLOYMENT.md) troubleshooting section
2. Review application logs
3. Check service status
4. Refer to CLAUDE.md for project context

---

**Last Updated**: 2025-12-19
**MakanGuru v1.0** - Production Ready
