<?php

namespace App\Models\Report\Booking;

use MongoDB\Laravel\Eloquent\Model;

class Overview extends Model{
    protected $connection = 'sky_booking';
    protected $table = 'booking';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

}
