<?php

namespace App\Models\Booking\Vehicle;

//use App\Http\Token;
//use Illuminate\Support\Facades\DB;
//use League\Flysystem\Config;
//use League\Flysystem\Config;
use MongoDB\Laravel\Eloquent\Model;
//use App\Models\CustomCasts\jsonToArray;
use App\Models\Booking\Driver\Vehicle;
//use App\Models\Sky\User\User;
//use function Termwind\ValueObjects\p;

//use Illuminate\Support\Facades\Http;
//use function League\Flysystem\map;

class Service_Booking extends Model{
    protected $connection = 'sky_booking';
    protected $table = 'service_booking';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
