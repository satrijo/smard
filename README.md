# Aerodrome Warning System (SMARD)

Sistem peringatan aerodrome untuk Stasiun Meteorologi Tunggul Wulung yang memungkinkan forecaster untuk membuat, mengelola, dan membatalkan peringatan cuaca aerodrome secara real-time dengan integrasi WhatsApp.

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Alur Kerja](#-alur-kerja)
- [Cara Penggunaan](#-cara-penggunaan)
- [API Endpoints](#-api-endpoints)
- [Struktur Database](#-struktur-database)
- [Troubleshooting](#-troubleshooting)
- [Kontribusi](#-kontribusi)

## ✨ Fitur Utama

### 🚨 Manajemen Peringatan
- **Pembuatan Peringatan**: Form interaktif untuk membuat peringatan aerodrome
- **Fenomena Cuaca**: Dukungan untuk berbagai fenomena cuaca (TS, GR, HVY RA, SFC WIND, VIS, SQ, VA, TOX CHEM, TSUNAMI, dll.)
- **Pembatalan Peringatan**: Sistem pembatalan dengan konfirmasi dan notifikasi
- **Edit Draft**: Kemampuan untuk mengedit pesan dan terjemahan sebelum diterbitkan

### 📱 Integrasi WhatsApp
- **Pengiriman Otomatis**: Peringatan otomatis dikirim ke grup WhatsApp
- **Format Standar**: Pesan mengikuti format standar meteorologi
- **Terjemahan**: Pesan dalam bahasa Indonesia dan sandi meteorologi

### 📊 Dashboard Real-time
- **Statistik Live**: Statistik peringatan real-time
- **Auto-refresh**: Pembaruan data otomatis setiap 30 detik
- **Status Monitoring**: Monitoring status peringatan (aktif, expired, dibatalkan)

### 📄 Laporan & Arsip
- **Arsip Harian**: Arsip peringatan hari ini
- **Semua Peringatan**: Database lengkap dengan filter dan pencarian
- **Export PDF**: Export laporan dalam format PDF
- **Filter Advanced**: Filter berdasarkan tanggal, status, forecaster, fenomena

## 🛠 Teknologi yang Digunakan

### Backend
- **Laravel 11**: PHP Framework
- **MySQL**: Database
- **Eloquent ORM**: Object-Relational Mapping
- **Laravel Queue**: Background job processing

### Frontend
- **Vue.js 3**: JavaScript Framework
- **Inertia.js**: SPA tanpa API
- **Tailwind CSS**: Utility-first CSS framework
- **Lucide Icons**: Icon library

### Integrasi
- **WhatsApp Business API**: Pengiriman pesan otomatis
- **DomPDF**: Generate PDF reports

## 💻 Persyaratan Sistem

- PHP 8.2 atau lebih tinggi
- Composer
- Node.js 18+ dan npm
- MySQL 8.0 atau lebih tinggi
- Web server (Apache/Nginx)
- WhatsApp Business API credentials

## 🚀 Instalasi

### Opsi 1: Docker Deployment (Recommended)

#### Prerequisites
- Docker dan Docker Compose terinstall
- Git

#### Quick Start
```bash
# Clone repository
git clone <repository-url>
cd smard

# Jalankan script deployment
chmod +x deploy.sh
./deploy.sh
```

#### Manual Docker Setup
```bash
# 1. Clone repository
git clone <repository-url>
cd smard

# 2. Setup environment
cp .env.example .env

# 3. Build dan start containers
docker-compose up -d --build

# 4. Setup Laravel (dalam container)
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --force
```

#### Akses Aplikasi
- **Web Application**: http://192.168.10.54
- **PostgreSQL**: localhost:5432
- **Database**: smard_db
- **Username**: smard_user
- **Password**: smard_password

### Opsi 2: Local Development

#### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- PostgreSQL 15+

#### Setup Manual
```bash
# 1. Clone repository
git clone <repository-url>
cd smard

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env

# 4. Konfigurasi database PostgreSQL
# Edit .env file:
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=smard_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# 5. Setup Laravel
php artisan key:generate
php artisan migrate
php artisan db:seed

# 6. Build assets
npm run build

# 7. Start server
php artisan serve
php artisan queue:work
```

## ⚙️ Konfigurasi

### WhatsApp Configuration
Edit file `config/whatsapp.php`:
```php
return [
    'api_url' => env('WHATSAPP_API_URL'),
    'token' => env('WHATSAPP_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
];
```

Tambahkan ke file `.env`:
```env
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_TOKEN=your_whatsapp_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_verify_token
```

### Forecaster Setup
1. Buka aplikasi di browser
2. Pilih forecaster dari dropdown di header
3. Data forecaster akan disimpan di localStorage

## 🔄 Alur Kerja

### 1. Pembuatan Peringatan
```
Forecaster Login → Pilih Forecaster → Isi Form Peringatan → 
Generate Preview → Edit (Opsional) → Terbitkan → 
Kirim WhatsApp → Simpan ke Database
```

### 2. Pembatalan Peringatan
```
Pilih Peringatan → Konfirmasi Pembatalan → 
Edit Pesan Pembatalan (Opsional) → 
Terbitkan Pembatalan → Kirim WhatsApp → 
Update Status Peringatan
```

### 3. Monitoring Real-time
```
Dashboard → Auto-refresh (30s) → Update Statistik → 
Update Daftar Peringatan → Notifikasi Status
```

## 📖 Cara Penggunaan

### 🎯 Membuat Peringatan Baru

1. **Login dan Pilih Forecaster**
   - Buka aplikasi di browser
   - Pilih forecaster dari dropdown di header
   - Pastikan forecaster sudah dipilih sebelum membuat peringatan

2. **Isi Form Peringatan**
   - **Bandara**: Otomatis terisi WAHL (Tunggul Wulung)
   - **Waktu Validitas**: Pilih waktu mulai dan berakhir
   - **Fenomena**: Pilih satu atau lebih fenomena cuaca
   - **Detail Fenomena**: Isi detail sesuai fenomena yang dipilih
   - **Sumber Data**: Pilih OBS (Observasi) atau FCST (Forecast)
   - **Intensitas**: Pilih WKN (Melemah), INTSF (Menguat), atau NC (Tanpa Perubahan)

3. **Generate dan Edit Preview**
   - Klik "Buat Draf Pesan"
   - Review pesan sandi dan terjemahan
   - Edit pesan atau terjemahan jika diperlukan
   - Klik "Simpan" untuk menyimpan perubahan

4. **Terbitkan Peringatan**
   - Klik "Terbitkan Peringatan"
   - Sistem akan mengirim pesan ke WhatsApp
   - Peringatan akan muncul di daftar peringatan aktif

### 🚫 Membatalkan Peringatan

1. **Pilih Peringatan**
   - Klik tombol cancel (X) pada peringatan yang ingin dibatalkan
   - Review detail peringatan yang akan dibatalkan

2. **Konfirmasi Pembatalan**
   - Pastikan semua fenomena telah mereda
   - Edit pesan pembatalan jika diperlukan
   - Edit terjemahan pembatalan jika diperlukan

3. **Terbitkan Pembatalan**
   - Klik "Ya, Batalkan Peringatan"
   - Sistem akan mengirim pesan pembatalan ke WhatsApp
   - Status peringatan akan berubah menjadi "Dibatalkan"

### 📊 Monitoring Dashboard

1. **Statistik Real-time**
   - Lihat jumlah peringatan aktif, expired, dan dibatalkan
   - Monitor auto-refresh status
   - Lihat waktu update terakhir

2. **Daftar Peringatan Aktif**
   - Review semua peringatan yang masih valid
   - Copy pesan untuk keperluan komunikasi
   - Monitor peringatan yang akan berakhir

3. **Auto-refresh**
   - Aktifkan/nonaktifkan auto-refresh
   - Manual refresh jika diperlukan
   - Monitor status koneksi

### 📁 Arsip dan Laporan

1. **Arsip Hari Ini**
   - Klik "Arsip Hari Ini" untuk melihat peringatan hari ini
   - Termasuk peringatan dan pembatalan

2. **Semua Peringatan**
   - Klik "Semua Peringatan" untuk database lengkap
   - Gunakan filter untuk mencari peringatan tertentu
   - Export ke PDF jika diperlukan

## 🔌 API Endpoints

### Peringatan
```
GET    /aerodrome/warnings              # Daftar peringatan aktif
POST   /aerodrome/warnings              # Buat peringatan baru
POST   /aerodrome/warnings/{id}/cancel  # Batalkan peringatan
GET    /aerodrome/warnings/statistics   # Statistik peringatan
GET    /aerodrome/warnings/next-sequence # Sequence number berikutnya
```

### Halaman
```
GET    /aerodrome                       # Dashboard utama
GET    /aerodrome/archive               # Arsip hari ini
GET    /aerodrome/all-warnings          # Semua peringatan
GET    /aerodrome/export-pdf            # Export PDF
```

## 🗄️ Struktur Database

### Tabel `aerodrome_warnings`
```sql
- id (Primary Key)
- airport_code (VARCHAR) - Kode bandara (WAHL)
- warning_number (VARCHAR) - Nomor peringatan
- sequence_number (INT) - Nomor urut harian
- start_time (DATETIME) - Waktu mulai validitas
- end_time (DATETIME) - Waktu berakhir validitas
- phenomena (JSON) - Array fenomena cuaca
- source (ENUM) - Sumber data (OBS/FCST)
- intensity (ENUM) - Intensitas (WKN/INTSF/NC)
- observation_time (DATETIME) - Waktu observasi (opsional)
- preview_message (TEXT) - Pesan sandi
- translation_message (TEXT) - Terjemahan Indonesia
- forecaster_id (INT) - ID forecaster
- forecaster_name (VARCHAR) - Nama forecaster
- forecaster_nip (VARCHAR) - NIP forecaster
- status (ENUM) - Status (ACTIVE/EXPIRED/CANCELLED)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

## 🔧 Troubleshooting

### Masalah Umum

1. **WhatsApp tidak terkirim**
   - Periksa konfigurasi WhatsApp di `.env`
   - Pastikan token dan phone_number_id benar
   - Cek log Laravel untuk error detail

2. **Auto-refresh tidak berfungsi**
   - Periksa koneksi internet
   - Restart browser
   - Cek console browser untuk error JavaScript

3. **Form tidak bisa disubmit**
   - Periksa CSRF token
   - Pastikan semua field required terisi
   - Cek validasi di browser console

4. **Database connection error**
   - Periksa konfigurasi database di `.env`
   - Pastikan MySQL server berjalan
   - Cek credentials database

### Log Files
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue logs
tail -f storage/logs/queue.log
```

### Commands Utama
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Restart queue
php artisan queue:restart

# Check routes
php artisan route:list

# Database
php artisan migrate:status
php artisan db:seed
```

## 🤝 Kontribusi

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📞 Support

Untuk bantuan dan dukungan teknis, silakan hubungi:
- **Email**: support@smard.local
- **WhatsApp**: +62 xxx-xxxx-xxxx
- **Documentation**: [Link ke dokumentasi lengkap]

## 📄 License

Project ini dilisensikan di bawah MIT License - lihat file [LICENSE](LICENSE) untuk detail.

---

**Aerodrome Warning System (SMARD)** - Sistem peringatan aerodrome modern untuk Stasiun Meteorologi Tunggul Wulung 🛩️🌤️
