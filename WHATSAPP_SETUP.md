# Setup WhatsApp Business API

Dokumentasi lengkap untuk mengkonfigurasi integrasi WhatsApp Business API dengan Aerodrome Warning System.

## 📋 Prerequisites

1. **Meta Developer Account**
   - Daftar di [Meta for Developers](https://developers.facebook.com/)
   - Verifikasi email dan nomor telepon

2. **WhatsApp Business Account**
   - Buat WhatsApp Business Account
   - Verifikasi nomor telepon bisnis

3. **Domain yang Valid**
   - Domain dengan SSL certificate (HTTPS)
   - Untuk development bisa menggunakan ngrok

## 🚀 Setup Step by Step

### 1. Buat Meta App

1. Buka [Meta for Developers](https://developers.facebook.com/)
2. Klik "Create App"
3. Pilih "Business" sebagai tipe aplikasi
4. Isi nama aplikasi dan email kontak
5. Klik "Create App"

### 2. Setup WhatsApp Business API

1. Di dashboard aplikasi, klik "Add Product"
2. Cari dan tambahkan "WhatsApp"
3. Klik "Set up" pada WhatsApp

### 4. Konfigurasi Phone Number

1. Klik "Add phone number"
2. Pilih nomor telepon yang sudah diverifikasi
3. Catat **Phone Number ID** (akan digunakan di konfigurasi)

### 5. Generate Access Token

1. Di menu "WhatsApp" → "Getting started"
2. Klik "Generate token"
3. Pilih nomor telepon yang sudah dikonfigurasi
4. Copy **Access Token** (akan digunakan di konfigurasi)

### 6. Setup Webhook

1. Di menu "WhatsApp" → "Configuration"
2. Klik "Edit" pada Webhook
3. Masukkan URL webhook: `https://yourdomain.com/webhook/whatsapp`
4. Masukkan Verify Token (bisa custom, misal: `smard_webhook_2024`)
5. Pilih events yang akan dikirim:
   - `messages`
   - `message_deliveries`
   - `message_reads`

### 7. Konfigurasi Environment

Edit file `.env`:
```env
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_TOKEN=your_access_token_here
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id_here
WHATSAPP_WEBHOOK_VERIFY_TOKEN=smard_webhook_2024
```

### 8. Setup Route Webhook

Pastikan route webhook sudah terdaftar di `routes/web.php`:
```php
Route::post('/webhook/whatsapp', [WhatsAppController::class, 'webhook']);
Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verify']);
```

### 9. Test Konfigurasi

1. Kirim pesan test dari WhatsApp ke nomor bisnis
2. Cek log Laravel untuk memastikan webhook diterima
3. Test pengiriman pesan dari aplikasi

## 🔧 Troubleshooting

### Webhook Verification Failed
- Pastikan URL webhook menggunakan HTTPS
- Periksa Verify Token di Meta dan aplikasi
- Pastikan route webhook sudah terdaftar

### Access Token Invalid
- Generate ulang Access Token di Meta
- Pastikan token tidak expired
- Periksa permission aplikasi

### Phone Number Not Verified
- Verifikasi nomor telepon di Meta
- Pastikan nomor aktif dan bisa menerima SMS
- Tunggu 24 jam setelah verifikasi

### Messages Not Delivered
- Periksa format pesan
- Pastikan nomor tujuan valid
- Cek rate limiting Meta

## 📱 Format Pesan

### Peringatan Aerodrome
```
*AERODROME WARNING*

*WAHL AD WRNG 9 VALID 150331/150431 TS GR FCST INTSF=*

- AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR 9
- BERLAKU TANGGAL 15 AGUSTUS 2025
- ANTARA PUKUL 03.31 - 04.31 UTC
- BADAI GUNTUR, HUJAN ES
- BERDASARKAN DATA FORECAST
- DIPRAKIRAKAN INTENSITAS AKAN MENINGKAT
```

### Pembatalan Peringatan
```
*AERODROME WARNING CANCELLATION*

*WAHL AD WRNG 10 VALID 150400/150431 CNL AD WRNG 9 150331/150431=*

- AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR 10
- BERLAKU TANGGAL 15 AGUSTUS 2025
- ANTARA PUKUL 04.00 - 04.31 UTC
- BAHWA PERINGATAN DINI CUACA NOMOR 9 (TANGGAL 15 AGUSTUS 2025, ANTARA PUKUL 03.31 - 04.31 UTC) TELAH BERAKHIR / DIBATALKAN
```

## 🔒 Security Best Practices

1. **Environment Variables**
   - Jangan hardcode credentials di kode
   - Gunakan environment variables
   - Rotate access token secara berkala

2. **Webhook Security**
   - Verifikasi signature webhook
   - Implement rate limiting
   - Log semua webhook events

3. **Error Handling**
   - Implement retry mechanism
   - Log semua error
   - Monitor delivery status

## 📊 Monitoring

### Log Files
```bash
# WhatsApp specific logs
tail -f storage/logs/whatsapp.log

# Laravel logs
tail -f storage/logs/laravel.log
```

### Meta Analytics
1. Buka Meta for Developers
2. Pilih aplikasi
3. Klik "Analytics" → "WhatsApp"
4. Monitor delivery rates dan error rates

## 🔄 Maintenance

### Regular Tasks
1. **Monthly**: Review dan rotate access tokens
2. **Weekly**: Check webhook delivery rates
3. **Daily**: Monitor error logs
4. **As needed**: Update webhook URL jika domain berubah

### Backup Configuration
Simpan konfigurasi penting:
- Access Token
- Phone Number ID
- Webhook URL
- Verify Token

## 📞 Support

Untuk bantuan teknis WhatsApp Business API:
- [Meta Developer Documentation](https://developers.facebook.com/docs/whatsapp)
- [WhatsApp Business API Support](https://developers.facebook.com/support/)
- [Community Forum](https://developers.facebook.com/community/)

---

**Note**: Pastikan selalu mengikuti [WhatsApp Business Policy](https://www.whatsapp.com/legal/business-policy/) dan [Meta Platform Terms](https://developers.facebook.com/terms/).
