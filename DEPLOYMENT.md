# Deployment Guide

Panduan lengkap untuk deployment Aerodrome Warning System ke production environment.

## 🚀 Production Setup

### 1. Server Requirements
- Ubuntu 20.04+ / CentOS 8+
- PHP 8.2+
- MySQL 8.0+
- Nginx/Apache
- SSL Certificate
- 2GB RAM minimum
- 20GB storage

### 2. Install Dependencies
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd

# Install MySQL
sudo apt install mysql-server

# Install Nginx
sudo apt install nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### 3. Database Setup
```bash
# Secure MySQL
sudo mysql_secure_installation

# Create database
sudo mysql -u root -p
CREATE DATABASE smard_db;
CREATE USER 'smard_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON smard_db.* TO 'smard_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Application Setup
```bash
# Clone repository
git clone <repository-url> /var/www/smard
cd /var/www/smard

# Set permissions
sudo chown -R www-data:www-data /var/www/smard
sudo chmod -R 755 /var/www/smard
sudo chmod -R 775 storage bootstrap/cache

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Environment setup
cp .env.example .env
php artisan key:generate
```

### 5. Environment Configuration
Edit `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smard_db
DB_USERNAME=smard_user
DB_PASSWORD=strong_password

CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# WhatsApp Configuration
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_TOKEN=your_production_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_token
```

### 6. Database Migration
```bash
php artisan migrate --force
php artisan db:seed --force
```

### 7. Nginx Configuration
Create `/etc/nginx/sites-available/smard`:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/smard/public;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/smard /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 8. Queue Worker Setup
Create systemd service `/etc/systemd/system/smard-queue.service`:
```ini
[Unit]
Description=SMARD Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/smard
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable smard-queue
sudo systemctl start smard-queue
```

### 9. Cron Jobs
```bash
# Edit crontab
sudo crontab -e

# Add Laravel scheduler
* * * * * cd /var/www/smard && php artisan schedule:run >> /dev/null 2>&1
```

### 10. SSL Certificate (Let's Encrypt)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d yourdomain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

## 🔧 Maintenance

### Backup Strategy
```bash
# Database backup
mysqldump -u smard_user -p smard_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Application backup
tar -czf smard_backup_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/smard

# Automated backup script
#!/bin/bash
BACKUP_DIR="/backup/smard"
DATE=$(date +%Y%m%d_%H%M%S)

# Database backup
mysqldump -u smard_user -p'password' smard_db > $BACKUP_DIR/db_$DATE.sql

# Application backup
tar -czf $BACKUP_DIR/app_$DATE.tar.gz /var/www/smard

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

### Monitoring
```bash
# Check queue status
sudo systemctl status smard-queue

# Check logs
tail -f /var/www/smard/storage/logs/laravel.log

# Monitor disk space
df -h

# Monitor memory
free -h
```

### Updates
```bash
# Pull latest changes
cd /var/www/smard
git pull origin main

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Restart services
sudo systemctl restart smard-queue
sudo systemctl reload nginx
```

## 🔒 Security

### Firewall Setup
```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow ssh

# Allow HTTP/HTTPS
sudo ufw allow 80
sudo ufw allow 443

# Check status
sudo ufw status
```

### File Permissions
```bash
# Set correct permissions
sudo chown -R www-data:www-data /var/www/smard
sudo chmod -R 755 /var/www/smard
sudo chmod -R 775 /var/www/smard/storage
sudo chmod -R 775 /var/www/smard/bootstrap/cache
```

### Environment Security
- Keep `.env` file secure
- Use strong database passwords
- Rotate access tokens regularly
- Enable HTTPS only
- Set up proper backup strategy

## 📊 Performance Optimization

### PHP Optimization
```bash
# Edit php.ini
sudo nano /etc/php/8.2/fpm/php.ini

# Optimize settings
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 10M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
```

### Nginx Optimization
```nginx
# Add to nginx.conf
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
```

## 🚨 Troubleshooting

### Common Issues
1. **500 Error**: Check Laravel logs and file permissions
2. **Queue not working**: Restart queue worker service
3. **Database connection**: Verify credentials and MySQL status
4. **WhatsApp not sending**: Check API credentials and webhook

### Log Locations
- Laravel: `/var/www/smard/storage/logs/laravel.log`
- Nginx: `/var/log/nginx/error.log`
- PHP: `/var/log/php8.2-fpm.log`
- System: `/var/log/syslog`

---

**Production Checklist**:
- [ ] SSL certificate installed
- [ ] Database backed up
- [ ] Queue worker running
- [ ] Cron jobs configured
- [ ] File permissions set
- [ ] Firewall configured
- [ ] Monitoring setup
- [ ] Backup strategy implemented
