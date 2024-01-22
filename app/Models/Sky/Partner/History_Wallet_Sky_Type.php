<?php

namespace App\Models\Sky\Partner;
use MongoDB\Laravel\Eloquent\Model;

class History_Wallet_Sky_Type extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_partner';
    protected $table = 'history_wallet_sky_type';

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
