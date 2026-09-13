# Panduan Deployment & Checklist Produksi (HafizPlus 2.0)

Panduan ini disusun untuk memastikan ke-13 layer arsitektur sistem berjalan dengan optimal di server produksi (public).

---

## 1. Hosting & Cloud Compute (Layer 5 & 6)
*   **Kebutuhan Minimum Server:** 
    *   OS: Ubuntu Linux 22.04 / 24.04 LTS
    *   Spesifikasi: Minimal 2 vCPU, 4GB RAM (untuk kelancaran PHP, MySQL, & Queue Runner)
*   **Web Server (Nginx):**
    *   Pastikan PHP-FPM 8.2+ terinstal.
    *   Contoh blok konfigurasi Nginx (`/etc/nginx/sites-available/hafizplus`):
        ```nginx
        server {
            listen 80;
            server_name hafizplus.id www.hafizplus.id;
            return 301 https://$host$request_uri; # Redirect ke HTTPS
        }

        server {
            listen 443 ssl http2;
            server_name hafizplus.id www.hafizplus.id;
            root /var/www/hafizplus-2.0/public;

            index index.php;
            charset utf-8;

            location / {
                try_files $uri $uri/ /index.php?$query_string;
            }

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

---

## 2. Keamanan & Environment (.env) (Layer 4, 8 & 9)
Setelah memindahkan codebase ke server, buat file `.env` produksi dengan konfigurasi pengerasan keamanan berikut:
```ini
APP_NAME="HafizPlus 2.0"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hafizplus.id # Gunakan HTTPS asli

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hafizplus_prod
DB_USERNAME=user_produksi
DB_PASSWORD=password_kuat_anda

# Session & Security
SESSION_DRIVER=database # Mempermudah horizontal scaling dibanding 'file'
SESSION_SECURE_COOKIE=true # Hanya kirim cookies via HTTPS

# API Rate Limits
HAFIZPLUS_API_RATE_LIMIT_PER_MINUTE=60
HAFIZPLUS_API_LOGIN_RATE_LIMIT_PER_MINUTE=5

# Two-Factor Authentication (wajib aktif di produksi)
HAFIZPLUS_2FA_ENFORCE=true
HAFIZPLUS_2FA_REQUIRED_ROLES=super_admin
```
Jangan lupa jalankan perintah berikut di folder root aplikasi:
```bash
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 3. CDN & HTTPS (Layer 10)
Untuk menghemat bandwidth VPS dan mempercepat pemuatan aset Frontend, sangat disarankan menggunakan **Cloudflare (Gratis)**:
1. Hubungkan DNS domain Anda ke nameserver Cloudflare.
2. Atur enkripsi SSL/TLS pada opsi **Full** atau **Full (Strict)**.
3. Di tab *Caching*, Cloudflare akan otomatis menyimpan file statis (CSS, JS yang dibuat oleh Vite) secara global di server edge mereka.

---

## 4. Antrean Pekerjaan (Queue Runner via Supervisor) (Layer 2)
Beberapa proses backend (seperti generating notifikasi) dijalankan secara background agar response aplikasi tetap instan. Konfigurasikan **Supervisor** di server untuk menjaga queue worker tetap berjalan:
1. Buat file `/etc/supervisor/conf.d/hafizplus-worker.conf`:
    ```ini
    [program:hafizplus-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /var/www/hafizplus-2.0/artisan queue:work --sleep=3 --tries=3 --max-time=3600
    autostart=true
    autorestart=true
    stopasgroup=true
    killasgroup=true
    user=www-data
    numprocs=2
    redirect_stderr=true
    stdout_logfile=/var/www/hafizplus-2.0/storage/logs/worker.log
    stopwaitsecs=3600
    ```
2. Jalankan perintah:
    ```bash
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start all
    ```

---

## 5. Scheduler & Backup Otomatis (Layer 13)
Aplikasi telah ditambahkan skema backup harian pada jam 03:00 pagi. Agar scheduler Laravel ini aktif, Anda **wajib** mendaftarkan cron job di server:
1. Jalankan perintah `crontab -e` dengan user `www-data` atau `root`.
2. Tambahkan baris berikut:
    ```cron
    * * * * * cd /var/www/hafizplus-2.0 && php artisan schedule:run >> /dev/null 2>&1
    ```
3. *Rekomendasi Disaster Recovery:* Konfigurasikan driver backup di `config/filesystems.php` ke cloud storage eksternal seperti AWS S3 atau Google Cloud Storage. Ubah file konfigurasi backup agar tidak hanya menyimpan lokal, melainkan diunggah ke cloud.

---

## 6. Error Tracking Real-time (Layer 12)
Untuk menangkap bug/error yang dialami user di server produksi:
1. Instal package **Sentry**:
    ```bash
    composer require sentry/sentry-laravel
    ```
2. Jalankan command inisialisasi:
    ```bash
    php artisan sentry:publish --dsn=INPUT_DSN_DARI_SENTRY_ANDA
    ```
3. Alternatif gratis: Konfigurasikan webhook Slack pada `.env` dengan variabel `LOG_SLACK_WEBHOOK_URL` agar log level `critical` langsung dikirim ke channel Slack Anda.
