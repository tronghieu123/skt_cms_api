<?php

namespace App\Models\Sky\Voucher;

use MongoDB\Laravel\Eloquent\Model;

class Voucher_Trademark extends Model{
    protected $connection = 'sky_voucher';
    protected $table = 'voucher_trademark';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];
}
