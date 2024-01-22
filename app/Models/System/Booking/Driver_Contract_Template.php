<?php

namespace App\Models\System\Booking;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\CustomCasts\jsonToArray; 

use App\Models\Booking\Driver\Vehicle;

class Driver_Contract_Template extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'driver_contract_template';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function contract_type()
    {
        switch (request()->type) {
            case 'vehicle':
                $data = Vehicle::pluck('title','_id');
                break;            
            default:
                $data = [];
                break;
        }
        return response_custom('', 0, $data);
    }
}
