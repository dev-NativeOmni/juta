# Panduan Deployment: Laravel IMS ke Fly.io & Supabase (PostgreSQL)

Dokumen ini memuat panduan lengkap konfigurasi dan arsitektur deployment aplikasi **IMS (Integrated Management System)** ke **Fly.io** dengan database **Supabase (PostgreSQL)**.

---

## 1. Arsitektur Infrastruktur

* **Web Runtime**: [FrankenPHP](https://frankenphp.dev/) (Caddy-based PHP 8.2 runtime di container Alpine Linux).
* **Platform Cloud**: [Fly.io](https://fly.io/) (Region: Singapore `sin`).
* **Database**: [Supabase](https://supabase.com/) PostgreSQL dengan Supavisor Connection Pooler.
* **Continuous Deployment**: GitHub Actions (`.github/workflows/fly-deploy.yml`) yang hanya berjalan pada commit branch `main` atau dipicu secara manual (*workflow_dispatch*).

---

## 2. Pengaturan Supabase (PostgreSQL)

### A. Dapatkan Informasi Koneksi
Buka dashboard project Supabase Anda:
1. Masuk ke menu **Project Settings** > **Database**.
2. Gulir ke bagian **Connection string**.
3. Pilih mode **URI** atau **Parameters**:
   * **Transaction Mode (Disarankan untuk Web / Port `6543`)**: Menghemat batas koneksi database saat instance Fly aktif.
   * **Session Mode / Direct (Port `5432`)**: Dibutuhkan jika menjalankan migrasi (`php artisan migrate`).

### B. Format Environment Variable Laravel
```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com  # ganti dengan host pooler Anda
DB_PORT=6543                                    # port 6543 (pooler) atau 5432 (direct)
DB_DATABASE=postgres
DB_USERNAME=postgres.[PROJECT_REF]
DB_PASSWORD=[PASSWORD_DATABASE_ANDA]
DB_SSLMODE=require
```
*Catatan: Pastikan `DB_SSLMODE=require` diatur karena Supabase mewajibkan koneksi terenkripsi SSL.*

---

## 3. Konfigurasi Rahasia di Fly.io (`fly secrets`)

Jangan pernah memasukkan kredensial database ke dalam file `fly.toml` atau git! Gunakan perintah `fly secrets set` dari terminal lokal:

```bash
# 1. Pastikan sudah login ke Flyctl
fly auth login

# 2. Inisialisasi secrets wajib untuk production
fly secrets set \
  APP_KEY="base64:..." \
  DB_CONNECTION="pgsql" \
  DB_HOST="aws-0-ap-southeast-1.pooler.supabase.com" \
  DB_PORT="6543" \
  DB_DATABASE="postgres" \
  DB_USERNAME="postgres.YOUR_PROJECT_REF" \
  DB_PASSWORD="YOUR_DATABASE_PASSWORD" \
  DB_SSLMODE="require" \
  APP_URL="https://ims-tahfizh.fly.dev"
```

---

## 4. Persistensi File Storage

Pada file `fly.toml`, volume `storage_vol` dipasang ke:
```toml
[mounts]
  source = "storage_vol"
  destination = "/var/www/html/storage/app/public"
```

Untuk membuat volume pertama kali di Fly.io:
```bash
fly volumes create storage_vol --region sin --size 3
```

> **Rekomendasi Skalabilitas Jangka Panjang**:
> Supabase menyediakan Storage berbasis S3. Untuk membuat aplikasi 100% *stateless* (bisa scale lebih dari 1 mesin tanpa masalah sinkronisasi disk), pasang package `league/flysystem-aws-s3-v3` di Laravel dan arahkan disk upload ke Supabase Storage.

---

## 5. Menjalankan Deployment

### Cara 1: Otomatis via GitHub Actions (CI/CD)
1. Buat secret di repository GitHub (**Settings** > **Secrets and variables** > **Actions**):
   * `FLY_API_TOKEN`: Token dari akun Fly.io Anda (dapatkan via `fly tokens create deploy`).
2. Deployment otomatis terpicu saat melakukan push/merge ke branch **`main`**.
3. Workflow juga dapat dijalankan secara manual dari tab **Actions** di GitHub via tombol **Run workflow**.

### Cara 2: Manual dari Komputer Lokal
```bash
fly deploy --remote-only
```
Perintah `--remote-only` akan mem-build Docker image langsung di builder Fly.io tanpa membebani laptop/komputer Anda.

---

## 6. Operasional & Monitoring

* **Melihat Log Real-time**:
  ```bash
  fly logs
  ```
* **Mengecek Status Mesin & Health Check**:
  ```bash
  fly status
  ```
* **Menjalankan Artisan Command di Container**:
  ```bash
  fly ssh console -C "php artisan migrate:status"
  ```
* **Mencegah Cold Start (Optional)**:
  Secara default di `fly.toml`, `min_machines_running = 0` (mesin tidur saat tidak ada request untuk menghemat resource). Jika ingin aplikasi selalu merespons instan tanpa *delay*:
  Ubah nilai di `fly.toml`:
  ```toml
  min_machines_running = 1
  auto_stop_machines = "off"
  ```
