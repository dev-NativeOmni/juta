# 🌐 PANDUAN TEKNIS MIGRASI DOMAIN: `ims.smaia7.sch.id` ➡️ `tad.smaia7.sch.id`
### TAD Management System (Tahfizh, Adab, Disiplin) — SMA Islam Al Azhar 7 Solo Baru

Panduan ini berisi langkah operasional langkah demi langkah (*step-by-step*) untuk memigrasikan sistem ke domain baru **`tad.smaia7.sch.id`** tanpa *downtime* (Zero Downtime) dan tetap menjaga link lama tetap berfungsi (*backward-compatible*).

---

## 📌 RINGKASAN PARAMETER SISTEM
* **Server**: Container 100 (`ims-server`) di Proxmox VE node `smaia7`
* **IP Server (A Record)**: `157.20.253.33`
* **DNS Provider**: Cloudflare (`smaia7.sch.id`)
* **Path Aplikasi**: `/www/wwwroot/ims.smaia7.sch.id`
* **Domain Lama**: `ims.smaia7.sch.id`
* **Domain Baru**: `tad.smaia7.sch.id`

---

## 🚀 LANGKAH 1: Tambahkan DNS Record di Cloudflare (Wajib Pertama)
1. Buka dashboard Cloudflare untuk domain `smaia7.sch.id`.
2. Masuk ke menu **DNS** > **Records** > klik **Add record**.
3. Isi kolom berikut:
   * **Type**: `A`
   * **Name**: `tad` *(akan menjadi `tad.smaia7.sch.id`)*
   * **IPv4 address**: `157.20.253.33`
   * **Proxy status**: **DNS only** *(ikon abu-abu, sama persis dengan konfigurasi `ims` agar webroot Let's Encrypt bisa langsung divalidasi oleh server)*
   * **TTL**: `Auto`
4. Klik **Save**.
5. **Uji Propagasi**:
   Buka terminal di komputer Anda atau server dan jalankan:
   ```bash
   dig tad.smaia7.sch.id +short
   # Harus menghasilkan: 157.20.253.33
   ```

---

## 🔒 LANGKAH 2: Konfigurasi Nginx & Terbitkan SSL Baru

### Pilihan A: Menggunakan aaPanel (Direkomendasikan & Termudah)
1. Buka dashboard web **aaPanel** server.
2. Masuk ke menu **Website** > cari situs `ims.smaia7.sch.id`.
3. Klik nama situs > tab **Domain manager**.
4. Masukkan domain baru `tad.smaia7.sch.id` (port 80/443), lalu klik **Add**.
   *(Sekarang situs merespons kedua domain sekaligus)*.
5. Pindah ke tab **SSL** > pilih **Let's Encrypt**.
6. Centang kedua domain:
   - [x] `ims.smaia7.sch.id`
   - [x] `tad.smaia7.sch.id`
7. Klik **Apply** / **Renew**. SSL baru dengan multi-domain SAN akan terpasang otomatis.

---

### Pilihan B: Menggunakan Terminal CLI (Manual)
Jika mengonfigurasi langsung via terminal Container 100:

1. **Terbitkan Sertifikat Let's Encrypt**:
   ```bash
   certbot certonly --webroot -w /www/wwwroot/ims.smaia7.sch.id/public -d tad.smaia7.sch.id -d ims.smaia7.sch.id
   ```

2. **Perbarui Nginx Vhost**:
   Edit file konfigurasi vhost Nginx (misal `/www/server/panel/vhost/nginx/ims.smaia7.sch.id.conf`):
   ```nginx
   server {
       listen 80;
       listen [::]:80;
       server_name tad.smaia7.sch.id ims.smaia7.sch.id;
       return 301 https://$host$request_uri;
   }

   server {
       listen 443 ssl http2;
       listen [::]:443 ssl http2;
       server_name tad.smaia7.sch.id ims.smaia7.sch.id;

       root /www/wwwroot/ims.smaia7.sch.id/public;
       index index.php index.html;

       ssl_certificate /etc/letsencrypt/live/tad.smaia7.sch.id/fullchain.pem;
       ssl_certificate_key /etc/letsencrypt/live/tad.smaia7.sch.id/privkey.pem;

       # Buffer & upload limit
       client_max_body_size 64M;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/tmp/php-cgi-82.sock; # Sesuaikan socket aktif
           fastcgi_index index.php;
           include fastcgi.conf;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

3. **Uji Sintaks & Reload Nginx**:
   ```bash
   nginx -t && nginx -s reload
   ```

---

## ⚙️ LANGKAH 3: Pembaruan Konfigurasi Laravel (`.env`)

Di dalam server Container 100, masuk ke direktori aplikasi:
```bash
cd /www/wwwroot/ims.smaia7.sch.id
```

1. **Tarik Kode Terbaru dari Git**:
   ```bash
   git pull origin main
   ```

2. **Edit file `.env`**:
   ```bash
   nano .env
   ```
   Sesuaikan parameter berikut:
   ```dotenv
   APP_NAME="TAD Management System"
   APP_URL=https://tad.smaia7.sch.id

   # Izinkan sesi Sanctum API untuk kedua domain selama masa transisi
   SANCTUM_STATEFUL_DOMAINS=tad.smaia7.sch.id,ims.smaia7.sch.id

   # Opsional: Jika ingin login di satu domain otomatis aktif di domain lainnya:
   SESSION_DOMAIN=.smaia7.sch.id
   ```

3. **Bersihkan & Segarkan Cache Laravel**:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## 🧪 LANGKAH 4: Pengujian & Verifikasi

Lakukan pengecekan dari browser:
1. Akses **`https://tad.smaia7.sch.id/login`** ➡️ Pastikan halaman login TAD terbuka dengan gembok SSL hijau valid.
2. Login sebagai Super Admin atau Guru.
3. Buka menu **Input Spreadsheet**:
   - Pastikan header kolom menampilkan badge **HARI INI**.
   - Coba klik tombol **Lompat ke Hari Ini**.
   - Cek Console browser (F12) ➡️ Pastikan tidak ada error CORS atau 419 CSRF.
4. Akses **`https://ims.smaia7.sch.id`** ➡️ Pastikan domain lama juga tetap dapat dibuka secara normal.

---

## 🔀 LANGKAH 5: Pengalihan Permanen 301 (Dilakukan Setelah 1–2 Pekan)
Setelah seluruh guru dan wali murid terbiasa dengan alamat baru `tad.smaia7.sch.id`, domain lama dialihkan permanen (301) agar semua bookmark lama otomatis pindah ke alamat baru:

Tambahkan blok Nginx khusus untuk domain lama:
```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name ims.smaia7.sch.id;
    return 301 https://tad.smaia7.sch.id$request_uri;
}
```
Reload Nginx: `nginx -s reload`.

---

## 🆘 Rencana Rollback (Jika Ada Kendala)
Jika sertifikat SSL tertunda atau terjadi masalah teknis:
1. Buka `.env` dan kembalikan `APP_URL=https://ims.smaia7.sch.id`.
2. Jalankan `php artisan optimize:clear`.
3. Sistem akan kembali berjalan 100% menggunakan domain lama `ims.smaia7.sch.id`.
