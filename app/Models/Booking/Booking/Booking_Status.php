<?php

namespace App\Models\Booking\Booking;

//use App\Http\Token;
//use Illuminate\Support\Facades\DB;
//use League\Flysystem\Config;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\CustomCasts\jsonToArray;
//use Illuminate\Support\Facades\Http;
//use function League\Flysystem\map;

class Booking_Status extends Model{
    protected $connection = 'sky_booking';
    protected $table = 'booking_status';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
