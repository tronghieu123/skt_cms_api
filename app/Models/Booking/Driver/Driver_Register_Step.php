<?php

namespace App\Models\Booking\Driver;
use MongoDB\Laravel\Eloquent\Model;

class Driver_Register_Step extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'driver_register_step';
    protected $casts = [
        'updated_at' => 'timestamp'
    ];
    protected $hidden = ['_id','driver_id','created_at'];
}
