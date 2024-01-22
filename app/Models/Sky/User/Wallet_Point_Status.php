<?php

namespace App\Models\Sky\User;

use MongoDB\Laravel\Eloquent\Model;

class Wallet_Point_Status extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_user';
    protected $table = 'wallet_point_status';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
    protected $hidden = ['_id'];
}
