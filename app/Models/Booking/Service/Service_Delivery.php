<?php

namespace App\Models\Booking\Service;

use MongoDB\Laravel\Eloquent\Model;

class Service_Delivery extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'service_delivery';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
