<?php

namespace Database\Seeders;

use App\Models\Masterjasa;
use App\Models\Masterpart;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        Masterpart::insert([
            ['nama' => 'Oli Mesin 5W-30', 'stok' => 24, 'harga' => 95000, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Filter Oli Avanza', 'stok' => 18, 'harga' => 85000, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Kampas Rem Depan', 'stok' => 6, 'harga' => 275000, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Aki Mobil 45 Ah', 'stok' => 8, 'harga' => 850000, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Busi Iridium', 'stok' => 0, 'harga' => 110000, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Filter Udara', 'stok' => 10, 'harga' => 125000, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Masterjasa::insert([
            ['nama' => 'Jasa Ganti Oli', 'harga' => 120000, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Servis Berkala', 'harga' => 350000, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Tune Up Mesin', 'harga' => 280000, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Pemeriksaan dan Servis Rem', 'harga' => 225000, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Diagnosa Mesin (Scanning)', 'harga' => 350000, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Servis Kelistrikan Ringan', 'harga' => 200000, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->call(TransaksiSeeder::class);
    }
}
