#!/bin/bash

###############################################################################
# MakanGuru - Application Deployment Script
#
# This script deploys/updates the MakanGuru application on the server.
# Run this script from the application root directory.
#
# Usage: bash deployment/deploy.sh
###############################################################################

set -e  # Exit on any error

APP_DIR="/var/www/makanguru"
PHP_FPM_SERVICE="php8.4-fpm"
NGINX_SERVICE="nginx"

echo "====================================="
echo "MakanGuru Deployment Script"
echo "====================================="

# Check if .env exists
if [ ! -f "$APP_DIR/.env" ]; then
    echo "❌ Error: .env file not found!"
    echo "Please copy .env.example to .env and configure it first."
    exit 1
fi

# Enter maintenance mode
echo "🔧 Enabling maintenance mode..."
cd "$APP_DIR"
php artisan down || true

# Pull latest changes (if using git)
echo "📥 Pulling latest changes..."
git pull origin main || echo "⚠️  Git pull skipped (not a git repository or no remote)"

# Install/update Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install/update NPM dependencies and build assets
echo "📦 Installing NPM dependencies..."
npm ci

echo "🏗️  Building frontend assets..."
npm run build

# Clear and cache config
echo "⚙️  Optimizing configuration..."
php artisan config:clear
php artisan config:cache

# Clear and cache routes
echo "🛣️  Optimizing routes..."
php artisan route:clear
php artisan route:cache

# Clear and cache views
echo "👁️  Optimizing views..."
php artisan view:clear
php artisan view:cache

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Clear application cache
echo "🧹 Clearing application cache..."
php artisan cache:clear

# Clear Redis cache (optional - uncomment if needed)
# echo "🔴 Flushing Redis cache..."
# php artisan redis:clear

# Optimize application
echo "⚡ Optimizing application..."
php artisan optimize

# Set correct permissions
echo "🔐 Setting file permissions..."
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"

# Restart PHP-FPM
echo "🔄 Restarting PHP-FPM..."
systemctl restart "$PHP_FPM_SERVICE"

# Reload Nginx
echo "🔄 Reloading Nginx..."
systemctl reload "$NGINX_SERVICE"

# Restart queue workers (if using Supervisor)
echo "👷 Restarting queue workers..."
supervisorctl reread
supervisorctl update
supervisorctl restart makanguru-worker:* || echo "⚠️  No queue workers configured"

# Exit maintenance mode
echo "✅ Disabling maintenance mode..."
php artisan up

echo ""
echo "✅ Deployment complete!"
echo ""
echo "Application URL: http://your-domain.com"
echo "Check status: systemctl status $PHP_FPM_SERVICE"
echo "View logs: tail -f storage/logs/laravel.log"
echo ""
