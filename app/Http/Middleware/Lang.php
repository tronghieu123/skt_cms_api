<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class Lang
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lang_cur = !empty($request->header('lang')) ? $request->header('lang') : 'vi';
        App::setLocale($lang_cur);
        return $next($request);
    }
}
