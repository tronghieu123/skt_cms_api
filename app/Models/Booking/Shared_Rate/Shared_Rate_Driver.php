<?php

namespace App\Models\Booking\Shared_Rate;

use App\Models\Sky\User\User;

use App\Models\Booking\Driver\Driver;

use App\Models\Booking\Booking\Booking;
use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;

class Shared_Rate_Driver extends Model{
    protected $connection = 'sky_booking';
    protected $table = 'shared_rate';
    protected $with = ['bookingInfo'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
    protected $type_name = [
        'customer_rate' => 'Khách hàng đánh giá',
        'driver_rate' => 'Tài xế đánh giá',
        'store_rate' => 'Cửa hàng đánh giá'
    ];

    protected static function booted() {
        static::addGlobalScope('driver', function (Builder $builder) {
            $builder->where('type', 'driver_rate')->with(['driverInfo' => function ($query) {
                $query->with(['partner' => function ($partner){
                    $partner->select('phone', 'full_name', 'email');
                }])->with(['info' => function ($info){
                    $info->select('avatar', '_id', 'driver_id');
                }])->without(['vehicle_type', 'approve'])
                ->select('partner', 'partner_id');
            }]);
        });
    }

    public function bookingInfo() {
        return $this->hasOne(Booking::class, '_id',  'type_id')->select(['item_code','driver_id']);
    }
    function driverInfo(){
        return $this->hasOne(Driver::class, '_id',  'assessor');
    }
    function userInfo(){
        return $this->hasOne(User::class, '_id',  'assessor');
    }

    public static function scopeFilter($query)
    {
        $filter = !empty(request('arr_filter')) ? json_decode(request('arr_filter'), true) : [];
        $query->when(!empty($filter['keyword']) ?? null, function ($query) use($filter){
            $query->whereHas('userInfo', function($q) use($filter) {
                $keyword = explode_custom($filter['keyword'],' ');
                if($keyword){
                    foreach ($keyword as $item){
                        $q->orWhere('full_name', 'LIKE', '%'.$item.'%');
                    }
                }
            });
            $query->orWhereHas('bookingInfo', function($q) use($filter) {
                $q->where('item_code', 'LIKE', '%'.$filter['keyword'].'%');
            });
        })
        ->when(!empty($filter['date_start']) ?? null, function ($query) use($filter){
            $date_start = convert_date_search($filter['date_start']);
            $query->whereDate("created_at", ">=", $date_start);
        })
        ->when(!empty($filter['date_end']) ?? null, function ($query) use($filter){
            $date_end = convert_date_search($filter['date_end']);
            $query->whereDate("created_at", ">=", $date_end);
        });
    }
    // --------- Danh sách đánh giá ---------
    function sharedRate(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = Shared_Rate_Driver::when(request('type') == 'pending' ?? null, function ($query){
                $query->where('is_approve', 0); // Đợi duyệt
            })
            ->when(request('type') == 'approved' ?? null, function ($query){
                $query->where('is_approve', 1); // Đã duyệt
            })
            ->when(request('type') == 'reject' ?? null, function ($query){
                $query->where('is_approve', 2); // từ chối
            })
            ->filter()
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        $data['other']['counter'] = $this->tabSharedRate();
        return response_pagination($data);
    }
    function tabSharedRate(){
        $data['all'] = Shared_Rate_Driver::filter()->count();
        $data['pending'] = Shared_Rate_Driver::filter()->where('is_approve', 0)->count();
        $data['approved'] = Shared_Rate_Driver::filter()->where('is_approve', 1)->count();
        $data['reject'] = Shared_Rate_Driver::filter()->where('is_approve', 2)->count();
        return $data;
    }
    // --------- Chi tiết đánh giá ---------
    function detailSharedRate(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('item'))){
            $data = Shared_Rate_Driver::where('_id', request('item'))->first();
            if($data){
                $data = $data->toArray();
                if(!empty($data['type'])){
                    $data['type_name'] = $this->type_name[$data['type']];
                    switch ($data['type']){
                        case 'customer_rate':
                            $data['user_info'] = User::where('_id', $data['assessor'])->first();
                            if($data['user_info']){
                                $data['user_info'] = $data['user_info']->toArray();
                                unset($data['user_info']['list_token']);
                            }
                            break;
                        case 'driver_rate':
                            $data['user_info'] = Driver::where('_id', $data['assessor'])->first();
                            if($data['user_info']){
                                $data['user_info'] = $data['user_info']->toArray();
                            }
                            break;
                        case 'store_rate':
//                            $data['user_info'] = Store::where('_id', $data['assessor'])->first();
//                            if($data['user_info']){
//                                $data['user_info'] = $data['user_info']->toArray();
//                            }
                            break;
                    }
                }
                return response_custom('',0,$data);
            }else{
                return response_custom('Không tìm thấy dữ liệu',1);
            }
        }else{
            return response_custom('Không tìm thấy item',1);
        }
    }

}
