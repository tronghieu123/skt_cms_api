<?php
namespace App\Models\CustomCasts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class explodeToArray implements CastsAttributes
{
    public function get($model, $key, $value, $attributes){
        return explode(',',$value);
    }

    public function set($model, $key, $value, $attributes){
        return explode(',',$value);
    }
}
