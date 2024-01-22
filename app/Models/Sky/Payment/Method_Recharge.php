<?php

namespace App\Models\Sky\Payment;

use MongoDB\Laravel\Eloquent\Model;

class Method_Recharge extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_payment';
    protected $table = 'method_recharge';

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
}
