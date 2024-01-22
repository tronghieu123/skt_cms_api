<?php

namespace App\Models\Booking\Service;

use App\Http\Token;
use Illuminate\Support\Facades\DB;
use League\Flysystem\Config;
use MongoDB\Laravel\Eloquent\Model;
//use App\Models\CustomCasts\jsonToArray;
//use Illuminate\Support\Facades\Http;
//use function League\Flysystem\map;

class Service_Price extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'service_price';
//    protected $with = ['info','vehicle_type','partner','approve'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

//    public function info() {
//        return $this->hasOne(Driver_Info::class, 'driver_id',  '_id');
//    }
//
//    public function vehicle_type() {
//        return $this->hasOne(Vehicle::class, '_id',  'vehicle')->select(['name_action']);
//    }
//
//    public function partner() {
//        return $this->hasOne(Partner::class, '_id',  'partner_id');
//    }
//
//    public function approve() {
//        return $this->hasMany(Driver_Register_Step::class, 'driver_id',  '_id')
//            ->orderBy('step','asc');
//    }
//
//    public function listDriver() {
//        if(request()->method() != 'POST'){
//            return response_custom('Sai phương thức!', 1, [],405);
//        }
//        if(request()->has('ext_param')) {
//            $data = Driver::filter()
//                ->where(['_id' => request('ext_param')])
//                ->first();
//            return response_custom('Thành công',0,$data);
//        } else {
//            $data = Driver::filter()
//                ->orderBy('created_at', 'desc')
//                ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
//                ->toArray();
//            return response_pagination($data);
//        }
//    }

//    public static function scopeFilter($query)
//    {
//        $query->when(request('type') == 'wait_approve' ?? null, function ($query){
//                $query->where('is_show', 0); // Đợi duyệt
//            })
//                ->when(request('type') == 'approved' ?? null, function ($query){
//                $query->where('is_show', 1); // Đã duyệt
//            })
//            ->when(request('type') == 'banned' ?? null, function ($query){
//                $query->where('is_show', 2); // Đã khóa
//            })
//            ->when(request('type') == 'wait_approve_again' ?? null, function ($query){
//                $query->where('is_show', 3); // Chờ duyệt lại
//            });
//    }

    // ----------- Cài đặt giá dịch vụ -----------
    // Load data select theo loại
    function loadDataSelectType(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('type'))){
            $data = [];
            $data['parent_id'] = Vehicle::where('is_show', 1)
                ->where('lang', Config('lang_cur'))
                ->where('type', request('type'))
                ->orderBy('title', 'asc')
                ->pluck('title','_id')
                ->toArray();
            if(!empty(request('parent_id'))){
                switch (request('type')){
                    case 'booking':
                        $data['child_id'] = Service_Booking::where('is_show', 1)
                            ->where('lang', Config('lang_cur'))
                            ->where('vehicle_id', request('parent_id'))
                            ->orderBy('title', 'asc')
                            ->pluck('title','_id')
                            ->toArray();
                        break;
                    case 'delivery':
                        $data['child_id'] = Service_Delivery::where('is_show', 1)
                            ->where('lang', Config('lang_cur'))
                            ->where('vehicle_id', request('parent_id'))
                            ->orderBy('title', 'asc')
                            ->pluck('title','_id')
                            ->toArray();
                        break;
                    case 'food':
                        $data['child_id'] = Service_Food::where('is_show', 1)
                            ->where('lang', Config('lang_cur'))
                            ->where('vehicle_id', request('parent_id'))
                            ->orderBy('title', 'asc')
                            ->pluck('title','_id')
                            ->toArray();
                        break;
                }
            }
            return response_custom('',0, $data);
        }else{
            return response_custom('Không tìm thấy loại', 1);
        }
    }
    // Chi tiết cài đặt giá dịch vụ
    function detailServicePrice(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('item'))){
            $data = [];
            $detail = Service_Price::where('_id', request('item'))->first();
            if($detail){
                $detail = $detail->toArray();
                $detail['arr_pricing'] = !empty($detail['arr_pricing']) ? json_decode($detail['arr_pricing'], true) : [];
                $data['item_detail'] = $detail;
                if(!empty($detail['type'])){
                    $data['parent_id'] = Vehicle::where('is_show', 1)
                        ->where('lang', Config('lang_cur'))
                        ->where('type', $detail['type'])
                        ->orderBy('title', 'asc')
                        ->pluck('title','_id')
                        ->toArray();
                    if(!empty($detail['parent_id'])){
                        switch ($detail['type']){
                            case 'booking':
                                $data['child_id'] = Service_Booking::where('is_show', 1)
                                    ->where('lang', Config('lang_cur'))
                                    ->where('vehicle_id', $detail['parent_id'])
                                    ->orderBy('title', 'asc')
                                    ->pluck('title','_id')
                                    ->toArray();
                                break;
                            case 'delivery':
                                $data['child_id'] = Service_Delivery::where('is_show', 1)
                                    ->where('lang', Config('lang_cur'))
                                    ->where('vehicle_id', $detail['parent_id'])
                                    ->orderBy('title', 'asc')
                                    ->pluck('title','_id')
                                    ->toArray();
                                break;
                            case 'food':
                                $data['child_id'] = Service_Food::where('is_show', 1)
                                    ->where('lang', Config('lang_cur'))
                                    ->where('vehicle_id', $detail['parent_id'])
                                    ->orderBy('title', 'asc')
                                    ->pluck('title','_id')
                                    ->toArray();
                                break;
                        }
                    }
                }
                return response_custom('',0,$data);
            }else{
                return response_custom('Không tìm thấy dữ liệu!',1);
            }
        }else{
            return response_custom('Không tìm thấy item!',1);
        }
    }
    // ----------- Cài đặt giá dịch vụ -----------
}
