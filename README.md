# Attendance Management System (Absensi Online)

Backend Laravel untuk sistem absensi karyawan dengan dashboard web dan API mobile. Dokumentasi ini dirapikan berdasarkan fitur yang benar-benar sudah terhubung di codebase saat ini.

## Fitur Yang Sudah Tersedia

- Dashboard web untuk admin/HRD/supervisor dan user, termasuk ringkasan jumlah karyawan, jumlah shift, kehadiran hari ini, keterlambatan, absensi terbaru, dan izin terbaru.
- Login/logout API menggunakan Laravel Sanctum.
- Manajemen user di dashboard: tambah, edit, hapus, pencarian, pengaturan role, phone, position, department, dan assignment shift.
- Manajemen profil user via API:
  - ambil data user login (`/api/user`)
  - ambil detail user berdasarkan ID
  - update biodata user
  - upload foto profil
  - update FCM token
- Manajemen company/profile perusahaan:
  - data perusahaan via API
  - pengaturan nama, email, alamat, koordinat kantor, radius absensi, jam kerja, tipe absensi, dan tarif lembur via dashboard
- Absensi mobile:
  - check-in dengan koordinat GPS dan upload foto
  - check-out dengan koordinat GPS dan upload foto
  - cek status sudah check-in/check-out hari ini
  - lihat riwayat absensi pribadi dan filter tanggal
- Face recognition:
  - face enrollment
  - update enrollment
  - hapus enrollment
  - verifikasi wajah
  - cek status enrollment
  - verifikasi wajah otomatis saat check-in/check-out jika `attendance_type = face`
- Manajemen izin:
  - user membuat pengajuan izin via API
  - admin membuka detail, menyetujui/menolak di dashboard
  - email dikirim saat izin disetujui
- Notes pribadi via API:
  - list catatan
  - tambah catatan
- Manajemen shift:
  - CRUD di dashboard
  - list/create/show/update via API
- Manajemen lembur:
  - user membuat request lembur via API
  - user melihat daftar dan detail lembur via API
  - dashboard untuk list, filter, create, edit status, dan hapus lembur
- Export data absensi ke CSV dari dashboard.

## Fitur Yang Sudah Ada Di Codebase Tapi Belum Terhubung Penuh

- Modul reimbursement sudah punya model, migration, controller, dan view, tetapi route aktifnya belum ditemukan di `routes/web.php` maupun `routes/api.php`.
- Modul QR absensi sudah punya model, migration, controller, dan view generator PDF, tetapi route aktif untuk akses fitur ini juga belum ditemukan.
- Dokumentasi lama sempat menyebut geofencing ketat, QR attendance API, dan reimbursement API. Pada code yang aktif saat ini, bagian tersebut belum terlihat terhubung sebagai endpoint/route yang siap dipakai.

## Teknologi

### Backend

- Laravel 11
- Laravel Sanctum
- MySQL/PostgreSQL
- Blade + Stisla

### Integrasi

- Firebase Cloud Messaging untuk token notifikasi
- Resend untuk email approval izin
- Face recognition internal berbasis ekstraksi fitur gambar

## Langkah Instalasi

1. Clone repository
   ```bash
   git clone https://github.com/zainalsalamun/laravel-absensi-backend-cmp.git
   cd laravel-absensi-backend-cmp
   ```
2. Install dependency
   ```bash
   composer install
   npm install
   ```
3. Siapkan environment
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Jalankan migrasi dan seeder
   ```bash
   php artisan migrate --seed
   ```
5. Jalankan server
   ```bash
   php artisan serve
   ```

## Dokumentasi Tambahan

- `API_DOCUMENTATION.md` untuk endpoint API yang aktif
- `FACE_RECOGNITION_README.md` untuk detail face recognition
- `DEPLOYMENT.md` untuk deployment
- `QUICK_REFERENCE.md` untuk referensi singkat

---

Dokumentasi diperbarui berdasarkan codebase pada 23 April 2026.
