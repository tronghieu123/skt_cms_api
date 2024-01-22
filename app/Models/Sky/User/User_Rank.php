<?php

namespace App\Models\Sky\User;

use League\Flysystem\Config;
use MongoDB\Laravel\Eloquent\Model;
use App\Http\Token;
use Illuminate\Support\Facades\Http;

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
