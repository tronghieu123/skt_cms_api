<?php

namespace App\Models\System\Food;

use App\Models\Booking\Service\Service_Food;
use App\Models\Booking\Service\Vehicle;
use App\Models\CustomCasts\jsonToArray;
use MongoDB\Laravel\Eloquent\Model;

class Service_Price extends Model{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'service_price';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'arr_pricing' => jsonToArray::class,
        'arr_fee_delivery' => jsonToArray::class,
        'arr_info_delivery' => jsonToArray::class,
    ];

    function doListServicePrice(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = Service_Price::where('type', 'food')
            ->when(request('sub') == 'manage_trash' ?? null, function ($query){
                $query->where('is_show', 0);
            })
            ->when(request('sub') == 'manage' ?? null, function ($query){
                $query->where('is_show', 1);
            })
            ->where('lang', Config('lang_cur'))
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        return response_pagination($data);
    }

    // ----------- Cài đặt giá dịch vụ -----------
    // Load data select theo loại
    function loadDataSelectParentId(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = [];
        if(!empty(request('parent_id'))){
            $data['child_id'] = Service_Food::where('is_show', 1)
                ->where('lang', Config('lang_cur'))
                ->where('vehicle_id', request('parent_id'))
                ->orderBy('title', 'asc')
                ->pluck('title', '_id')
                ->toArray();
        }
        return response_custom('',0, $data);
    }
    // Chi tiết cài đặt giá dịch vụ
    function detailServicePrice(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $parent_id = Vehicle::where('is_show', 1)
            ->where('lang', Config('lang_cur'))
            ->where('type', 'food')
            ->orderBy('title', 'asc')
            ->pluck('title','_id')
            ->toArray();
        if(!empty(request('item'))){
            $data = [];
            $detail = Service_Price::where('_id', request('item'))->where('type', 'food')->first();
            if($detail){
                $detail = $detail->toArray();
                $data['item_detail'] = $detail;
                $data['parent_id'] = $parent_id;
                if(!empty($detail['parent_id'])){
                    $data['child_id'] = Service_Food::where('is_show', 1)
                        ->where('lang', Config('lang_cur'))
                        ->where('vehicle_id', $detail['parent_id'])
                        ->orderBy('title', 'asc')
                        ->pluck('title','_id')
                        ->toArray();
                }
                return response_custom('',0, $data);
            }else{
                return response_custom('Không tìm thấy dữ liệu!',1);
            }
        }else{
            $data['parent_id'] = $parent_id;
            return response_custom('',0, $data);
        }
    }
}
