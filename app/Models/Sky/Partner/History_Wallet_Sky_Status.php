<?php

namespace App\Models\Sky\Partner;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\CustomCasts\jsonToArray;

class History_Wallet_Sky_Status extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_partner';
    protected $table = 'history_wallet_sky_status';

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
