# MakanGuru - Deployment Guide

This guide provides step-by-step instructions for deploying MakanGuru to AWS EC2 with Ubuntu 24.04 LTS.

## Table of Contents

- [Prerequisites](#prerequisites)
- [AWS EC2 Setup](#aws-ec2-setup)
- [Server Configuration](#server-configuration)
- [Application Deployment](#application-deployment)
- [SSL/TLS Setup](#ssltls-setup)
- [Queue Workers](#queue-workers)
- [Monitoring & Maintenance](#monitoring--maintenance)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

### AWS Account Setup

1. **AWS Account**: Create an AWS account at https://aws.amazon.com
2. **EC2 Instance**: Provision an EC2 instance with the following specs:
   - **Instance Type**: `t3.medium` or higher (2 vCPU, 4GB RAM minimum)
   - **OS**: Ubuntu 24.04 LTS (64-bit)
   - **Storage**: 30GB SSD minimum
   - **Security Group**: Allow ports 22 (SSH), 80 (HTTP), 443 (HTTPS)

3. **Elastic IP**: Assign a static Elastic IP to your EC2 instance

### Domain Configuration

1. Purchase a domain name (e.g., from Namecheap, Google Domains, Route53)
2. Point your domain's A record to your EC2 Elastic IP:
   ```
   Type: A
   Name: @
   Value: YOUR_EC2_ELASTIC_IP
   TTL: 3600

   Type: A
   Name: www
   Value: YOUR_EC2_ELASTIC_IP
   TTL: 3600
   ```

### Required Credentials

Prepare the following before deployment:
- **EC2 SSH key pair** (.pem file)
- **GROQ_API_KEY** from https://console.groq.com/ (required - provides OpenAI GPT + Meta Llama)
- **GEMINI_API_KEY** (optional, legacy) from https://ai.google.dev/
- **MySQL root password** (create a strong password)
- **Database password** for application user

---

## AWS EC2 Setup

### 1. Connect to Your EC2 Instance

```bash
# SSH into your server (replace with your key and IP)
ssh -i /path/to/your-key.pem ubuntu@YOUR_EC2_IP

# Update system
sudo apt-get update && sudo apt-get upgrade -y
```

### 2. Run Server Setup Script

```bash
# Download and run the server setup script
wget https://raw.githubusercontent.com/YOUR_REPO/deployment/setup-server.sh
sudo bash setup-server.sh
```

This script installs:
- PHP 8.4 with required extensions
- Nginx web server
- MySQL 8.0 database
- Redis for caching and queues
- Node.js 24.x LTS
- Composer
- Supervisor for queue workers

**Expected Duration**: 10-15 minutes

---

## Server Configuration

### 1. Secure MySQL Installation

```bash
# Run MySQL secure installation
sudo mysql_secure_installation

# Follow prompts:
# - Set root password
# - Remove anonymous users: Yes
# - Disallow root login remotely: Yes
# - Remove test database: Yes
# - Reload privilege tables: Yes
```

### 2. Create Database and User

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE makanguru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'makanguru'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON makanguru.* TO 'makanguru'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Configure Redis

```bash
# Edit Redis configuration
sudo nano /etc/redis/redis.conf

# Set maxmemory policy (add/update these lines):
maxmemory 256mb
maxmemory-policy allkeys-lru

# Restart Redis
sudo systemctl restart redis-server
```

---

## Application Deployment

### 1. Clone Repository

```bash
# Navigate to web directory
cd /var/www

# Clone your repository
sudo git clone https://github.com/YOUR_USERNAME/makanguru.git
cd makanguru

# Set ownership
sudo chown -R www-data:www-data /var/www/makanguru
```

### 2. Configure Environment

```bash
# Copy environment file
sudo cp .env.example .env

# Edit environment variables
sudo nano .env
```

**Update the following in `.env`:**

```ini
APP_NAME=MakanGuru
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=makanguru
DB_USERNAME=makanguru
DB_PASSWORD=your_database_password

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# AI Configuration (Automatic Fallback: OpenAI → Meta)
GROQ_API_KEY=your_groq_api_key
GEMINI_API_KEY=your_gemini_api_key  # Optional (legacy support)
AI_PROVIDER=groq  # Primary: OpenAI GPT, Fallback: Meta Llama
```

### 3. Run Initial Deployment

```bash
# Make deployment script executable
sudo chmod +x deployment/deploy.sh

# Run deployment (as root or with sudo)
sudo bash deployment/deploy.sh
```

This script will:
- Install Composer dependencies
- Install NPM dependencies and build assets
- Generate application key
- Run database migrations
- Cache configuration and routes
- Set correct file permissions
- Restart PHP-FPM and Nginx

---

## SSL/TLS Setup

### 1. Configure Nginx

```bash
# Copy Nginx configuration
sudo cp deployment/nginx.conf /etc/nginx/sites-available/makanguru

# Update domain name
sudo sed -i 's/your-domain.com/your-actual-domain.com/g' /etc/nginx/sites-available/makanguru

# Enable site
sudo ln -sf /etc/nginx/sites-available/makanguru /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default  # Remove default site

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### 2. Install SSL Certificate

```bash
# Run SSL setup script
sudo bash deployment/setup-ssl.sh your-domain.com
```

This script will:
- Install Certbot
- Obtain Let's Encrypt SSL certificate
- Configure automatic renewal (cron job)
- Update Nginx configuration with SSL paths

**Verify SSL**: Visit https://your-domain.com

---

## Queue Workers

### 1. Configure Supervisor

```bash
# Copy Supervisor configuration
sudo cp deployment/supervisor.conf /etc/supervisor/conf.d/makanguru.conf

# Update paths if necessary
sudo nano /etc/supervisor/conf.d/makanguru.conf

# Reload Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start makanguru-worker:*

# Check status
sudo supervisorctl status
```

### 2. Set Up Cron for Scheduler

```bash
# Edit www-data user's crontab
sudo crontab -e -u www-data

# Add this line:
* * * * * cd /var/www/makanguru && php artisan schedule:run >> /dev/null 2>&1
```

---

## Monitoring & Maintenance

### Log Files

```bash
# Application logs
tail -f /var/www/makanguru/storage/logs/laravel.log

# Nginx access logs
tail -f /var/log/nginx/makanguru-access.log

# Nginx error logs
tail -f /var/log/nginx/makanguru-error.log

# Queue worker logs
tail -f /var/www/makanguru/storage/logs/worker.log

# PHP-FPM logs
tail -f /var/log/php8.4-fpm.log
```

### Service Management

```bash
# PHP-FPM
sudo systemctl status php8.4-fpm
sudo systemctl restart php8.4-fpm

# Nginx
sudo systemctl status nginx
sudo systemctl reload nginx

# MySQL
sudo systemctl status mysql
sudo systemctl restart mysql

# Redis
sudo systemctl status redis-server
sudo systemctl restart redis-server

# Supervisor
sudo supervisorctl status
sudo supervisorctl restart makanguru-worker:*
```

### Deployment Updates

```bash
# Pull latest changes
cd /var/www/makanguru
sudo git pull origin main

# Run deployment script
sudo bash deployment/deploy.sh
```

### Database Backups

```bash
# Create backup script
sudo nano /usr/local/bin/backup-makanguru.sh
```

**Add this content:**

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/makanguru"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u makanguru -p'your_password' makanguru | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete

echo "Backup completed: db_$DATE.sql.gz"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-makanguru.sh

# Add to crontab (run daily at 2 AM)
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-makanguru.sh
```

### Cache Management

```bash
# Clear all caches
cd /var/www/makanguru
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Troubleshooting

### 502 Bad Gateway

```bash
# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Check PHP-FPM socket
ls -la /var/run/php/php8.4-fpm.sock

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

### 500 Internal Server Error

```bash
# Check application logs
tail -f /var/www/makanguru/storage/logs/laravel.log

# Check Nginx error logs
tail -f /var/log/nginx/makanguru-error.log

# Ensure .env is configured
cat /var/www/makanguru/.env

# Run migrations
php artisan migrate --force
```

### Database Connection Issues

```bash
# Test MySQL connection
mysql -u makanguru -p -h 127.0.0.1 makanguru

# Check MySQL status
sudo systemctl status mysql

# Verify credentials in .env
cat /var/www/makanguru/.env | grep DB_
```

### Queue Workers Not Running

```bash
# Check Supervisor status
sudo supervisorctl status

# View worker logs
tail -f /var/www/makanguru/storage/logs/worker.log

# Restart workers
sudo supervisorctl restart makanguru-worker:*
```

### Redis Connection Issues

```bash
# Check Redis status
sudo systemctl status redis-server

# Test Redis connection
redis-cli ping  # Should return PONG

# Check Laravel Redis connection
php artisan tinker
>>> Redis::ping()
```

### SSL Certificate Issues

```bash
# Check certificate status
sudo certbot certificates

# Renew certificates manually
sudo certbot renew

# Test renewal
sudo certbot renew --dry-run
```

### Permission Issues

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/makanguru

# Fix permissions
sudo chmod -R 755 /var/www/makanguru
sudo chmod -R 775 /var/www/makanguru/storage
sudo chmod -R 775 /var/www/makanguru/bootstrap/cache
```

---

## Performance Optimization

### 1. Enable OPcache

```bash
# Edit PHP-FPM configuration
sudo nano /etc/php/8.4/fpm/php.ini

# Enable OPcache (uncomment/add):
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

### 2. Configure Laravel Caching

```bash
cd /var/www/makanguru

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 3. Monitor Server Resources

```bash
# Install htop
sudo apt-get install htop

# Monitor in real-time
htop

# Check disk usage
df -h

# Check memory usage
free -h
```

---

## Security Checklist

- [ ] SSH key-based authentication enabled
- [ ] Password authentication disabled for SSH
- [ ] UFW firewall configured
- [ ] MySQL root remote login disabled
- [ ] Strong database passwords set
- [ ] SSL/TLS certificate installed and auto-renewing
- [ ] `.env` file has correct permissions (600)
- [ ] `APP_DEBUG=false` in production
- [ ] Security headers configured in Nginx
- [ ] Regular backups scheduled
- [ ] Application logs monitored
- [ ] System updates automated

---

## Additional Resources

- **Laravel Documentation**: https://laravel.com/docs
- **Nginx Documentation**: https://nginx.org/en/docs/
- **Let's Encrypt**: https://letsencrypt.org/
- **AWS EC2 Guide**: https://docs.aws.amazon.com/ec2/

---

**Deployment completed!** Your MakanGuru application should now be live at https://your-domain.com

For support, check the logs or refer to the troubleshooting section above.
