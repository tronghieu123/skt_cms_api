<?php

namespace App\Models\V1;

use MongoDB\Laravel\Eloquent\Model;

class Province extends Model
{
    public $timestamps = false;

    protected $connection = 'sky';

    protected $table = 'location_province';

    public function getRouteKeyName()
    {
        return 'code';
    }

    public function district()
    {
        return $this->hasMany(District::class, 'province_code', 'code');
    }
}
