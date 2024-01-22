<?php

namespace App\Models\Booking\Booking;

use MongoDB\Laravel\Eloquent\Model;

class Booking_Method_Payment extends Model{
    protected $connection = 'sky_booking';
    protected $table = 'booking_method_payment';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
