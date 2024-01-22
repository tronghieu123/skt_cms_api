<?php

namespace App\Models\Booking\Driver;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Partner extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_partner';
    protected $table = 'partner';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];
}
