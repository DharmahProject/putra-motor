<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'address',
        'bank_1_name',
        'bank_1_account_name',
        'bank_1_account',
        'bank_2_name',
        'bank_2_account_name',
        'bank_2_account',
        'logo_path',
        'logo_data',
        'logo_mime',
    ];

    protected $hidden = [
        'logo_data',
    ];
}
