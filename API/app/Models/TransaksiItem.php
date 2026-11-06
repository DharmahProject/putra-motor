<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiItem extends Model
{
    protected $table = 'transaksi_item';

    protected $fillable = [
        'transaksi_id',
        'jenis',
        'masterpart_id',
        'masterjasa_id',
        'nama',
        'keterangan',
        'satuan',
        'qty',
        'harga',
        'urutan',
    ];

    protected $casts = [
        'masterpart_id' => 'integer',
        'masterjasa_id' => 'integer',
        'qty' => 'integer',
        'harga' => 'integer',
        'urutan' => 'integer',
    ];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }
}
