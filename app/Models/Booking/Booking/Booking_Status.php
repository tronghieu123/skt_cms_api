<?php

namespace App\Models\Booking\Booking;

use MongoDB\Laravel\Eloquent\Model;

class Booking_Status extends Model{
    protected $connection = 'sky_booking';
    protected $table = 'booking_status';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
