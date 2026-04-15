# 🧪 Face Recognition API - Testing Guide

Panduan lengkap untuk testing Face Recognition Attendance System menggunakan Postman atau cURL.

---

## 📋 Prerequisites

### Option 1: Menggunakan Postman (Recommended)
1. Install Postman: https://www.postman.com/downloads/
2. Import collection: `POSTMAN_Face_Recognition_Collection.json`
3. Update variable `base_url` jika perlu (default: `http://localhost:8000`)

### Option 2: Menggunakan cURL
- Terminal dengan cURL terinstall
- Foto wajah untuk testing (simpan di folder project)

---

## 🚀 Setup & Start Server

### Step 1: Pastikan Docker Running
```bash
# Start containers
docker compose up -d

# Cek status
docker compose ps
```

### Step 2: Run Migration & Seeder
```bash
# Masuk ke container
docker compose exec app bash

# Jalankan migration
php artisan migrate

# Fresh migration dengan seed
php artisan migrate:fresh --seed

# Create storage link
php artisan storage:link

# Exit container
exit
```

### Step 3: Start Laravel Server (jika tidak pakai nginx)
```bash
# Access container shell
docker compose exec app bash

# Start development server
php artisan serve --host=0.0.0.0 --port=8000

# Atau gunakan artisan command via docker
docker compose exec app php artisan serve --host=0.0.0.0 --port=8000
```

**Server siap di:** `http://localhost:8000`

---

## 📝 Testing Flow with Postman

### **TEST 1: Login & Get Token**

1. Buka Postman Collection
2. Navigate to: **1. Authentication** → **Login - Test User**
3. Click **Send**

**Request:**
```
POST http://localhost:8000/api/login
Content-Type: application/json

{
    "email": "testuser@example.com",
    "password": "password"
}
```

**Expected Response (200):**
```json
{
    "user": {
        "id": 2,
        "name": "Test User Face",
        "email": "testuser@example.com",
        "role": "user",
        ...
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxx"
}
```

✅ **Success jika:** 
- Status code: 200
- Token otomatis saved ke collection variable
- User info muncul

❌ **Failed jika:** 
- 401 Invalid credentials → Cek email/password
- 500 Server error → Cek logs: `docker compose logs app`

---

### **TEST 2: Check Face Status (Before Enrollment)**

1. Navigate to: **2. Face Recognition** → **Check Face Status (Before Enrollment)**
2. Click **Send**

**Request:**
```
GET http://localhost:8000/api/face-status
Authorization: Bearer {{token}}
```

**Expected Response (200):**
```json
{
    "is_enrolled": false,
    "message": "No face enrollment found. Please enroll your face.",
    "data": null
}
```

✅ **Success jika:** `is_enrolled: false`

---

### **TEST 3: Enroll Face**

1. Navigate to: **2. Face Recognition** → **Enroll Face**
2. **PENTING:** Update file path di Body → `photo`
   - Ganti `/path/to/your/face-photo.jpg` dengan path foto wajah Anda
   - Contoh: `/Users/macbookpro/Desktop/my-face.jpg`
3. Click **Send**

**Request:**
```
POST http://localhost:8000/api/face-enrollment
Authorization: Bearer {{token}}
Content-Type: multipart/form-data

Form Data:
- photo: [file] your-face-photo.jpg
```

**Expected Response (200):**
```json
{
    "success": true,
    "message": "Face enrolled successfully. You can now use face recognition for attendance.",
    "data": {
        "enrollment_id": 1,
        "photo_url": "/storage/assets/faces/1234567890.jpg"
    }
}
```

✅ **Success jika:** 
- `success: true`
- Message: "Face enrolled successfully..."
- `enrollment_id` ada

❌ **Failed jika:**
- 400 "Failed to extract face features" → Foto blur atau tidak jelas
- 422 Validation error → Format file salah atau ukuran > 2MB

**💡 Tips untuk enrollment sukses:**
- Gunakan foto wajah yang jelas dan terang
- Pastikan wajah terlihat penuh (tidak terpotong)
- Pencahayaan cukup (tidak gelap)
- Background tidak terlalu ramai
- Format: jpg, jpeg, png
- Size: max 2MB

---

### **TEST 4: Check Face Status (After Enrollment)**

1. Navigate to: **2. Face Recognition** → **Check Face Status (After Enrollment)**
2. Click **Send**

**Request:**
```
GET http://localhost:8000/api/face-status
Authorization: Bearer {{token}}
```

**Expected Response (200):**
```json
{
    "is_enrolled": true,
    "message": "Face is enrolled",
    "data": {
        "enrollment_id": 1,
        "photo_url": "/storage/assets/faces/1234567890.jpg",
        "created_at": "2026-04-14T10:00:00.000000Z",
        "updated_at": "2026-04-14T10:00:00.000000Z"
    }
}
```

✅ **Success jika:** `is_enrolled: true`

---

### **TEST 5: Verify Face - Same Photo (Harus Match)**

1. Navigate to: **2. Face Recognition** → **Verify Face - Same Photo**
2. **PENTING:** Gunakan foto yang **SAMA PERSIS** dengan saat enrollment
3. Update file path di Body → `photo`
4. Click **Send**

**Request:**
```
POST http://localhost:8000/api/face-verify
Authorization: Bearer {{token}}
Content-Type: multipart/form-data

Form Data:
- photo: [file] same-face-photo.jpg
```

**Expected Response (200):**
```json
{
    "success": true,
    "message": "Face verified successfully",
    "is_match": true,
    "similarity": 95.5,
    "threshold": 65,
    "needs_enrollment": false
}
```

✅ **Success jika:** 
- `is_match: true`
- `similarity > 65` (seharusnya tinggi >80% untuk foto sama persis)

❌ **Failed jika:**
- `is_match: false` → Algorithm issue, coba enroll ulang
- Similarity sangat rendah (<50%) → Foto berbeda atau corrupt

---

### **TEST 6: Verify Face - Different Photo (Testing Accuracy)**

1. Navigate to: **2. Face Recognition** → **Verify Face - Different Photo**
2. Gunakan foto wajah **BERBEDA** (tapi masih orang yang sama)
   - Contoh: Foto dengan angle berbeda, pencahayaan berbeda
3. Update file path
4. Click **Send**

**Expected Response (200 atau 403):**
```json
{
    "success": true,
    "message": "Face verified successfully" atau "Face does not match",
    "is_match": true atau false,
    "similarity": 72.3,
    "threshold": 65,
    "needs_enrollment": false
}
```

✅ **Expected behavior:**
- Similarity lebih rendah dari foto sama persis
- Jika similarity > 65 → `is_match: true` (recognized)
- Jika similarity < 65 → `is_match: false` (not recognized)

**💡 Ini normal untuk basic image recognition.** Sistem ini bukan AI-based, jadi akurasi tergantung konsistensi foto.

---

### **TEST 7: Check-in with Face Verification**

1. Navigate to: **3. Attendance with Face** → **Check-in with Face**
2. Update file path `photo` dengan foto wajah
3. Pastikan coordinates sudah benar (default: company location)
4. Click **Send**

**Request:**
```
POST http://localhost:8000/api/checkin
Authorization: Bearer {{token}}
Content-Type: multipart/form-data

Form Data:
- latitude: -7.747033
- longitude: 110.355398
- photo: [file] your-face-photo.jpg
```

**Expected Response (200):**
```json
{
    "message": "Checkin success",
    "attendance": {
        "user_id": 2,
        "date": "2026-04-14",
        "time_in": "08:30:45",
        "latlon_in": "-7.747033,110.355398",
        "photo_in": "assets/attendances/xyz.jpg",
        ...
    }
}
```

✅ **Success jika:** 
- Message: "Checkin success"
- Attendance record created

❌ **Failed jika:**
- 403 "Face verification failed" → Foto tidak match dengan enrolled face
- 403 "Face enrollment required" → Belum enroll face
- 400 "Checkin first" (saat checkout) → Belum checkin

---

### **TEST 8: Check if Checked-in**

1. Navigate to: **3. Attendance with Face** → **Check if Checked-in**
2. Click **Send**

**Request:**
```
GET http://localhost:8000/api/is-checkin
Authorization: Bearer {{token}}
```

**Expected Response (200):**
```json
{
    "checkedin": true,
    "checkedout": false
}
```

✅ **Success jika:** `checkedin: true`

---

### **TEST 9: Check-out with Face Verification**

1. Navigate to: **3. Attendance with Face** → **Check-out with Face**
2. Update file path `photo`
3. Click **Send**

**Request:**
```
POST http://localhost:8000/api/checkout
Authorization: Bearer {{token}}
Content-Type: multipart/form-data

Form Data:
- latitude: -7.747033
- longitude: 110.355398
- photo: [file] your-face-photo.jpg
```

**Expected Response (200):**
```json
{
    "message": "Checkout success",
    "attendance": {
        "user_id": 2,
        "date": "2026-04-14",
        "time_in": "08:30:45",
        "time_out": "17:05:12",
        "latlon_in": "-7.747033,110.355398",
        "latlon_out": "-7.747033,110.355398",
        "photo_in": "assets/attendances/xyz.jpg",
        "photo_out": "assets/attendances/abc.jpg",
        ...
    }
}
```

✅ **Success jika:** 
- Message: "Checkout success"
- `time_out` terisi

---

### **TEST 10: Get My Attendances**

1. Navigate to: **3. Attendance with Face** → **Get My Attendances**
2. Click **Send**

**Request:**
```
GET http://localhost:8000/api/api-attendances?date=2026-04-14
Authorization: Bearer {{token}}
```

**Expected Response (200):**
```json
{
    "message": "Success",
    "data": [
        {
            "id": 1,
            "user_id": 2,
            "date": "2026-04-14",
            "time_in": "08:30:45",
            "time_out": "17:05:12",
            "latlon_in": "-7.747033,110.355398",
            "latlon_out": "-7.747033,110.355398",
            "photo_in": "assets/attendances/xyz.jpg",
            "photo_out": "assets/attendances/abc.jpg",
            ...
        }
    ]
}
```

✅ **Success jika:** Attendance record muncul dengan data lengkap

---

## 🧪 Testing dengan cURL (Alternative)

Jika tidak mau pakai Postman, bisa pakai cURL:

### 1. Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "password": "password"
  }'

# Save token dari response
TOKEN="1|your_token_here"
```

### 2. Check Face Status
```bash
curl -X GET http://localhost:8000/api/face-status \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Enroll Face
```bash
curl -X POST http://localhost:8000/api/face-enrollment \
  -H "Authorization: Bearer $TOKEN" \
  -F "photo=@/path/to/your/face.jpg"
```

### 4. Verify Face
```bash
curl -X POST http://localhost:8000/api/face-verify \
  -H "Authorization: Bearer $TOKEN" \
  -F "photo=@/path/to/your/face.jpg"
```

### 5. Check-in with Face
```bash
curl -X POST http://localhost:8000/api/checkin \
  -H "Authorization: Bearer $TOKEN" \
  -F "latitude=-7.747033" \
  -F "longitude=110.355398" \
  -F "photo=@/path/to/your/face.jpg"
```

### 6. Check-out with Face
```bash
curl -X POST http://localhost:8000/api/checkout \
  -H "Authorization: Bearer $TOKEN" \
  -F "latitude=-7.747033" \
  -F "longitude=110.355398" \
  -F "photo=@/path/to/your/face.jpg"
```

---

## 🎯 Complete Test Scenario

### **Scenario 1: Happy Path (Full Flow)**

```
1. Login → Get token ✅
2. Check face status → Should be not enrolled ✅
3. Enroll face with clear photo ✅
4. Check face status → Should be enrolled ✅
5. Verify face with same photo → Should match (>80% similarity) ✅
6. Check-in with face photo → Success ✅
7. Check if checked-in → Should be true ✅
8. Check-out with face photo → Success ✅
9. Get attendances → Record exists ✅
```

### **Scenario 2: Face Verification Failed**

```
1. Login ✅
2. Enroll face with photo A ✅
3. Verify face with photo B (different person) → Should fail or low similarity ✅
4. Check-in with photo B → Should get 403 Face verification failed ✅
```

### **Scenario 3: No Enrollment**

```
1. Login ✅
2. Skip enrollment
3. Check-in with face → Should get 403 Face enrollment required ✅
```

---

## 🐛 Troubleshooting

### **Issue: 500 Internal Server Error**

**Check logs:**
```bash
docker compose logs app | tail -50
# atau
docker compose exec app tail -f storage/logs/laravel.log
```

**Common fixes:**
```bash
# Clear cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear

# Re-link storage
docker compose exec app php artisan storage:link

# Check permissions
docker compose exec app chmod -R 775 storage bootstrap/cache
```

---

### **Issue: Face enrollment failed**

**Possible causes:**
1. Foto terlalu blur
2. Foto terlalu kecil (resolution rendah)
3. Format tidak support (webp, bmp, dll)
4. File size > 2MB

**Solution:**
- Gunakan foto dengan resolusi minimal 640x480
- Pastikan wajah jelas terlihat
- Format: jpg, jpeg, png only
- Size: < 2MB

---

### **Issue: Face verification failed (similarity rendah)**

**Expected behavior untuk basic image recognition:**
- Foto sama persis: 80-100% similarity
- Foto berbeda (orang sama): 50-80% similarity
- Foto berbeda (orang berbeda): < 50% similarity

**Tips improve accuracy:**
1. Enroll dengan foto yang sangat jelas
2. Gunakan foto dengan pencahayaan bagus
3. Hindari shadow di wajah
4. Face langsung menghadap kamera (frontal)

---

### **Issue: Token expired**

```bash
# Login ulang untuk get new token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"testuser@example.com","password":"password"}'
```

---

## 📊 Test Results Checklist

Gunakan checklist ini untuk tracking testing progress:

```
[ ] Test 1:  Login berhasil
[ ] Test 2:  Face status = not enrolled
[ ] Test 3:  Face enrollment success
[ ] Test 4:  Face status = enrolled
[ ] Test 5:  Face verify same photo = match (>65%)
[ ] Test 6:  Face verify different photo = varies
[ ] Test 7:  Check-in with face = success
[ ] Test 8:  Check if checked-in = true
[ ] Test 9:  Check-out with face = success
[ ] Test 10: Get attendances = record exists

Additional Tests:
[ ] Face enrollment without token = 401 Unauthorized
[ ] Check-in without enrollment = 403 Face enrollment required
[ ] Check-in with wrong face = 403 Face verification failed
[ ] Update face enrollment = success
[ ] Remove face enrollment = success
[ ] Re-enroll after remove = success
```

---

## 📸 Sample Test Data

Jika butuh sample foto untuk testing:

**Option 1: Pakai foto sendiri**
- Siapkan 2-3 foto wajah Anda dengan angle/pencahayaan berbeda
- Simpan di folder project untuk mudah diakses

**Option 2: Generate test images**
```bash
# Create dummy image folder
mkdir -p test-images

# Download sample face photos (dari placeholder services)
# Atau pakai foto sendiri
```

**Recommended:**
- `face-1.jpg` - Foto frontal, pencahayaan terang
- `face-2.jpg` - Foto angle sedikit berbeda
- `face-3.jpg` - Foto dengan pencahayaan berbeda

---

## 📞 Support

Jika ada issue saat testing:
1. Cek logs: `docker compose logs app`
2. Cek database: `docker compose exec app php artisan tinker`
3. Clear cache: `docker compose exec app php artisan optimize:clear`

---

**Last Updated:** April 14, 2026
**Status:** Ready for testing ✅
