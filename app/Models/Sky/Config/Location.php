<?php

namespace App\Models\Sky\Config;
//use Illuminate\Database\Eloquent\Model;

use App\Http\Controllers\Controller;
use League\Flysystem\Config;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use function Pest\Mixins\export;

class Location extends Model
{
    public $timestamps = false;

    protected $connection = 'sky';

    function getLocation()
    {
        $db = DB::connection('sky');
        $country = !empty(request('country')) ? request('country') : 'vi';
        if(request()->has('district')){ // Lấy danh sách phường xã con
            $data = $db->collection('location_ward')
                ->where('is_show', 1)
                ->where('country_code', $country)
                ->where('district_code', request('district'))
                ->orderBy('title', 'asc')
                ->pluck('title', 'code');
            $type = 'ward';
            $refresh = [];
            if(request('district') == 0){
                $refresh = ['ward'];
            }
        }elseif (request()->has('province')){ // Lấy danh sách quận huyện con
            $data = $db->collection('location_district')
                ->where('is_show', 1)
                ->where('country_code', $country)
                ->where('province_code', request('province'))
                ->orderBy('title', 'asc')
                ->pluck('title', 'code');
            $type = 'district';
            $refresh = ['ward'];
            if(request('province') == 0){
                $refresh = ['district', 'ward'];
            }
        }elseif(!request()->has('ward')){ // Lấy danh sách tỉnh thành con
            $data = $db->collection('location_province')
                ->where('is_show', 1)
                ->where('country_code', $country)
                ->orderBy('title', 'asc')
                ->pluck('title', 'code');
            $type = 'province';
            $refresh = ['ward'];
        }else{
            $type = '';
            $refresh = [];
            $data = [];
        }
        return response()->json([
            'error' => 0,
            'code' => 200,
            'message' => '',
            'type' => $type,
            'refresh' => $refresh,
            'data' => $data
        ],200);
    }
}
