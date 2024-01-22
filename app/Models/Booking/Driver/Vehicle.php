<?php

namespace App\Models\Booking\Driver;
use MongoDB\Laravel\Eloquent\Model;

class Vehicle extends Model{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'vehicle';
}
