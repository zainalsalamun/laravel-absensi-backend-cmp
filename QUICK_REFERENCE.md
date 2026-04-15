# 🎯 Face Recognition API - Quick Reference Card

---

## 🚀 Quick Start (3 Steps)

```bash
# 1. Setup (run once)
./setup-and-test.sh

# 2. Open Postman
# Import: POSTMAN_Face_Recognition_Collection.json

# 3. Start testing!
```

---

## 📍 API Endpoints Summary

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/login` | ❌ | Login & get token |
| POST | `/api/face-enrollment` | ✅ | Enroll face photo |
| GET | `/api/face-status` | ✅ | Check enrollment status |
| POST | `/api/face-verify` | ✅ | Verify face match |
| DELETE | `/api/face-enrollment` | ✅ | Remove enrollment |
| POST | `/api/checkin` | ✅ | Check-in with face |
| POST | `/api/checkout` | ✅ | Check-out with face |
| GET | `/api/is-checkin` | ✅ | Check attendance status |

---

## 🔑 Test Credentials

```
Email: testuser@example.com
Password: password
```

---

## 📋 Testing Flow (Copy-Paste Ready)

### Step 1: Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"testuser@example.com","password":"password"}'

# Response: {"user":{...},"token":"1|xxxxx"}
# Save the token!
```

### Step 2: Check Face Status
```bash
curl http://localhost:8000/api/face-status \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Expected: {"is_enrolled":false,...}
```

### Step 3: Enroll Face
```bash
curl -X POST http://localhost:8000/api/face-enrollment \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "photo=@/absolute/path/to/your-face.jpg"

# Expected: {"success":true,"message":"Face enrolled successfully",...}
```

### Step 4: Verify Face
```bash
curl -X POST http://localhost:8000/api/face-verify \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "photo=@/absolute/path/to/your-face.jpg"

# Expected: {"is_match":true,"similarity":95.5,...}
```

### Step 5: Check-in
```bash
curl -X POST http://localhost:8000/api/checkin \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "latitude=-7.747033" \
  -F "longitude=110.355398" \
  -F "photo=@/absolute/path/to/your-face.jpg"

# Expected: {"message":"Checkin success","attendance":{...}}
```

### Step 6: Check-out
```bash
curl -X POST http://localhost:8000/api/checkout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "latitude=-7.747033" \
  -F "longitude=110.355398" \
  -F "photo=@/absolute/path/to/your-face.jpg"

# Expected: {"message":"Checkout success","attendance":{...}}
```

---

## 🐛 Common Issues & Fixes

| Issue | Solution |
|-------|----------|
| 401 Unauthorized | Token expired, login again |
| 500 Server Error | Check logs: `docker compose logs app` |
| Face enrollment failed | Use clearer photo, frontal face, good lighting |
| Face verification failed | Use same photo as enrollment, check similarity score |
| Migration error | Check DB connection in `.env` |

---

## 📁 Important Files

| File | Purpose |
|------|---------|
| `POSTMAN_Face_Recognition_Collection.json` | Postman collection |
| `TESTING_GUIDE.md` | Detailed testing guide |
| `FACE_RECOGNITION_README.md` | Implementation docs |
| `API_DOCUMENTATION.md` | Full API documentation |
| `setup-and-test.sh` | Quick setup script |

---

## 🔧 Docker Commands

```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# View logs
docker compose logs app

# Access container shell
docker compose exec app bash

# Run migration
docker compose exec app php artisan migrate

# Fresh migration with seed
docker compose exec app php artisan migrate:fresh --seed

# Clear cache
docker compose exec app php artisan optimize:clear
```

---

## 📊 Expected Similarity Scores

| Scenario | Expected Similarity | Result |
|----------|-------------------|--------|
| Same photo (identical) | 90-100% | ✅ Match |
| Same person, different photo | 60-85% | ✅/❌ Depends |
| Different person | < 50% | ❌ No match |

**Threshold:** 65% (configurable in FaceRecognitionService.php)

---

## 🎯 Success Criteria

All tests pass if:
- ✅ Can enroll face
- ✅ Face verification with same photo = match (>65%)
- ✅ Check-in with face = success
- ✅ Check-out with face = success
- ✅ Attendance record created

---

**Last Updated:** April 14, 2026
