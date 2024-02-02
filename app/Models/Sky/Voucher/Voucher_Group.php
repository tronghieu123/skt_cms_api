<?php

namespace App\Models\Sky\Voucher;

use MongoDB\Laravel\Eloquent\Model;

class Voucher_Group extends Model{
    protected $connection = 'sky_voucher';
    protected $table = 'voucher_group';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];
}
