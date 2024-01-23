<?php

namespace App\Models\Sky\User;

use MongoDB\Laravel\Eloquent\Model;

class DeviceToken extends Model{
    public $timestamps = false;
    protected $connection = 'sky_user';
    protected $table = 'device_token';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];
}
