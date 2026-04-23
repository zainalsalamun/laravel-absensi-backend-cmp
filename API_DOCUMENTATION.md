# Laravel Absensi API Documentation

Dokumentasi ini disesuaikan dengan endpoint yang memang terdaftar di `routes/api.php` dan method controller yang sudah diimplementasikan.

- Base URL: `http://localhost:8000/api`
- Authorization: `Bearer {token}`
- Kecuali `POST /login`, semua endpoint membutuhkan token Sanctum

## 1. Authentication

### POST `/login`

Login user dan mengembalikan access token.

Payload:

- `email` (required, email)
- `password` (required)

Response `200`:

```json
{
  "user": {},
  "token": "1|xxxxxxxxxxxxxx"
}
```

Response `401`:

```json
{
  "message": "Invalid credentials"
}
```

### POST `/logout`

Menghapus token yang sedang dipakai.

Response `200`:

```json
{
  "message": "Logged out"
}
```

### POST `/update-profile`

Upload foto profil user yang sedang login.

Payload `multipart/form-data`:

- `image` (required, jpeg/png/jpg, max 2MB)

Response `200`:

```json
{
  "message": "Profile updated",
  "user": {}
}
```

### POST `/update-fcm-token`

Update token Firebase Cloud Messaging.

Payload:

- `fcm_token` (required)

Response `200`:

```json
{
  "message": "FCM token updated"
}
```

## 2. User

### GET `/user`

Mengambil data user login beserta relasi `shift`.

### GET `/api-user/{id}`

Mengambil data user berdasarkan ID.

Response `200`:

```json
{
  "status": "Success",
  "message": "User found",
  "data": {}
}
```

### POST `/api-user/edit`

Update biodata user.

Payload `multipart/form-data`:

- `id` (required)
- `name` (required)
- `email` (required, email)
- `phone` (required)
- `image` (optional, jpeg/png/jpg, max 2MB)

Response `200`:

```json
{
  "status": "Success",
  "message": "Update user success",
  "data": {}
}
```

## 3. Company

### GET `/company`

Mengambil data company aktif. Saat ini controller mengambil `Company::find(1)`.

Response `200`:

```json
{
  "company": {}
}
```

## 4. Attendance

### POST `/checkin`

Mencatat check-in harian.

Payload `multipart/form-data`:

- `latitude` (required)
- `longitude` (required)
- `photo` (required, image, max 2MB)

Catatan:

- Jika `company.attendance_type = face`, endpoint ini akan memverifikasi wajah user terlebih dahulu.

Response `200`:

```json
{
  "message": "Checkin success",
  "attendance": {}
}
```

Response `403` saat verifikasi wajah gagal:

```json
{
  "message": "Face verification failed. Please try again or contact admin.",
  "similarity": 0
}
```

### POST `/checkout`

Mencatat check-out harian.

Payload `multipart/form-data`:

- `latitude` (required)
- `longitude` (required)
- `photo` (required, image, max 2MB)

Response `200`:

```json
{
  "message": "Checkout success",
  "attendance": {}
}
```

Response `400`:

```json
{
  "message": "Checkin first"
}
```

### GET `/is-checkin`

Cek status check-in/check-out user untuk hari ini.

Response `200`:

```json
{
  "checkedin": true,
  "checkedout": false
}
```

### GET `/api-attendances`

Riwayat absensi milik user login.

Query params opsional:

- `date` format `YYYY-MM-DD`

Response `200`:

```json
{
  "message": "Success",
  "data": []
}
```

## 5. Face Recognition

### POST `/face-enrollment`

Enroll wajah user.

Payload `multipart/form-data`:

- `photo` (required, jpeg/png/jpg, max 2MB)

Response `200`:

```json
{
  "success": true,
  "message": "Face enrolled successfully. You can now use face recognition for attendance.",
  "data": {
    "enrollment_id": 1,
    "photo_url": "/storage/assets/faces/xyz.jpg"
  }
}
```

### PUT `/face-enrollment`

Update/re-enroll wajah. Payload dan response sama dengan endpoint enroll.

### DELETE `/face-enrollment`

Hapus enrollment wajah.

Response `200`:

```json
{
  "success": true,
  "message": "Face enrollment removed successfully"
}
```

### POST `/face-verify`

Verifikasi wajah terhadap data yang sudah dienroll.

Payload `multipart/form-data`:

- `photo` (required, jpeg/png/jpg, max 2MB)

Response `200` jika match:

```json
{
  "success": true,
  "message": "Face verified successfully",
  "is_match": true,
  "similarity": 78.5,
  "threshold": 65,
  "needs_enrollment": false
}
```

Response `403` jika tidak match:

```json
{
  "success": true,
  "message": "Face does not match",
  "is_match": false,
  "similarity": 45.2,
  "threshold": 65,
  "needs_enrollment": false
}
```

Response `404` jika belum enroll:

```json
{
  "success": false,
  "message": "No face enrollment found. Please enroll your face first.",
  "needs_enrollment": true
}
```

### GET `/face-status`

Cek status enrollment wajah user login.

Response `200`:

```json
{
  "is_enrolled": true,
  "message": "Face is enrolled",
  "data": {
    "enrollment_id": 1,
    "photo_url": "/storage/assets/faces/xyz.jpg",
    "created_at": "2026-04-14T10:00:00.000000Z",
    "updated_at": "2026-04-14T10:00:00.000000Z"
  }
}
```

## 6. Permission

### POST `/api-permissions`

Membuat pengajuan izin.

Payload `multipart/form-data`:

- `date` (required)
- `reason` (required)
- `image` (optional)

Response `201`:

```json
{
  "message": "Permission created successfully"
}
```

Catatan:

- Resource route `api-permissions` memang terdaftar sebagai `apiResource`, tetapi yang saat ini terimplementasi di controller API hanya proses `store`.

## 7. Notes

### GET `/api-notes`

List note milik user login.

Response `200`:

```json
{
  "notes": []
}
```

### POST `/api-notes`

Membuat note baru.

Payload:

- `title` (required)
- `note` (required)

Response `201`:

```json
{
  "message": "Note created successfully"
}
```

Catatan:

- Resource route `api-notes` terdaftar, tetapi method yang saat ini terimplementasi hanya `index` dan `store`.

## 8. Shifts

### GET `/api-shifts`

List semua shift.

Response `200`:

```json
{
  "status": "success",
  "data": []
}
```

### POST `/api-shifts`

Buat shift baru.

Payload:

- `name` (required)
- `time_in` (required)
- `time_out` (required)

Response `201`:

```json
{
  "status": "success",
  "data": {}
}
```

### GET `/api-shifts/{id}`

Detail shift berdasarkan ID.

### PUT/PATCH `/api-shifts/{id}`

Update shift.

Payload:

- `name` (optional)
- `time_in` (optional)
- `time_out` (optional)

Catatan:

- `DELETE /api-shifts/{id}` belum diimplementasikan di controller API.

## 9. Overtime

### GET `/api-overtimes`

List lembur milik user login.

Response `200`:

```json
{
  "message": "Success",
  "data": []
}
```

### POST `/api-overtimes`

Buat request lembur.

Payload:

- `date` (required, date)
- `duration` (required, integer, minimal 1)
- `description` (optional, string)

Response `201`:

```json
{
  "message": "Overtime request created",
  "data": {}
}
```

### GET `/api-overtimes/{id}`

Detail lembur berdasarkan ID.

## 10. Catatan Implementasi

- Endpoint `POST /check-qr` tidak ditemukan di `routes/api.php`, jadi tidak lagi dicantumkan sebagai endpoint aktif.
- Endpoint reimbursement API juga tidak ditemukan di route API aktif.
- Dokumentasi ini hanya mencantumkan fitur API yang benar-benar tersambung di codebase saat ini.
