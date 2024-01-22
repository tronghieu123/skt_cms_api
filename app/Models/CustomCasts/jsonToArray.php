<?php
namespace App\Models\CustomCasts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class jsonToArray implements CastsAttributes
{
    public function get($model, $key, $value, $attributes){
        return json_decode($value,true);
    }

    public function set($model, $key, $value, $attributes){
        return json_decode($value,true);
    }
}
