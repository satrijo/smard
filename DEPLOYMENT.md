# 🚀 SMARD Deployment Guide

Panduan lengkap untuk deployment SMARD (Aerodrome Warning System) menggunakan Docker dengan PostgreSQL dan Caddy.

## 📋 Prerequisites

### Sistem Requirements
- **OS**: Linux (Ubuntu 20.04+), Windows 10/11, atau macOS
- **RAM**: Minimal 4GB (Recommended 8GB+)
- **Storage**: Minimal 10GB free space
- **Network**: Akses ke IP lokal 192.168.10.54

### Software Requirements
- **Docker**: Version 20.10+
- **Docker Compose**: Version 2.0+
- **Git**: Untuk clone repository

## 🐳 Docker Setup

### 1. Install Docker
```bash
# Ubuntu/Debian
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Windows/macOS
# Download dari https://www.docker.com/products/docker-desktop
```

### 2. Install Docker Compose
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install docker-compose-plugin

# Verify installation
docker compose version
```

## 🚀 Quick Deployment

### Automated Deployment
```bash
# 1. Clone repository
git clone <repository-url>
cd smard

# 2. Jalankan script deployment
chmod +x deploy.sh
./deploy.sh
```

### Manual Deployment
```bash
# 1. Clone repository
git clone <repository-url>
cd smard

# 2. Setup environment
cp env.example .env

# 3. Build dan start containers
docker-compose up -d --build

# 4. Setup Laravel
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --force
```

## 🔧 Konfigurasi

### Environment Variables
File `.env` akan dibuat otomatis dari `env.example`. Konfigurasi utama:

```env
# Application
APP_NAME="SMARD"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.10.54

# Database PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=smard_db
DB_USERNAME=smard_user
DB_PASSWORD=smard_password

# WhatsApp Configuration (tambahkan sesuai kebutuhan)
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_TOKEN=your_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_id
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_token
```

### Network Configuration
- **Web Application**: http://192.168.10.54
- **PostgreSQL**: localhost:5432
- **Caddy**: Port 80 & 443

## 📊 Services Overview

### 1. App Container (Laravel)
- **Image**: Custom PHP 8.2-FPM
- **Port**: 9000 (internal)
- **Features**: 
  - PHP 8.2 dengan ekstensi PostgreSQL
  - Node.js 22 untuk build assets
  - Composer untuk dependencies
  - Supervisor untuk process management

### 2. PostgreSQL Container
- **Image**: postgres:15-alpine
- **Port**: 5432
- **Database**: smard_db
- **Credentials**: smard_user / smard_password
- **Persistence**: Volume postgres_data

### 3. Caddy Container (Web Server)
- **Image**: caddy:2-alpine
- **Port**: 80, 443
- **Features**:
  - Reverse proxy ke PHP-FPM
  - Static file serving
  - Gzip compression
  - Automatic HTTPS (jika diperlukan)

## 🔍 Monitoring & Logs

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f postgres
docker-compose logs -f caddy

# Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log
```

### Container Status
```bash
# Check running containers
docker-compose ps

# Check resource usage
docker stats
```

## 🛠️ Maintenance

### Backup Database
```bash
# Create backup
docker-compose exec postgres pg_dump -U smard_user smard_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore backup
docker-compose exec -T postgres psql -U smard_user smard_db < backup_file.sql
```

### Update Application
```bash
# Pull latest changes
git pull origin main

# Rebuild and restart
docker-compose down
docker-compose up -d --build

# Run migrations
docker-compose exec app php artisan migrate --force
```

### Clear Cache
```bash
# Laravel cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Rebuild cache
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Container tidak start
```bash
# Check logs
docker-compose logs

# Check disk space
df -h

# Check Docker daemon
sudo systemctl status docker
```

#### 2. Database connection error
```bash
# Check PostgreSQL container
docker-compose ps postgres

# Check database logs
docker-compose logs postgres

# Test connection
docker-compose exec app php artisan tinker
# DB::connection()->getPdo();
```

#### 3. Permission issues
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage
docker-compose exec app chmod -R 755 storage
docker-compose exec app chmod -R 755 bootstrap/cache
```

#### 4. Caddy tidak bisa akses app
```bash
# Check network
docker network ls
docker network inspect smard_smard_network

# Check app container
docker-compose exec app php-fpm -t
```

### Performance Optimization

#### 1. Enable OPcache
```bash
# Add to Dockerfile
RUN docker-php-ext-install opcache
```

#### 2. Configure PHP-FPM
```bash
# Edit php-fpm.conf untuk production
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

#### 3. Database Optimization
```bash
# PostgreSQL tuning
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB
```

## 🔒 Security Considerations

### 1. Change Default Passwords
```bash
# Update database password
docker-compose exec postgres psql -U smard_user -d smard_db
# ALTER USER smard_user PASSWORD 'new_secure_password';
```

### 2. Environment Variables
- Jangan commit file `.env` ke repository
- Gunakan secrets management untuk production
- Rotate credentials secara berkala

### 3. Network Security
- Batasi akses ke port database (5432)
- Gunakan firewall untuk membatasi akses
- Consider VPN untuk remote access

### 4. SSL/TLS
```bash
# Caddy automatic HTTPS
# Edit Caddyfile untuk domain dengan SSL
your-domain.com {
    tls your-email@domain.com
    # ... rest of configuration
}
```

## 📈 Scaling

### Horizontal Scaling
```bash
# Scale app containers
docker-compose up -d --scale app=3

# Load balancer configuration
# Add nginx/haproxy container
```

### Vertical Scaling
```bash
# Increase resources in docker-compose.yml
services:
  app:
    deploy:
      resources:
        limits:
          memory: 2G
          cpus: '2'
```

## 📞 Support

### Useful Commands
```bash
# Access container shell
docker-compose exec app bash
docker-compose exec postgres psql -U smard_user -d smard_db

# Check service health
docker-compose exec app php artisan route:list
docker-compose exec app php artisan queue:work --once

# Monitor resources
docker system df
docker system prune
```

### Log Locations
- **Laravel**: `/var/www/storage/logs/`
- **Caddy**: `/var/log/caddy/`
- **PostgreSQL**: Container logs
- **Supervisor**: `/var/log/supervisor/`

---

**SMARD Deployment Guide** - Panduan lengkap deployment sistem peringatan aerodrome 🛩️🌤️
