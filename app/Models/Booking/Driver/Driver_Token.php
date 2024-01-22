<?php

namespace App\Models\Booking\Driver;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\CustomCasts\jsonToArray;

class Driver_Token extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'driver_token';
//    protected $casts = [];
}
