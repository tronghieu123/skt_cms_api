<?php

namespace App\Models\Booking\Booking;

//use App\Http\Token;
//use Illuminate\Support\Facades\DB;
use App\Models\Sky\User\User;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\CustomCasts\jsonToArray;
//use Illuminate\Support\Facades\Http;
//use function League\Flysystem\map;

class Booking extends Model{
    protected $connection = 'sky_booking';
    protected $table = 'booking';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'expired_booking' => 'timestamp',
        'driver_approve_date' => 'timestamp',
        'date_cancel' => 'timestamp',
    ];

    function user_info(){
        return $this->hasOne(User::class, '_id',  'user_id');
    }
}
