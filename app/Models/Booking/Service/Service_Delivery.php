<?php

namespace App\Models\Booking\Service;

//use App\Http\Token;
//use Illuminate\Support\Facades\DB;
//use League\Flysystem\Config;
use MongoDB\Laravel\Eloquent\Model;
//use App\Models\CustomCasts\jsonToArray;
//use Illuminate\Support\Facades\Http;
//use function League\Flysystem\map;

class Service_Delivery extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'service_delivery';
//    protected $with = ['info','vehicle_type','partner','approve'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
