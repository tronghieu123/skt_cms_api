<?php

namespace App\Models\V1;

use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;

class Ward extends Model
{
    public $timestamps = false;

    protected $connection = 'sky';

    protected $table = 'location_ward';
    // protected $casts = [
    //     'created_at'  => 'datetime',
    //     'updated_at'  => 'datetime',
    // ];

    public function scopeTitle(Builder $query, $title): Builder
    {
        return $query->where('title', 'LIKE', '%' . $title . '%');
    }
    public function district()
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}
