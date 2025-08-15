# WhatsApp Integration Setup

## Overview
Sistem Aerodrome Warning telah diintegrasikan dengan WhatsApp untuk mengirim notifikasi otomatis ketika forecaster menerbitkan atau membatalkan peringatan.

## Konfigurasi

### 1. Environment Variables
Tambahkan konfigurasi berikut ke file `.env`:

```env
# WhatsApp Configuration
WHATSAPP_ENABLED=true
WHATSAPP_API_URL=https://wa-gw.rack.my.id/send-message
WHATSAPP_DEFAULT_NUMBER=6282111119138
WHATSAPP_GROUP=true
```

**Penjelasan Konfigurasi:**
- `WHATSAPP_ENABLED`: Aktifkan/nonaktifkan fitur WhatsApp (true/false)
- `WHATSAPP_API_URL`: URL endpoint API WhatsApp gateway
- `WHATSAPP_DEFAULT_NUMBER`: Nomor WhatsApp default untuk menerima pesan
- `WHATSAPP_GROUP`: Kirim ke grup WhatsApp (true) atau personal chat (false)

### 2. Konfigurasi File
File konfigurasi WhatsApp berada di `config/whatsapp.php` dan dapat dikustomisasi sesuai kebutuhan.

## Fitur

### 1. Notifikasi Peringatan Baru
Ketika forecaster menerbitkan peringatan baru, sistem akan otomatis mengirim pesan WhatsApp yang berisi:
- 🚨 Emoji peringatan
- Sandi peringatan (format standar)
- Terjemahan dalam Bahasa Indonesia
- Informasi forecaster (nama dan NIP)
- Waktu penerbitan

### 2. Notifikasi Pembatalan
Ketika forecaster membatalkan peringatan, sistem akan mengirim pesan WhatsApp yang berisi:
- ❌ Emoji pembatalan
- Sandi pembatalan
- Terjemahan pembatalan
- Informasi peringatan yang dibatalkan
- Informasi forecaster yang melakukan pembatalan
- Waktu pembatalan

## Format Pesan

### Peringatan Baru
```
*AERODROME WARNING*

WAHL AD WRNG 01 VALID 130000/130200 VIS 3000M BR OBS AT 0000Z NC=

- AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR 1,
- BERLAKU TANGGAL 13 AGUSTUS 2025,
- ANTARA PUKUL 00.00 - 02.00 UTC,
- VISIBILITY 3000 METER,
- TERJADI KABUT TIPIS (HALIMUN) / MIST,
- BERDASARKAN DATA OBSERVASI PUKUL 00.00 UTC,
- DIPRAKIRAKAN INTENSITAS AKAN TETAP TIDAK ADA PERUBAHAN.
```

### Pembatalan
```
*PEMBATALAN AERODROME WARNING*

WAHL AD WRNG 02 VALID 130100/130200 CNL AD WRNG 01 130000/130200=

- AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR 2,
- BERLAKU TANGGAL 13 AGUSTUS 2025,
- ANTARA PUKUL 01.00 - 02.00 UTC,
- BAHWA PERINGATAN DINI CUACA NOMOR 1 (TANGGAL 13 AGUSTUS 2025, ANTARA PUKUL 00.00 - 02.00 UTC) TELAH BERAKHIR / DIBATALKAN.
```

## Troubleshooting

### 1. WhatsApp Tidak Terkirim
- Periksa apakah `WHATSAPP_ENABLED=true` di file `.env`
- Periksa log Laravel di `storage/logs/laravel.log` untuk error detail
- Pastikan API endpoint WhatsApp dapat diakses

### 2. Error "WhatsApp belum terkoneksi"
Jika Anda mendapatkan error ini, berarti WhatsApp gateway server belum siap:
- Hubungi administrator WhatsApp gateway untuk memastikan server sudah running
- Pastikan WhatsApp sudah login di gateway server
- Tunggu beberapa menit dan coba lagi

### 3. SSL Certificate Error
Jika terjadi SSL certificate error di development environment:
- Kode sudah dihandle dengan `Http::withoutVerifying()`
- Untuk production, pastikan SSL certificate valid

### 2. Format Pesan Salah
- Periksa konfigurasi template di `config/whatsapp.php`
- Pastikan model `AerodromeWarning` memiliki method `generateStandardMessage()` dan `generateIndonesianTranslation()`

### 3. Nomor WhatsApp Salah
- Periksa `WHATSAPP_DEFAULT_NUMBER` di file `.env`
- Pastikan format nomor sesuai (628xxxxxxxxxx)

## Logging
Semua aktivitas pengiriman WhatsApp akan dicatat di log Laravel dengan level:
- `info`: Berhasil dikirim
- `error`: Gagal dikirim

## Disable Notifications
Untuk menonaktifkan notifikasi WhatsApp, set `WHATSAPP_ENABLED=false` di file `.env`.

## Testing

### 1. Test WhatsApp Service
```bash
php artisan whatsapp:test
```

Atau dengan pesan kustom:
```bash
php artisan whatsapp:test --message="Pesan test kustom"
```

### 2. Resend WhatsApp Notification
Untuk mengirim ulang notifikasi untuk peringatan tertentu:

```bash
# Resend warning notification
php artisan whatsapp:resend 1

# Resend cancellation notification
php artisan whatsapp:resend 2 --type=cancellation
```

## Command List
- `whatsapp:test` - Test WhatsApp service dengan pesan sederhana
- `whatsapp:status` - Cek status koneksi WhatsApp API
- `whatsapp:test-template` - Test template pesan peringatan dengan data sample
- `whatsapp:resend {warning_id}` - Kirim ulang notifikasi untuk peringatan tertentu
