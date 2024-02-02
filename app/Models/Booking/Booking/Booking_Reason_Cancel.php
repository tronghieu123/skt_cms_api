<?php

namespace App\Models\Booking\Booking;

use MongoDB\Laravel\Eloquent\Model;

class Booking_Reason_Cancel extends Model{
    protected $connection = 'sky_booking';
    protected $table = 'booking_reason_cancel';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
