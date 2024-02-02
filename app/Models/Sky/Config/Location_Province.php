<?php

namespace App\Models\Sky\Config;

use MongoDB\Laravel\Eloquent\Model;
use function Pest\Mixins\export;

class Location_Province extends Model{
    public $timestamps = false;
    protected $connection = 'sky';
    protected $table = 'location_province';
}
