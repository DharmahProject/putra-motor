<?php

namespace Database\Seeders;

use App\Models\Masterjasa;
use App\Models\Masterpart;
use App\Models\Transaksi;
use Illuminate\Database\Seeder;

class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        if (Transaksi::query()->exists()) {
            return;
        }

        $part = Masterpart::query()->pluck('id', 'nama');
        $jasa = Masterjasa::query()->pluck('id', 'nama');
        $now = now();

        $rows = [
            [
                'nomor' => 'TRX-2026-0118',
                'tanggal' => '2026-11-05',
                'pelanggan' => 'Budi Santoso',
                'telepon' => '0812 3456 7890',
                'kendaraan' => 'Toyota Avanza Veloz',
                'plat' => 'B 1234 SJK',
                'advisor' => 'Andi Pratama',
                'odometer' => 68520,
                'jenis_servis' => 'Servis Berkala + Bubut',
                'diskon' => 0,
                'total' => 885000,
                'status' => 'Selesai',
                'status_pembayaran' => 'Lunas',
                'metode_pembayaran' => 'Tunai',
                'items' => [
                    ['jenis' => 'jasa', 'masterjasa_id' => $jasa['Servis Berkala'] ?? null, 'nama' => 'Servis Berkala', 'qty' => 1, 'harga' => 350000],
                    ['jenis' => 'lainnya', 'nama' => 'Bubut', 'keterangan' => 'Bubut piringan rem', 'qty' => 1, 'harga' => 535000],
                ],
            ],
            [
                'nomor' => 'TRX-2026-0117',
                'tanggal' => '2026-11-05',
                'pelanggan' => 'Rina Wulandari',
                'telepon' => '0813 7744 9210',
                'kendaraan' => 'Honda Brio',
                'plat' => 'B 1765 KZP',
                'advisor' => 'Rizky Hidayat',
                'odometer' => 42180,
                'jenis_servis' => 'Ganti Oli + Filter Oli',
                'diskon' => 0,
                'total' => 420000,
                'status' => 'Dalam Pengerjaan',
                'status_pembayaran' => 'Belum Bayar',
                'metode_pembayaran' => 'Transfer',
                'items' => [
                    ['jenis' => 'jasa', 'masterjasa_id' => $jasa['Jasa Ganti Oli'] ?? null, 'nama' => 'Jasa Ganti Oli', 'qty' => 1, 'harga' => 120000],
                    ['jenis' => 'sparepart', 'masterpart_id' => $part['Oli Mesin 5W-30'] ?? null, 'nama' => 'Oli Mesin 5W-30', 'satuan' => 'Pcs', 'qty' => 2, 'harga' => 95000],
                    ['jenis' => 'sparepart', 'masterpart_id' => $part['Filter Oli Avanza'] ?? null, 'nama' => 'Filter Oli Avanza', 'satuan' => 'Pcs', 'qty' => 1, 'harga' => 110000],
                ],
            ],
            [
                'nomor' => 'TRX-2026-0116',
                'tanggal' => '2026-11-04',
                'pelanggan' => 'Andi Pratama',
                'telepon' => '0857 2012 8811',
                'kendaraan' => 'Suzuki XL7',
                'plat' => 'B 2890 TQA',
                'advisor' => 'Doni Saputra',
                'odometer' => 40125,
                'jenis_servis' => 'Servis Berkala (40.000 Km)',
                'diskon' => 0,
                'total' => 1280000,
                'status' => 'Selesai',
                'status_pembayaran' => 'Lunas',
                'metode_pembayaran' => 'QRIS',
                'items' => [
                    ['jenis' => 'jasa', 'masterjasa_id' => $jasa['Servis Berkala'] ?? null, 'nama' => 'Servis Berkala (40.000 Km)', 'qty' => 1, 'harga' => 1280000],
                ],
            ],
            [
                'nomor' => 'TRX-2026-0115',
                'tanggal' => '2026-11-04',
                'pelanggan' => 'Dewi Lestari',
                'telepon' => '0819 3355 6612',
                'kendaraan' => 'Mitsubishi Xpander',
                'plat' => 'B 1654 NBP',
                'advisor' => 'Andi Pratama',
                'odometer' => 51340,
                'jenis_servis' => 'Perbaikan Rem (Ganti Kampas)',
                'diskon' => 0,
                'total' => 980000,
                'status' => 'Dalam Pengerjaan',
                'status_pembayaran' => 'Belum Bayar',
                'metode_pembayaran' => 'Tunai',
                'items' => [
                    ['jenis' => 'sparepart', 'masterpart_id' => $part['Kampas Rem Depan'] ?? null, 'nama' => 'Kampas Rem Depan', 'satuan' => 'Pcs', 'qty' => 2, 'harga' => 275000],
                    ['jenis' => 'jasa', 'masterjasa_id' => $jasa['Pemeriksaan dan Servis Rem'] ?? null, 'nama' => 'Pemeriksaan dan Servis Rem', 'qty' => 1, 'harga' => 225000],
                    ['jenis' => 'lainnya', 'nama' => 'Bubut dan cairan rem', 'keterangan' => 'Bubut piringan dan isi cairan rem', 'qty' => 1, 'harga' => 205000],
                ],
            ],
            [
                'nomor' => 'TRX-2026-0114',
                'tanggal' => '2026-11-03',
                'pelanggan' => 'Agus Setiawan',
                'telepon' => '0878 9221 1180',
                'kendaraan' => 'Toyota Rush',
                'plat' => 'B 1408 FNL',
                'advisor' => 'Rizky Hidayat',
                'odometer' => 76900,
                'jenis_servis' => 'Ganti Aki + Cek Kelistrikan',
                'diskon' => 0,
                'total' => 1050000,
                'status' => 'Selesai',
                'status_pembayaran' => 'Lunas',
                'metode_pembayaran' => 'Transfer',
                'items' => [
                    ['jenis' => 'sparepart', 'masterpart_id' => $part['Aki Mobil 45 Ah'] ?? null, 'nama' => 'Aki Mobil 45 Ah', 'satuan' => 'Pcs', 'qty' => 1, 'harga' => 850000],
                    ['jenis' => 'jasa', 'masterjasa_id' => $jasa['Servis Kelistrikan Ringan'] ?? null, 'nama' => 'Servis Kelistrikan Ringan', 'qty' => 1, 'harga' => 200000],
                ],
            ],
            [
                'nomor' => 'TRX-2026-0113',
                'tanggal' => '2026-11-03',
                'pelanggan' => 'Siti Rahmawati',
                'telepon' => '0812 9001 4433',
                'kendaraan' => 'Daihatsu Terios',
                'plat' => 'B 1973 PLY',
                'advisor' => 'Doni Saputra',
                'odometer' => 35620,
                'jenis_servis' => 'Servis Ringan + Tune Up',
                'diskon' => 0,
                'total' => 650000,
                'status' => 'Dalam Pengerjaan',
                'status_pembayaran' => 'Belum Bayar',
                'metode_pembayaran' => 'QRIS',
                'items' => [
                    ['jenis' => 'jasa', 'nama' => 'Servis Ringan', 'qty' => 1, 'harga' => 370000],
                    ['jenis' => 'jasa', 'masterjasa_id' => $jasa['Tune Up Mesin'] ?? null, 'nama' => 'Tune Up Mesin', 'qty' => 1, 'harga' => 280000],
                ],
            ],
            [
                'nomor' => 'TRX-2026-0112',
                'tanggal' => '2026-11-02',
                'pelanggan' => 'Hadi Susanto',
                'telepon' => '0821 5500 7821',
                'kendaraan' => 'Honda HR-V',
                'plat' => 'B 2301 MKB',
                'advisor' => 'Andi Pratama',
                'odometer' => 28410,
                'jenis_servis' => 'Diagnosa Mesin (Scanning)',
                'diskon' => 0,
                'total' => 350000,
                'status' => 'Dalam Pengerjaan',
                'status_pembayaran' => 'Belum Bayar',
                'metode_pembayaran' => 'Tunai',
                'items' => [
                    ['jenis' => 'jasa', 'masterjasa_id' => $jasa['Diagnosa Mesin (Scanning)'] ?? null, 'nama' => 'Diagnosa Mesin (Scanning)', 'qty' => 1, 'harga' => 350000],
                ],
            ],
        ];

        foreach ($rows as $row) {
            $items = $row['items'];
            unset($row['items']);
            $transaksi = Transaksi::create([...$row, 'created_at' => $now, 'updated_at' => $now]);
            foreach ($items as $index => $item) {
                $transaksi->items()->create([
                    'jenis' => $item['jenis'],
                    'masterpart_id' => $item['masterpart_id'] ?? null,
                    'masterjasa_id' => $item['masterjasa_id'] ?? null,
                    'nama' => $item['nama'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'satuan' => $item['satuan'] ?? ($item['jenis'] === 'sparepart' ? 'Pcs' : null),
                    'qty' => $item['qty'],
                    'harga' => $item['harga'],
                    'urutan' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
