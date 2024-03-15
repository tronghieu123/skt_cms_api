<?php

namespace App\Models\Booking\Booking;

use MongoDB\Laravel\Eloquent\Model;

class Delivery_Type extends Model{
    protected $connection = 'sky_booking';
    protected $table = 'delivery_type';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
