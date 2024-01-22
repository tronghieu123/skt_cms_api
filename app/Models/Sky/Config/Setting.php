<?php

namespace App\Models\Sky\Config;

use League\Flysystem\Config;
use MongoDB\Laravel\Eloquent\Model;
use App\Http\Token;
use Illuminate\Support\Facades\Http;

class Setting extends Model
{
    public $timestamps = false;

    protected $connection = 'sky_cms';

    protected $table = 'sky_setting';

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];
}
