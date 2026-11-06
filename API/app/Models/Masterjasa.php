<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Masterjasa extends Model
{
    protected $table = 'masterjasa';

    protected $fillable = [
        'nama',
        'harga',
    ];

    protected $casts = [
        'harga' => 'integer',
    ];
}
