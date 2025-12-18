#!/bin/bash

###############################################################################
# MakanGuru - AWS EC2 Server Setup Script
#
# This script sets up a fresh Ubuntu 24.04 EC2 instance for MakanGuru deployment.
# It installs and configures:
# - PHP 8.4 with required extensions
# - Nginx web server
# - MySQL 8.0 database
# - Redis for caching and queues
# - Composer
# - Node.js and NPM
# - Supervisor for queue workers
#
# Usage: sudo bash setup-server.sh
###############################################################################

set -e  # Exit on any error

echo "====================================="
echo "MakanGuru Server Setup Script"
echo "Ubuntu 24.04 LTS"
echo "====================================="

# Update system packages
echo "📦 Updating system packages..."
apt-get update
apt-get upgrade -y

# Install essential utilities
echo "🔧 Installing essential utilities..."
apt-get install -y software-properties-common curl wget git unzip vim

# Add PHP 8.4 PPA (if not available in default repos)
echo "📦 Adding PHP 8.4 repository..."
add-apt-repository ppa:ondrej/php -y
apt-get update

# Install PHP 8.4 and extensions
echo "🐘 Installing PHP 8.4 and required extensions..."
apt-get install -y \
    php8.4-fpm \
    php8.4-cli \
    php8.4-common \
    php8.4-mysql \
    php8.4-zip \
    php8.4-gd \
    php8.4-mbstring \
    php8.4-curl \
    php8.4-xml \
    php8.4-bcmath \
    php8.4-redis \
    php8.4-intl

# Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Nginx
echo "🌐 Installing Nginx..."
apt-get install -y nginx

# Install MySQL 8.0
echo "🗄️  Installing MySQL 8.0..."
apt-get install -y mysql-server

# Secure MySQL installation (automated)
mysql --version
echo "⚠️  Remember to run 'mysql_secure_installation' manually after this script!"

# Install Redis
echo "🔴 Installing Redis..."
apt-get install -y redis-server

# Configure Redis to start on boot
systemctl enable redis-server
systemctl start redis-server

# Install Node.js 24.x LTS
echo "📦 Installing Node.js 24.x LTS..."
curl -fsSL https://deb.nodesource.com/setup_24.x | bash -
apt-get install -y nodejs

# Install Supervisor for queue workers
echo "👷 Installing Supervisor..."
apt-get install -y supervisor
systemctl enable supervisor
systemctl start supervisor

# Configure PHP-FPM
echo "⚙️  Configuring PHP-FPM..."
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.4/fpm/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 20M/' /etc/php/8.4/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 20M/' /etc/php/8.4/fpm/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 512M/' /etc/php/8.4/fpm/php.ini

# Restart PHP-FPM
systemctl restart php8.4-fpm

# Create application directory
echo "📁 Creating application directory..."
mkdir -p /var/www/makanguru
chown -R www-data:www-data /var/www/makanguru

# Configure firewall (UFW)
echo "🔥 Configuring firewall..."
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
# Don't enable UFW automatically to avoid locking out

echo ""
echo "✅ Server setup complete!"
echo ""
echo "Next steps:"
echo "1. Create MySQL database and user:"
echo "   mysql -u root -p"
echo "   CREATE DATABASE makanguru;"
echo "   CREATE USER 'makanguru'@'localhost' IDENTIFIED BY 'your_password';"
echo "   GRANT ALL PRIVILEGES ON makanguru.* TO 'makanguru'@'localhost';"
echo "   FLUSH PRIVILEGES;"
echo ""
echo "2. Clone your repository to /var/www/makanguru"
echo "3. Run deployment script: bash deployment/deploy.sh"
echo "4. Configure Nginx: Copy deployment/nginx.conf to /etc/nginx/sites-available/"
echo "5. Set up SSL with Certbot: bash deployment/setup-ssl.sh"
echo ""
