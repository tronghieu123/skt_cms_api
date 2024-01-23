<?php

namespace App\Models\Booking\Shared_Rate;

use App\Http\Token;
use App\Models\Booking\Driver\Driver_Token;
use App\Models\Sky\User\User;
use App\Models\Sky\User\DeviceToken;
use App\Models\Sky\User\Wallet_Point_Log;

use App\Models\Booking\Driver\Driver;
use App\Models\Booking\Driver\Partner;

use App\Models\Booking\Booking\Booking;
use App\Models\Booking\Booking\Setting;

use App\Models\Sky\Partner\History_Wallet_Sky;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;

class Shared_Rate_Customer extends Model{
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
        static::addGlobalScope('customer', function (Builder $builder) {
            $builder->where('type', 'customer_rate')->with('userInfo');
        });
    }

    public function bookingInfo() {
        return $this->hasOne(Booking::class, '_id',  'type_id')->select(['item_code','driver_id']);
    }
    function driverInfo(){
        return $this->hasOne(Driver::class, '_id',  'assessor')->without('info');
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
        $data = Shared_Rate_Customer::when(request('type') == 'pending' ?? null, function ($query){
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
        $data['all'] = Shared_Rate_Customer::filter()->count();
        $data['pending'] = Shared_Rate_Customer::filter()->where('is_approve', 0)->count();
        $data['approved'] = Shared_Rate_Customer::filter()->where('is_approve', 1)->count();
        $data['reject'] = Shared_Rate_Customer::filter()->where('is_approve', 2)->count();
        return $data;
    }
    // --------- Chi tiết đánh giá ---------
    function detailSharedRate(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('item'))){
            $data = Shared_Rate_Customer::where('_id', request('item'))->first();
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

    // Duyệt đánh giá
    function approvalSharedRate(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('item'))){
            $rate = Shared_Rate_Customer::where('_id', request('item'))->first();
            if($rate){
                $setting = Setting::pluck('setting_value', 'setting_key');

                $rate = $rate->toArray();
                $rate['is_approve'] = $rate['is_approve'] ?? 0;
                if($rate['type'] == 'customer_rate'){
                    $user_id = $rate['assessor'] ?? ($rate['booking_info']['user_id'] ?? '');
                    $driver_id = $rate['booking_info']['driver_id'] ?? '';

                    $data = [];
                    $data['booking'] = $rate['booking_info'];
                    $wallet_driver = Driver::where('_id', $driver_id)->without('info','vehicle_type','approve')->first();
                    if($wallet_driver){
                        $data['driver'] = $wallet_driver->toArray();
                    }
                    $customer = User::where('_id', $user_id)->without('list_token')->first(['_id','wallet_point','wallet_point_total']);
                    if($customer){
                        $data['customer'] = $customer->toArray();
                    }

                    if(request()->has('approve')){
                        if(request('approve') == 0){ // Từ chối
                            if(!empty(request('reason'))){
                                switch ($rate['is_approve']){
                                    case 0:
                                        $update = [
                                            'is_approve' => 2,
                                            'reason' => request('reason'),
                                            'rejected_at' => mongo_time()
                                        ];
                                        $ok = Shared_Rate_Customer::where('_id', request('item'))->update($update);
                                        if($ok){
                                            if($user_id){
                                                $this->send_notic($user_id, request('reason'));
                                            }
                                            return response_custom('Từ chối đánh giá thành công!');
                                        }
                                        break;
                                    case 1:
                                        return response_custom('Đánh giá đã được duyệt rồi, bạn không thể từ chối!',1);
                                        break;
                                    case 2:
                                        return response_custom('Đã từ chối đánh giá này rồi!',1);
                                        break;
                                }
                            }else{
                                return response_custom('Vui lòng nhập lí do từ chối!',1);
                            }
                        }elseif(request('approve') == 1){ // Duyệt
                            switch ($rate['is_approve']){
                                case 0:
                                case 2:
                                    $arr_bonus = !empty(request('arr_bonus')) ? json_decode(request('arr_bonus'), true) : [];
                                    $update = [
                                        'is_approve' => 1,
                                        'arr_bonus' => $arr_bonus,
                                        'approved_at' => mongo_time()
                                    ];
                                    $ok = Shared_Rate_Customer::where('_id', request('item'))->update($update);
                                    if($ok){
                                        $this->bonus($data, $arr_bonus, $setting);
                                        return response_custom('Duyệt đánh giá thành công!');
                                    }
                                    break;
                                case 1:
                                    return response_custom('Đánh giá đã được duyệt rồi!',1);
                                    break;
                            }
                        }
                    }else{
                        return response_custom('Không tìm thấy hành động!',1);
                    }
                }else{
                    return response_custom('Không duyệt đánh giá của tài xế!',1);
                }
            }
        }
        return response_custom('Không tìm thấy đánh giá!',1);
    }

    function bonus($data, $arr_bonus, $setting){
        $money_bonus_driver = $setting['money_bonus_rate_driver'] ?? 0; // Số tiền tài xế được thưởng
        $spoint_bonus_customer = $setting['spoint_bonus_rate_customer'] ?? 0; // Số điểm KH được thưởng

        // Cộng tiền cho tài xế
        $wallet_sky = $data['driver']['partner']['wallet_sky'] ?? 0;
        $wallet_sky_total = $data['driver']['partner']['wallet_sky_total'] ?? 0;
        if(!empty($arr_bonus['add_money_driver']) && !empty($data['driver']) && $money_bonus_driver > 0){
            $add_history_wallet_sky = [
                'dbname' => $this->connection,
                'dbtable' => $this->table,
                'dbtableid' => request('item'),
                'type' => 'rate_bonus',
                'item_code' => $data['booking']['item_code'],
                'value_type' => 1,
                'value' => (double)$money_bonus_driver,
                'value_before' => (double)$wallet_sky,
                'value_after' => (double)($wallet_sky + $money_bonus_driver),
                'partner_id' => $data['driver']['partner']['_id'] ?? '',
                'is_status' => 1,
                'is_show' => 1,
                'lang' => 'vi',
                'created_at' => mongo_time(),
                'updated_at' => mongo_time(),
            ];
            $ok1 = History_Wallet_Sky::insert($add_history_wallet_sky);
            if($ok1){
                $update_partner = [
                    'wallet_sky' => (double)($wallet_sky + $money_bonus_driver),
                    'wallet_sky_total' => (double)($wallet_sky_total + $money_bonus_driver)
                ];
                Partner::where('_id', $data['driver']['partner']['_id'])->update($update_partner);
                $device_token_driver = Driver_Token::where('driver_id', $data['driver']['_id'])->pluck('device_token');
                if($device_token_driver){
                    $target_notic = Config('Api_app').'/firebase/api/messaging';
                    foreach ($device_token_driver as $item){
                        $data_notic_driver = [
                            'token' => $item,
                            'template' => 'addMoneyCustomerRateToDriver',
                            'arr_replace' => [
                                'body' => [
                                    'money' => formatNumber($money_bonus_driver),
                                    'booking' => $data['booking']['item_code']
                                ]
                            ],
                            'push_data' => [
                                'type' => 'addMoneyFromSharedRate'
                            ]
                        ];
                        Http::post($target_notic, $data_notic_driver)->json();
                    }
                }
            }
        }

        // Cộng điểm cho Khách hàng
        $wallet_point = $data['customer']['wallet_point'] ?? 0;
        $wallet_point_total = $data['customer']['wallet_point_total'] ?? 0;
        if(!empty($arr_bonus['add_point_customer']) && !empty($data['customer']) && $spoint_bonus_customer > 0){
            $add_wallet_point_log = [
                'dbname' => $this->connection,
                'dbtable' => $this->table,
                'dbtableid' => request('item'),
                'type' => 'rate_bonus',
                'item_code' => $data['booking']['item_code'],
                'value_type' => 1,
                'value' => (double)$spoint_bonus_customer,
                'value_before' => (double)$wallet_point,
                'value_after' => (double)($wallet_point + $spoint_bonus_customer),
                'user_id' => $data['customer']['_id'] ?? '',
                'is_status' => 1,
                'is_show' => 1,
                'lang' => 'vi',
                'created_at' => mongo_time(),
                'updated_at' => mongo_time(),
            ];
            $ok2 = Wallet_Point_Log::insert($add_wallet_point_log);
            if($ok2){
                $update_user = [
                    'wallet_point' => (double)($wallet_point + $spoint_bonus_customer),
                    'wallet_point_total' => (double)($wallet_point_total + $spoint_bonus_customer),
                    'wallet_point_change' => 1
                ];
                User::where('_id', $data['customer']['_id'])->update($update_user);
                $device_token_user = DeviceToken::where('user_id', $data['customer']['_id'])->pluck('device_token');
                if($device_token_user){
                    $target_notic = Config('Api_app').'/firebase/api/messaging';
                    foreach ($device_token_user as $item){
                        $data_notic_user = [
                            'token' => $item,
                            'template' => 'addSpointCustomerRateToUser',
                            'arr_replace' => [
                                'body' => [
                                    'spoint' => formatNumber($spoint_bonus_customer),
                                    'booking' => $data['booking']['item_code']
                                ]
                            ],
                            'push_data' => [
                                'type' => 'addSpointFromSharedRate'
                            ]
                        ];
                        Http::post($target_notic, $data_notic_user)->json();
                    }
                }
            }
        }
    }

    function send_notic($user, $reason){
        $device_token = DeviceToken::where('user_id', $user)->pluck('device_token');
        if($device_token){
            $target_notic = Config('Api_app').'/firebase/api/messaging';
            foreach ($device_token as $item){
                $data = [
                    'token' => $item,
                    'push_noti' => [
                        'title' => 'Đánh giá của bạn đã bị từ chối!',
                        'body' => $reason
                    ]
                ];
                Http::post($target_notic, $data)->json(); // Gửi thông báo đến tài khoản bị khóa
            }
        }
    }
}
