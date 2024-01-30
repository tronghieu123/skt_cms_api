<?php

namespace App\Http;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\File;

class Token
{
    function getToken($target, $user_id){
        $key = File::get(base_path('app/Http/oauth-token.key'));
        $encrypter = new Encrypter($key, "AES-256-CBC");
        $encrypted = $encrypter->encrypt($user_id);
        return $encrypted;
    }
}
