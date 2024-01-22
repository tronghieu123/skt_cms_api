<?php

namespace App\Models\Booking\Driver;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\CustomCasts\jsonToArray;

class Driver_Contract_Status extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'driver_contract_status';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
    protected $hidden = ['_id'];
}
