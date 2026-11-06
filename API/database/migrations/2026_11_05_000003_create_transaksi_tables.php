<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 20)->unique();
            $table->date('tanggal');
            $table->string('pelanggan', 150);
            $table->string('telepon', 30);
            $table->string('kendaraan', 150);
            $table->string('plat', 20);
            $table->string('advisor', 150)->nullable();
            $table->unsignedInteger('odometer')->nullable();
            $table->string('jenis_servis', 150);
            $table->unsignedBigInteger('diskon')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->string('status', 30);
            $table->string('metode_pembayaran', 20);
            $table->timestamps();
            $table->index('tanggal');
            $table->index('status');
        });

        Schema::create('transaksi_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->cascadeOnDelete();
            $table->string('jenis', 20);
            $table->foreignId('masterpart_id')->nullable()->constrained('masterpart')->nullOnDelete();
            $table->foreignId('masterjasa_id')->nullable()->constrained('masterjasa')->nullOnDelete();
            $table->string('nama', 150);
            $table->string('keterangan', 255)->nullable();
            $table->string('satuan', 30)->nullable();
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('harga');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_item');
        Schema::dropIfExists('transaksi');
    }
};
