<?php

namespace App\Models\Cms\Gateway;

use App\Models\CustomCasts\jsonToArray;
use MongoDB\Laravel\Eloquent\Model;

class Gateway extends Model{
    public $timestamps = false;
    protected $connection = 'sky_cms';
    protected $table = 'gateway';
    protected $casts = [
        'created_at' => 'timestamp',
        'permission' => jsonToArray::class
    ];

}
