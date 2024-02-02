<?php

namespace App\Models\Booking\Service;

use MongoDB\Laravel\Eloquent\Model;

class Vehicle extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'vehicle';
//    protected $with = ['info','vehicle_type','partner','approve'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
