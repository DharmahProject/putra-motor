<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    protected $table = 'transaksi';

    protected $fillable = [
        'nomor',
        'tanggal',
        'pelanggan',
        'telepon',
        'kendaraan',
        'plat',
        'advisor',
        'odometer',
        'jenis_servis',
        'diskon',
        'total',
        'status',
        'status_pembayaran',
        'metode_pembayaran',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'odometer' => 'integer',
        'diskon' => 'integer',
        'total' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TransaksiItem::class)->orderBy('urutan')->orderBy('id');
    }
}
