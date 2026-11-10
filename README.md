# Putra Motor

## Deploy ke Vercel

Repository ini berjalan sebagai satu container PHP (FrankenPHP) supaya UI PHP di
root dan Laravel API di `API/` tetap memakai URL dan logic yang sama.

### Pengaturan project Vercel

1. Import repository dengan **Root Directory** di root repository (kosong atau `.`),
   bukan folder `API`.
2. Framework Preset akan dibaca dari `vercel.json` sebagai **Container**.
3. Hapus override lama untuk Build Command dan Output Directory. Project ini tidak
   memakai folder `dist`.
4. Tambahkan Environment Variables berikut untuk Production dan Preview:

```env
APP_NAME=Putra Motor
APP_ENV=production
APP_KEY=base64:GENERATE_DENGAN_PHP_ARTISAN_KEY_GENERATE_SHOW
APP_DEBUG=false
APP_URL=https://domain-vercel-anda.vercel.app

DB_CONNECTION=mysql
DB_HOST=HOST_MYSQL_PUBLIK
DB_PORT=4000
DB_DATABASE=putra_motor
DB_USERNAME=USERNAME_DATABASE
DB_PASSWORD=PASSWORD_DATABASE
# Wajib untuk MySQL publik yang memakai TLS (TiDB Cloud, Aiven, dll.)
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

POS_SESSION_DRIVER=database
SESSION_LIFETIME=120
PROFILE_LOGO_DRIVER=database
POS_APP_URL=https://domain-vercel-anda.vercel.app
```

Tambahkan juga `FONNTE_TOKEN` dan `FONNTE_API_URL` jika fitur reset password
WhatsApp digunakan. Jangan commit file `API/.env` atau nilai password ke Git.

`DB_HOST=localhost` hanya berlaku jika aplikasi berjalan di hosting yang sama
dengan MySQL. Deployment Vercel harus memakai MySQL yang bisa diakses publik
(misalnya TiDB Cloud atau Aiven). Shared hosting seperti cPanel Rumahweb biasanya
memblokir port 3306 dari luar sehingga Vercel akan timeout.

### Database production

Jalankan migration satu kali terhadap database production dari mesin yang memang
diizinkan mengakses MySQL Hostinger:

```bash
php API/artisan migrate --force
```

Migration menambahkan tabel session yang dipakai bersama oleh semua instance
Vercel dan kolom penyimpanan logo. Tabel session juga dibuat otomatis pada login
pertama bila user database mempunyai izin `CREATE TABLE`.

### Verifikasi

Setelah deployment selesai, buka `/health`. Respons yang benar:

```json
{"status":"ok"}
```

Lalu uji captcha, login, satu endpoint dashboard, dan upload logo. Filesystem
container Vercel tidak persisten; karena itu session dan logo production disimpan
di MySQL.
