<?php

namespace App\Models\Sky\User;

use MongoDB\Laravel\Eloquent\Model;

class User_Rank extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_user';
    protected $table = 'user_rank';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];
}
