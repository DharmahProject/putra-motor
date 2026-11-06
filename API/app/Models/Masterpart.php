<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Masterpart extends Model
{
    protected $table = 'masterpart';

    protected $fillable = [
        'nama',
        'stok',
        'harga',
    ];

    protected $casts = [
        'stok' => 'integer',
        'harga' => 'integer',
    ];
}
