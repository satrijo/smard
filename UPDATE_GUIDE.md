# 🔄 SMARD Update Guide

Panduan lengkap untuk update aplikasi SMARD dari dev server ke production server.

## 📋 Alur Update

### **1. Development Server (Dev)**
```bash
# Developer membuat perubahan
git add .
git commit -m "Add new feature"
git push origin main
```

### **2. Production Server (Prod)**
```bash
# Admin server menjalankan update
./update.sh
# atau
.\update.ps1
```

## 🚀 Quick Update Commands

### **Linux/macOS**
```bash
chmod +x update.sh
./update.sh
```

### **Windows PowerShell**
```powershell
.\update.ps1
```

## 📊 Detail Alur Update Script

### **1. Pre-Update Checks**
- ✅ Validasi direktori project
- ✅ Cek Docker & Docker Compose
- ✅ Backup file `.env` otomatis

### **2. Git Operations**
- 📥 Pull latest changes dari repository
- 🔍 Deteksi environment variables baru
- 📦 Stash local changes (opsional)

### **3. Container Management**
- 🛑 Stop containers gracefully
- 🐳 Build containers dengan code baru
- ⏳ Wait PostgreSQL ready

### **4. Laravel Maintenance**
- 🔧 Clear semua cache
- 🗄️ Run migrations otomatis
- ⚡ Optimize untuk production
- 🏥 Health check aplikasi

### **5. Post-Update**
- 📊 Show container status
- ✅ Konfirmasi update berhasil
- 📋 Tampilkan useful commands

## 🔧 Konfigurasi Git

### **Setup Repository**
```bash
# Clone repository
git clone <repository-url>
cd smard

# Setup remote (jika belum)
git remote add origin <repository-url>
git branch -M main
```

### **Branch Strategy**
```bash
# Development
git checkout -b feature/new-feature
git push origin feature/new-feature

# Merge ke main
git checkout main
git merge feature/new-feature
git push origin main
```

## 📁 File Management

### **Environment Variables**
```bash
# Backup otomatis saat update
.env.backup.20250115_143022

# Restore jika diperlukan
cp .env.backup.20250115_143022 .env
```

### **Database Migrations**
```bash
# Check migration status
docker-compose exec app php artisan migrate:status

# Run migrations manually
docker-compose exec app php artisan migrate --force

# Rollback jika diperlukan
docker-compose exec app php artisan migrate:rollback
```

## 🔄 Rollback Strategy

### **Quick Rollback**
```bash
# 1. Check commit history
git log --oneline -5

# 2. Rollback ke commit sebelumnya
git checkout <previous-commit-hash>

# 3. Rebuild containers
docker-compose down
docker-compose up -d --build

# 4. Restore database jika diperlukan
docker-compose exec postgres psql -U smard_user -d smard_db < backup_file.sql
```

### **Database Rollback**
```bash
# Backup sebelum update
docker-compose exec postgres pg_dump -U smard_user smard_db > backup_before_update.sql

# Restore jika update gagal
docker-compose exec -T postgres psql -U smard_user smard_db < backup_before_update.sql
```

## 🚨 Troubleshooting Update

### **Common Issues**

#### 1. **Git Pull Failed**
```bash
# Check git status
git status

# Reset jika ada conflict
git reset --hard HEAD
git pull origin main
```

#### 2. **Container Build Failed**
```bash
# Check logs
docker-compose logs app

# Clean build
docker-compose down
docker system prune -f
docker-compose up -d --build
```

#### 3. **Migration Failed**
```bash
# Check migration status
docker-compose exec app php artisan migrate:status

# Fix migration
docker-compose exec app php artisan migrate:rollback
docker-compose exec app php artisan migrate --force
```

#### 4. **Application Not Responding**
```bash
# Check container status
docker-compose ps

# Check application logs
docker-compose logs -f app

# Restart containers
docker-compose restart
```

## 📈 Monitoring Update

### **Health Check Commands**
```bash
# Check application status
curl -f http://192.168.10.54

# Check container health
docker-compose ps

# Check logs
docker-compose logs -f app
docker-compose logs -f postgres
docker-compose logs -f caddy
```

### **Performance Monitoring**
```bash
# Check resource usage
docker stats

# Check disk space
df -h

# Check memory usage
free -h
```

## 🔒 Security Considerations

### **Update Security**
- ✅ Backup otomatis sebelum update
- ✅ Environment variables tidak ter-overwrite
- ✅ Database migrations aman
- ✅ Rollback capability

### **Access Control**
```bash
# Restrict script permissions
chmod 750 update.sh
chmod 750 update.ps1

# Run as specific user
sudo -u smard-user ./update.sh
```

## 📞 Support

### **Emergency Contacts**
- **System Admin**: admin@smard.local
- **Developer**: dev@smard.local
- **Emergency**: +62 xxx-xxxx-xxxx

### **Useful Commands**
```bash
# Emergency stop
docker-compose down

# Emergency restart
docker-compose restart

# Check all logs
docker-compose logs -f

# Access container
docker-compose exec app bash
```

---

**SMARD Update Guide** - Panduan update aplikasi yang aman dan terstruktur 🔄🛡️
