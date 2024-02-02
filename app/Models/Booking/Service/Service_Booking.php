<?php

namespace App\Models\Booking\Service;

use MongoDB\Laravel\Eloquent\Model;

class Service_Booking extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'service_booking';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
