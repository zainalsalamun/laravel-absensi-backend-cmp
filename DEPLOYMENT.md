# Panduan Deployment Aplikasi Absensi Laravel

## Pilihan 1: Deployment dengan Docker (Disarankan)
### Persyaratan
- VPS dengan Docker dan Docker Compose terinstall
- Minimal 2 CPU dan 2GB RAM
- Domain yang sudah di-point ke IP VPS

### Langkah-langkah Deployment

1. **Persiapan Environment**
   ```bash
   # Copy file environment contoh
   cp .env.docker.example .env
   
   # Edit file .env sesuai konfigurasi kamu
   nano .env
   ```

2. **Atur Konfigurasi Nginx dan SSL**
   Untuk SSL Letsencrypt, kamu bisa menambahkan service certbot ke docker-compose.yml atau menggunakan certbot standalone:
   ```bash
   # Install certbot
   sudo apt install certbot
   
   # Dapatkan SSL certificate
   sudo certbot certonly --standalone -d your-domain.com
   
   # Copy certificate ke direktori docker/nginx/ssl/
   sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem docker/nginx/ssl/
   sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem docker/nginx/ssl/
   ```

3. **Jalankan Aplikasi**
   ```bash
   # Build dan jalankan container
   docker-compose up -d --build
   
   # Generate application key
   docker-compose exec app php artisan key:generate
   
   # Jalankan migrasi database
   docker-compose exec app php artisan migrate --seed
   
   # Simpan konfigurasi cache
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

4. **Verifikasi Deployment**
   Buka browser dan akses `https://your-domain.com`

### Manajemen Service
```bash
# Lihat log service
docker-compose logs -f

# Restart semua service
docker-compose restart

# Hentikan service
docker-compose down

# Lihat status container
docker-compose ps
```

---

## Pilihan 2: Deployment Tanpa Docker (Standard Laravel Deployment)
### Persyaratan
- VPS dengan PHP 8.2+ terinstall
- Web Server (Nginx/Apache)
- Database PostgreSQL 15+
- Redis (opsional untuk queue dan cache)

### Langkah-langkah Deployment

1. **Persiapan Server**
   ```bash
   # Update package server
   sudo apt update && sudo apt upgrade -y
   
   # Install dependencies PHP
   sudo apt install php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-gd php8.2-redis php8.2-zip php8.2-curl
   
   # Install Web Server Nginx
   sudo apt install nginx
   
   # Install Database PostgreSQL
   sudo apt install postgresql postgresql-contrib
   
   # Install Redis
   sudo apt install redis-server
   ```

2. **Konfigurasi Database**
   ```bash
   # Masuk ke PostgreSQL
   sudo -u postgres psql
   
   # Buat database dan user
   CREATE DATABASE absensi;
   CREATE USER absensi_user WITH PASSWORD 'password_aman_anda';
   GRANT ALL PRIVILEGES ON DATABASE absensi TO absensi_user;
   \q
   ```

3. **Deploy Kode Aplikasi**
   ```bash
   # Clone repository di direktori web
   cd /var/www
   git clone https://github.com/zainalsalamun/laravel-absensi-backend-cmp.git absensi
   cd absensi
   
   # Install dependencies Composer
   composer install --optimize-autoloader --no-dev
   
   # Copy file environment
   cp .env.example .env
   
   # Edit file .env
   nano .env
   ```

4. **Konfigurasi Nginx**
   Buat file konfigurasi Nginx di `/etc/nginx/sites-available/absensi.conf`:
   ```nginx
   server {
       listen 80;
       server_name your-domain.com;
       root /var/www/absensi/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-XSS-Protection "1; mode=block";
       add_header X-Content-Type-Options "nosniff";

       index index.php;

       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location = /favicon.ico { access_log off; log_not_found off; }
       location = /robots.txt  { access_log off; log_not_found off; }

       error_page 404 /index.php;

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

   Aktifkan konfigurasi:
   ```bash
   sudo ln -s /etc/nginx/sites-available/absensi.conf /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl restart nginx
   ```

5. **Jalankan Aplikasi**
   ```bash
   # Generate application key
   php artisan key:generate
   
   # Jalankan migrasi dan seeder
   php artisan migrate --seed
   
   # Set permission direktori
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   
   # Install dan jalankan queue worker
   sudo systemctl enable --now redis-server
   php artisan queue:work --daemon
   ```

---

## Best Practices Deployment
1. **Gunakan SSL Letsencrypt** untuk mengamankan traffic
2. **Batasi akses firewall** hanya port 80, 443, dan 22
3. **Gunakan environment production** dengan APP_DEBUG=false
4. **Setup cron job untuk scheduler Laravel**
   ```bash
   # Edit crontab
   crontab -e
   
   # Tambahkan baris ini
   * * * * * cd /var/www/absensi && php artisan schedule:run >> /dev/null 2>&1
   ```
5. **Backup database secara rutin**
6. **Monitor log aplikasi** secara berkala
