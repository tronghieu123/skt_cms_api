<?php

use App\Http\Controllers\V1\DistrictController;
use App\Http\Controllers\V1\ProvinceController;
use App\Http\Controllers\V1\WardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$namespace = 'App\\Http\\Controllers';

Route::post('/login', 'App\\Http\\Controllers\\AuthController@login')->name('login');

Route::middleware('auth:sanctum')->get('api/user', function (Request $request) {
    return $request->user();
});

Route::prefix('api/v1')->group(function () {
    // \DB::listen(function($query) {
    //     print_r(json_encode(($query->sql)));
    // });
    Route::apiResources([
        'province' => ProvinceController::class,
        'district' => DistrictController::class,
        'ward' => WardController::class,
    ]);

    
});

Route::group(
    ['namespace' => $namespace, 'controller' => $namespace . '\\Controller', 'middleware' => 'lang', 'prefix' => 'api'],
    function () {
        $tmp = explode('/api/', request()->url());
        $api = explode('/', $tmp[1] ?? '')[0] ?? '';
        //        $api_no_token = ['login'];
        Route::match(['get', 'post', 'put', 'delete'], "{$api}/{id?}", "Handle");
        //        if(in_array($api, $api_no_token)){
//            Route::post('api/login', '\\App\\Models\\User@login');
//        } else {
//            Route::group(['middleware' => 'auth'], function() use ($api) {
//                Route::match(['get', 'post', 'put', 'delete'], "api/{$api}/{id?}", "Handle");
//            });
//        }
    }
);
