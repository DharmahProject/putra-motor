<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('status_pembayaran', 30)->default('Belum Bayar')->after('status');
        });

        DB::table('transaksi')->where('status', 'Lunas')->update([
            'status' => 'Selesai',
            'status_pembayaran' => 'Lunas',
        ]);

        DB::table('transaksi')->whereIn('status', ['Belum Bayar', 'Draft', 'Dalam Proses'])->update([
            'status' => 'Dalam Pengerjaan',
            'status_pembayaran' => 'Belum Bayar',
        ]);
    }

    public function down(): void
    {
        DB::table('transaksi')->where('status_pembayaran', 'Lunas')->update(['status' => 'Lunas']);
        DB::table('transaksi')->where('status', 'Dalam Pengerjaan')->update(['status' => 'Belum Bayar']);

        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn('status_pembayaran');
        });
    }
};
