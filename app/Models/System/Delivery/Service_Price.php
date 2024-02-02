<?php

namespace App\Models\System\Delivery;

use App\Models\Booking\Service\Service_Delivery;
use App\Models\Booking\Service\Vehicle;
use App\Models\CustomCasts\jsonToArray;
use App\Models\Sky\Config\Location_Province;
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
        $tmp = Location_Province::where('is_show', 1)->orderBy('title', 'asc')->pluck('title', 'code')->toArray();
        $province = ['all' => 'Toàn quốc'];
        $province += $tmp;
        $data = Service_Price::where('type', 'delivery')
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
        foreach ($data['data'] as $k => $item){
            if(!empty($item['province'])){
                $prov_tmp = (array_intersect_key($province, array_flip($item['province'])));
                if($prov_tmp){
                    $data['data'][$k]['province'] = implode(', ', $prov_tmp);
                }
            }
        }
        return response_pagination($data);
    }

    // ----------- Cài đặt giá dịch vụ -----------
    function loadDataSelectParentId(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = [];
        if(!empty(request('parent_id'))){
            $data['child_id'] = Service_Delivery::where('is_show', 1)
                ->where('lang', Config('lang_cur'))
                ->where('vehicle_id', request('parent_id'))
                ->orderBy('title', 'asc')
                ->pluck('title','_id')
                ->toArray();
        }
        return response_custom('',0, $data);
    }
    // Chi tiết cài đặt giá dịch vụ
    function detailServicePrice(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = [];
        $parent_id = Vehicle::where('is_show', 1)
            ->where('lang', Config('lang_cur'))
            ->where('type', 'delivery')
            ->orderBy('title', 'asc')
            ->pluck('title','_id')
            ->toArray();

        $tmp = Location_Province::where('is_show', 1)->orderBy('title', 'asc')->pluck('title', 'code')->toArray();
        $province = ['all' => 'Toàn quốc'];
        $province += $tmp;
        if(!empty(request('item'))){
            $detail = Service_Price::where('_id', request('item'))->where('type', 'delivery')->first();
            if($detail){
                $detail = $detail->toArray();
                $data['item_detail'] = $detail;
                $data['parent_id'] = $parent_id;
                if(!empty($detail['parent_id'])){
                    $data['child_id'] = Service_Delivery::where('is_show', 1)
                        ->where('lang', Config('lang_cur'))
                        ->where('vehicle_id', $detail['parent_id'])
                        ->orderBy('title', 'asc')
                        ->pluck('title','_id')
                        ->toArray();
                }
                $data['province'] = $province;
                return response_custom('',0, $data);
            }else{
                return response_custom('Không tìm thấy dữ liệu!',1);
            }
        }else{
            $data['parent_id'] = $parent_id;
            $data['province'] = $province;
            return response_custom('',0, $data);
        }
    }
}
