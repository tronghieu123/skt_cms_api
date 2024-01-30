<?php
namespace App\Models\CustomCasts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class jsonToArray implements CastsAttributes
{
    public function get($model, $key, $value, $attributes){
        if(!empty($value) && is_string($value)){
            return json_decode($value,true);
        }elseif(is_array($value)){
            return $value;
        }else{
            return [];
        }
    }

    public function set($model, $key, $value, $attributes){
        if(!empty($value) && is_string($value)){
            return json_decode($value,true);
        }elseif(is_array($value)){
            return $value;
        }else{
            return [];
        }
    }
}
