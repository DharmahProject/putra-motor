# Query MySQL Bengkel Mas Anto

Database: `bengkel_pos`

Jalankan berurutan di MySQL. Query yang sama dipakai Laravel lewat migration dan seeder.

## Buat database

```sql
CREATE DATABASE IF NOT EXISTS bengkel_pos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bengkel_pos;
```

## Tabel users dan hak akses

Untuk instalasi baru yang belum memiliki tabel `users`:

```sql
CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  karyawan_id BIGINT UNSIGNED NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  email_verified_at TIMESTAMP NULL DEFAULT NULL,
  password VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt (password_hash)',
  role VARCHAR(50) NOT NULL DEFAULT 'Kasir',
  permissions JSON NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email),
  UNIQUE KEY users_karyawan_id_unique (karyawan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Jika tabel `users` bawaan Laravel sudah ada, cukup tambahkan kolom berikut:

```sql
ALTER TABLE users
  ADD COLUMN karyawan_id BIGINT UNSIGNED NULL AFTER id,
  ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'Kasir' AFTER password,
  ADD COLUMN permissions JSON NULL AFTER role,
  ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER permissions,
  ADD UNIQUE KEY users_karyawan_id_unique (karyawan_id);
```

Contoh user awal dengan password `admin123` yang disimpan sebagai hash bcrypt. Hash baru bisa dibuat dengan `php -r "echo password_hash('PASSWORD', PASSWORD_DEFAULT);"`. Akun lama yang masih MD5 otomatis diubah ke bcrypt saat login berhasil.

```sql
INSERT INTO users (name, email, password, role, permissions, active, created_at, updated_at) VALUES
  ('Administrator', 'admin@putramotor.id', '$2y$10$KuvOzoP8fMkw3cXQk5pqVOGeRe4CPMnKB4cjC/PHblxt2BC8uYKQq', 'Administrator',
   JSON_ARRAY('Dashboard', 'Transaksi', 'Sparepart', 'Jasa', 'Pengaturan'), 1, NOW(), NOW());
```

## Tabel karyawan (Service Advisor)

```sql
CREATE TABLE karyawan (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id VARCHAR(30) NOT NULL,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(150) NOT NULL,
  shift VARCHAR(20) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY karyawan_employee_id_unique (employee_id),
  UNIQUE KEY karyawan_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Hubungkan user ke karyawan setelah kedua tabel tersedia:

```sql
ALTER TABLE users
  ADD CONSTRAINT users_karyawan_id_foreign
  FOREIGN KEY (karyawan_id) REFERENCES karyawan (id)
  ON DELETE SET NULL;
```

`employee_id` tidak dikirim dari form. API membuatnya otomatis dengan format `ID-PM-001`, `ID-PM-002`, dan seterusnya.

```sql
INSERT INTO karyawan (employee_id, name, phone, email, shift, active, created_at, updated_at) VALUES
  ('ID-PM-001', 'Andi Pratama', '081234567890', 'andi@putramotor.id', 'Pagi', 1, NOW(), NOW()),
  ('ID-PM-002', 'Siti Rahma', '081398765432', 'siti@putramotor.id', 'Siang', 1, NOW(), NOW());
```

## Tabel profil bengkel

```sql
CREATE TABLE profiles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  address TEXT NULL,
  bank_1_name VARCHAR(100) NULL,
  bank_1_account_name VARCHAR(150) NULL,
  bank_1_account VARCHAR(50) NULL,
  bank_2_name VARCHAR(100) NULL,
  bank_2_account_name VARCHAR(150) NULL,
  bank_2_account VARCHAR(50) NULL,
  logo_path VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Tabel masterpart

```sql
CREATE TABLE masterpart (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(150) NOT NULL,
  stok INT UNSIGNED NOT NULL DEFAULT 0,
  harga BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Tabel masterjasa

```sql
CREATE TABLE masterjasa (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(150) NOT NULL,
  harga BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Data awal masterpart

```sql
INSERT INTO masterpart (nama, stok, harga, created_at, updated_at) VALUES
  ('Oli Mesin 5W-30', 24, 95000, NOW(), NOW()),
  ('Filter Oli Avanza', 18, 85000, NOW(), NOW()),
  ('Kampas Rem Depan', 6, 275000, NOW(), NOW()),
  ('Aki Mobil 45 Ah', 8, 850000, NOW(), NOW()),
  ('Busi Iridium', 0, 110000, NOW(), NOW()),
  ('Filter Udara', 10, 125000, NOW(), NOW());
```

## Data awal masterjasa

```sql
INSERT INTO masterjasa (nama, harga, created_at, updated_at) VALUES
  ('Jasa Ganti Oli', 120000, NOW(), NOW()),
  ('Servis Berkala', 350000, NOW(), NOW()),
  ('Tune Up Mesin', 280000, NOW(), NOW()),
  ('Pemeriksaan dan Servis Rem', 225000, NOW(), NOW()),
  ('Diagnosa Mesin (Scanning)', 350000, NOW(), NOW()),
  ('Servis Kelistrikan Ringan', 200000, NOW(), NOW());
```

## Tabel transaksi

```sql
CREATE TABLE transaksi (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nomor VARCHAR(20) NOT NULL,
  tanggal DATE NOT NULL,
  pelanggan VARCHAR(150) NOT NULL,
  telepon VARCHAR(30) NOT NULL,
  kendaraan VARCHAR(150) NOT NULL,
  plat VARCHAR(20) NOT NULL,
  advisor VARCHAR(150) NULL,
  odometer INT UNSIGNED NULL,
  jenis_servis VARCHAR(150) NOT NULL,
  diskon BIGINT UNSIGNED NOT NULL DEFAULT 0,
  total BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL,
  status_pembayaran VARCHAR(30) NOT NULL DEFAULT 'Belum Bayar',
  metode_pembayaran VARCHAR(20) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY transaksi_nomor_unique (nomor),
  KEY transaksi_tanggal_index (tanggal),
  KEY transaksi_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Tabel transaksi_item

```sql
CREATE TABLE transaksi_item (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  transaksi_id BIGINT UNSIGNED NOT NULL,
  jenis VARCHAR(20) NOT NULL,
  masterpart_id BIGINT UNSIGNED NULL,
  masterjasa_id BIGINT UNSIGNED NULL,
  nama VARCHAR(150) NOT NULL,
  keterangan VARCHAR(255) NULL,
  satuan VARCHAR(30) NULL,
  qty INT UNSIGNED NOT NULL,
  harga BIGINT UNSIGNED NOT NULL,
  urutan INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY transaksi_item_transaksi_id_foreign (transaksi_id),
  KEY transaksi_item_masterpart_id_foreign (masterpart_id),
  KEY transaksi_item_masterjasa_id_foreign (masterjasa_id),
  CONSTRAINT transaksi_item_transaksi_id_foreign FOREIGN KEY (transaksi_id) REFERENCES transaksi (id) ON DELETE CASCADE,
  CONSTRAINT transaksi_item_masterpart_id_foreign FOREIGN KEY (masterpart_id) REFERENCES masterpart (id) ON DELETE SET NULL,
  CONSTRAINT transaksi_item_masterjasa_id_foreign FOREIGN KEY (masterjasa_id) REFERENCES masterjasa (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Data awal transaksi

`masterpart_id` dan `masterjasa_id` mengikuti urutan data awal di atas. Jalankan sekali pada tabel yang masih kosong.

```sql
INSERT INTO transaksi (nomor, tanggal, pelanggan, telepon, kendaraan, plat, advisor, odometer, jenis_servis, diskon, total, status, status_pembayaran, metode_pembayaran, created_at, updated_at) VALUES
  ('TRX-2026-0118', '2026-11-05', 'Budi Santoso', '0812 3456 7890', 'Toyota Avanza Veloz', 'B 1234 SJK', 'Andi Pratama', 68520, 'Servis Berkala + Bubut', 0, 885000, 'Selesai', 'Lunas', 'Tunai', NOW(), NOW()),
  ('TRX-2026-0117', '2026-11-05', 'Rina Wulandari', '0813 7744 9210', 'Honda Brio', 'B 1765 KZP', 'Rizky Hidayat', 42180, 'Ganti Oli + Filter Oli', 0, 420000, 'Dalam Pengerjaan', 'Belum Bayar', 'Transfer', NOW(), NOW()),
  ('TRX-2026-0116', '2026-11-04', 'Andi Pratama', '0857 2012 8811', 'Suzuki XL7', 'B 2890 TQA', 'Doni Saputra', 40125, 'Servis Berkala (40.000 Km)', 0, 1280000, 'Selesai', 'Lunas', 'QRIS', NOW(), NOW()),
  ('TRX-2026-0115', '2026-11-04', 'Dewi Lestari', '0819 3355 6612', 'Mitsubishi Xpander', 'B 1654 NBP', 'Andi Pratama', 51340, 'Perbaikan Rem (Ganti Kampas)', 0, 980000, 'Dalam Pengerjaan', 'Belum Bayar', 'Tunai', NOW(), NOW()),
  ('TRX-2026-0114', '2026-11-03', 'Agus Setiawan', '0878 9221 1180', 'Toyota Rush', 'B 1408 FNL', 'Rizky Hidayat', 76900, 'Ganti Aki + Cek Kelistrikan', 0, 1050000, 'Selesai', 'Lunas', 'Transfer', NOW(), NOW()),
  ('TRX-2026-0113', '2026-11-03', 'Siti Rahmawati', '0812 9001 4433', 'Daihatsu Terios', 'B 1973 PLY', 'Doni Saputra', 35620, 'Servis Ringan + Tune Up', 0, 650000, 'Dalam Pengerjaan', 'Belum Bayar', 'QRIS', NOW(), NOW()),
  ('TRX-2026-0112', '2026-11-02', 'Hadi Susanto', '0821 5500 7821', 'Honda HR-V', 'B 2301 MKB', 'Andi Pratama', 28410, 'Diagnosa Mesin (Scanning)', 0, 350000, 'Dalam Pengerjaan', 'Belum Bayar', 'Tunai', NOW(), NOW());

INSERT INTO transaksi_item (transaksi_id, jenis, masterpart_id, masterjasa_id, nama, keterangan, satuan, qty, harga, urutan, created_at, updated_at) VALUES
  (1, 'jasa', NULL, 2, 'Servis Berkala', NULL, NULL, 1, 350000, 1, NOW(), NOW()),
  (1, 'lainnya', NULL, NULL, 'Bubut', 'Bubut piringan rem', NULL, 1, 535000, 2, NOW(), NOW()),
  (2, 'jasa', NULL, 1, 'Jasa Ganti Oli', NULL, NULL, 1, 120000, 1, NOW(), NOW()),
  (2, 'sparepart', 1, NULL, 'Oli Mesin 5W-30', NULL, 'Pcs', 2, 95000, 2, NOW(), NOW()),
  (2, 'sparepart', 2, NULL, 'Filter Oli Avanza', NULL, 'Pcs', 1, 110000, 3, NOW(), NOW()),
  (3, 'jasa', NULL, 2, 'Servis Berkala (40.000 Km)', NULL, NULL, 1, 1280000, 1, NOW(), NOW()),
  (4, 'sparepart', 3, NULL, 'Kampas Rem Depan', NULL, 'Pcs', 2, 275000, 1, NOW(), NOW()),
  (4, 'jasa', NULL, 4, 'Pemeriksaan dan Servis Rem', NULL, NULL, 1, 225000, 2, NOW(), NOW()),
  (4, 'lainnya', NULL, NULL, 'Bubut dan cairan rem', 'Bubut piringan dan isi cairan rem', NULL, 1, 205000, 3, NOW(), NOW()),
  (5, 'sparepart', 4, NULL, 'Aki Mobil 45 Ah', NULL, 'Pcs', 1, 850000, 1, NOW(), NOW()),
  (5, 'jasa', NULL, 6, 'Servis Kelistrikan Ringan', NULL, NULL, 1, 200000, 2, NOW(), NOW()),
  (6, 'jasa', NULL, NULL, 'Servis Ringan', NULL, NULL, 1, 370000, 1, NOW(), NOW()),
  (6, 'jasa', NULL, 3, 'Tune Up Mesin', NULL, NULL, 1, 280000, 2, NOW(), NOW()),
  (7, 'jasa', NULL, 5, 'Diagnosa Mesin (Scanning)', NULL, NULL, 1, 350000, 1, NOW(), NOW());
```
