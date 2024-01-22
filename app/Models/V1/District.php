<?php

namespace App\Models\V1;

use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;

class District extends Model
{
    public $timestamps = false;

    protected $connection = 'sky';

    protected $table = 'location_district';

    public function getRouteKeyName()
    {
        return 'code';
    }

    public function scopeTitle(Builder $query, $title): Builder
    {
        return $query->where('title', 'LIKE', '%' . $title . '%');
    }
    public function province()
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }
    public function ward()
    {
        return $this->hasMany(Ward::class, 'district_code', 'code');
    }
}
