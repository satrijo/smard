# 👥 Forecaster Update Documentation

Dokumentasi update daftar forecaster SMARD (Aerodrome Warning System).

## 📋 Daftar Forecaster Baru

### **Updated Forecaster List**

| ID | Nama | NIP |
|----|------|-----|
| 1 | Nurfaijin, S.Si., M.Sc. | 197111101997031001 |
| 2 | Sawardi, S.T | 197109251992021001 |
| 3 | Suharti | 197208181993012001 |
| 4 | Hakim Mubasyir, S.Kom | 197812221998031001 |
| 5 | Agung Surono, SP | 197912182000031001 |
| 6 | Deas Achmad Rivai, S. Kom, M. Si | 198906022010121001 |
| 7 | Gaib Prawoto, A.Md | 197507202009111001 |
| 8 | Rendi Krisnawan, A.Md | 198612122008121001 |
| 9 | Adnan Dendy Mardika, S.Kom | 198908192010121001 |
| 10 | Feriharti Nugrohowati, S.T | 198909052010122001 |
| 11 | Desi Luqman Az Zahro, S.Kom | 198912272012122001 |
| 12 | Nurmaya, S.Tr.Met. | 199101012009112001 |
| 13 | Purwanggoro Sukipiadi, S.Kom | 198311222012121001 |
| 14 | Khamim Sodik, S.Kom | 198408072012121001 |
| 15 | Satriyo Unggul Wicaksono | 199403122013121001 |

## 🔄 Files yang Diupdate

### **1. Frontend Component**
- **File**: `resources/js/components/forms/AerodromeWarningForm.vue`
- **Change**: Update array `forecasters` dengan data baru

### **2. Database Seeder**
- **File**: `database/seeders/AerodromeWarningSeeder.php`
- **Change**: Update sample data forecaster

### **3. Update Script**
- **File**: `update_forecasters.php`
- **Purpose**: Script untuk update data forecaster yang sudah ada di database

## 🚀 Cara Update

### **Automatic Update (Recommended)**
```bash
# Deploy dengan script deployment
./deploy.sh
# atau
.\deploy.ps1
```

### **Manual Update**
```bash
# 1. Update frontend code
git pull origin main

# 2. Rebuild containers
docker compose up -d --build

# 3. Run forecaster update script
docker compose exec app php update_forecasters.php
```

### **Standalone Forecaster Update**
```bash
# Jika hanya ingin update forecaster tanpa full deployment
docker compose exec app php update_forecasters.php
```

## 📊 Script Update Forecaster

### **Fitur Script `update_forecasters.php`**
- ✅ Update data forecaster yang sudah ada di database
- ✅ Preserve data peringatan yang sudah dibuat
- ✅ Update nama dan NIP forecaster
- ✅ Report jumlah data yang diupdate
- ✅ Safe update (tidak menghapus data)

### **Output Script**
```
🔄 Starting forecaster data update...
✅ Updated 5 warnings for forecaster ID 1: Nurfaijin, S.Si., M.Sc.
✅ Updated 3 warnings for forecaster ID 2: Sawardi, S.T

🎉 Update completed!
📊 Total warnings updated: 8
👥 Total forecasters: 15

📋 New forecaster list:
  1. Nurfaijin, S.Si., M.Sc. (197111101997031001)
  2. Sawardi, S.T (197109251992021001)
  ...

✅ Forecaster data has been updated successfully!
```

## 🔧 Database Impact

### **Tables Affected**
- **`aerodrome_warnings`**: Update `forecaster_name` dan `forecaster_nip`

### **Data Preservation**
- ✅ Semua peringatan yang sudah dibuat tetap ada
- ✅ Hanya nama dan NIP forecaster yang diupdate
- ✅ Tidak ada data yang hilang

### **Migration Strategy**
- **Soft Update**: Update existing records
- **No Schema Changes**: Tidak ada perubahan struktur tabel
- **Backward Compatible**: Tetap kompatibel dengan data lama

## 🎯 User Experience

### **Before Update**
- User melihat daftar forecaster lama
- Data peringatan menggunakan nama forecaster lama

### **After Update**
- User melihat daftar forecaster baru (15 forecaster)
- Data peringatan lama tetap ada dengan nama forecaster yang diupdate
- Peringatan baru menggunakan nama forecaster yang benar

## 🔒 Security & Validation

### **Data Validation**
- ✅ NIP format validation
- ✅ Nama forecaster tidak boleh kosong
- ✅ ID forecaster harus unik

### **Access Control**
- ✅ Hanya admin yang bisa update forecaster
- ✅ Script hanya bisa dijalankan di environment yang tepat

## 📞 Support

### **Jika Ada Masalah**
1. **Check logs**: `docker compose logs -f app`
2. **Verify data**: `docker compose exec app php artisan tinker`
3. **Rollback**: Restore dari backup database

### **Emergency Contacts**
- **System Admin**: admin@smard.local
- **Developer**: dev@smard.local

---

**Forecaster Update Documentation** - Update daftar forecaster SMARD 👥🔄
