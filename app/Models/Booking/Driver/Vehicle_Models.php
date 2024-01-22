<?php

namespace App\Models\Booking\Driver;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\CustomCasts\jsonToArray;

class Vehicle_Models extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'vehicle_models';
//    protected $casts = [];
}
