#!/bin/bash

###############################################################################
# MakanGuru - SSL/TLS Setup Script using Let's Encrypt Certbot
#
# This script automatically obtains and installs SSL certificates from
# Let's Encrypt for your domain using Certbot.
#
# Prerequisites:
# - Domain DNS must point to this server's IP address
# - Nginx must be installed and running
# - Ports 80 and 443 must be accessible
#
# Usage: sudo bash setup-ssl.sh your-domain.com
###############################################################################

set -e  # Exit on any error

if [ "$EUID" -ne 0 ]; then
    echo "❌ Error: This script must be run as root (use sudo)"
    exit 1
fi

if [ -z "$1" ]; then
    echo "❌ Error: Domain name is required"
    echo "Usage: sudo bash setup-ssl.sh your-domain.com"
    exit 1
fi

DOMAIN=$1
EMAIL="admin@${DOMAIN}"  # Change this to your actual email

echo "====================================="
echo "MakanGuru SSL Setup with Let's Encrypt"
echo "====================================="
echo "Domain: $DOMAIN"
echo "Email: $EMAIL"
echo "====================================="

# Install Certbot and Nginx plugin
echo "📦 Installing Certbot..."
apt-get update
apt-get install -y certbot python3-certbot-nginx

# Verify Nginx configuration
echo "🔍 Checking Nginx configuration..."
nginx -t

# Create a temporary Nginx config for ACME challenge
TEMP_NGINX_CONF="/etc/nginx/sites-available/makanguru-temp"

cat > "$TEMP_NGINX_CONF" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN;

    root /var/www/makanguru/public;

    location /.well-known/acme-challenge/ {
        root /var/www/makanguru/public;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

# Enable temporary config
echo "⚙️  Enabling temporary Nginx configuration..."
rm -f /etc/nginx/sites-enabled/makanguru
ln -sf "$TEMP_NGINX_CONF" /etc/nginx/sites-enabled/makanguru
nginx -t
systemctl reload nginx

# Obtain SSL certificate
echo "🔐 Obtaining SSL certificate from Let's Encrypt..."
certbot certonly \
    --nginx \
    --non-interactive \
    --agree-tos \
    --email "$EMAIL" \
    -d "$DOMAIN" \
    -d "www.$DOMAIN"

# Update the main Nginx config with SSL paths
echo "⚙️  Updating Nginx configuration with SSL..."
NGINX_CONF="/etc/nginx/sites-available/makanguru"

# Copy the deployment nginx.conf template
cp /var/www/makanguru/deployment/nginx.conf "$NGINX_CONF"

# Replace placeholder domain
sed -i "s/your-domain.com/$DOMAIN/g" "$NGINX_CONF"

# Enable the SSL-enabled config
rm -f /etc/nginx/sites-enabled/makanguru
ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/makanguru

# Test and reload Nginx
echo "🔍 Testing Nginx configuration..."
nginx -t

echo "🔄 Reloading Nginx..."
systemctl reload nginx

# Set up automatic renewal
echo "⏰ Setting up automatic certificate renewal..."
certbot renew --dry-run

# Create a cron job for automatic renewal (if not exists)
CRON_JOB="0 3 * * * certbot renew --quiet --post-hook 'systemctl reload nginx'"
(crontab -l 2>/dev/null | grep -v "certbot renew" ; echo "$CRON_JOB") | crontab -

echo ""
echo "✅ SSL setup complete!"
echo ""
echo "Your site is now accessible at:"
echo "  https://$DOMAIN"
echo "  https://www.$DOMAIN"
echo ""
echo "Certificate details:"
certbot certificates
echo ""
echo "SSL certificates will auto-renew every 60 days."
echo "Renewal cron job has been added to root's crontab."
echo ""
echo "Test SSL configuration:"
echo "  https://www.ssllabs.com/ssltest/analyze.html?d=$DOMAIN"
echo ""
