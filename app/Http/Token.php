<?php

namespace App\Http;

use MongoDB\Laravel\Eloquent\Model;

use DateTimeImmutable;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Parser;
use Illuminate\Support\Facades\File;

class Token
{
    function getToken($target, $user_id){
        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $algorithm    = new Sha256();
        $signingKey   = InMemory::plainText(random_bytes(32));
        $host  = request()->root();
        $now   = new DateTimeImmutable();
        $key   = File::get(base_path('app/Http/oauth-token.key'));

        $token = $tokenBuilder
            ->issuedBy($host)
            ->permittedFor($target)
            ->identifiedBy(\Str::random(40))
            ->issuedAt($now)
            ->expiresAt($now->modify('+5 minute'))
            ->relatedTo($key)
            ->withClaim('uid', $user_id)
            ->getToken($algorithm, $signingKey);
        return $token->toString();
    }
}
