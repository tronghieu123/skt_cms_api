<?php

//use App\Http\Controllers\V1\DistrictController;
//use App\Http\Controllers\V1\ProvinceController;
//use App\Http\Controllers\V1\WardController;
//use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$namespace = 'App\\Http\\Controllers';
$tmp = explode('/api/', request()->url());
$api = explode('/', $tmp[1] ?? '')[0] ?? '';

if ($api=='login' || $api=='role_group') {
    if($api=='role_group') {
        Route::post('/api/role_group', $namespace. '\\AuthController@role_group');
    } else {
        Route::post('/api/login', $namespace. '\\AuthController@login');
    }
    return true;
} else {
    // Route group with Sanctum middleware
    Route::group(['namespace' => $namespace, 'controller' => $namespace . '\\Controller', 'middleware' => ['auth:sanctum', 'lang'], 'prefix' => 'api'], function () use($namespace, $api) {
        if($api=='logout') {
            Route::post('/logout', $namespace. '\\AuthController@logout');
        } else {
            Route::match(['get', 'post', 'put', 'delete'], "{$api}/{id?}", "Handle");
        }
    });
}
