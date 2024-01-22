<?php

namespace App\Models\Sky\User;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Wallet_Point_Log extends Model{
    public $timestamps = false;
    protected $connection = 'sky_user';
    protected $table = 'wallet_point_log';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function user_info(){
        return $this->hasOne(User::class, '_id', 'user_id')->select(['full_name','phone','picture']);
    }

    // Danh sách user
    function listWalletPointLog(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = Wallet_Point_Log::filter()
            ->with('user_info')
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        $data['other']['type'] = Wallet_Point_Type::get(['title','bg_color','text_color','class','name_action','description'])->keyBy('name_action');
        $data['other']['status'] = Wallet_Point_Status::get(['title','bg_color','text_color','class','value'])->keyBy('value');
        $data['other']['minus_total'] = Wallet_Point_Log::filter()->where('value_type', -1)->where('is_status', 1)->sum('value');
        $data['other']['add_total'] = Wallet_Point_Log::filter()->where('value_type', 1)->where('is_status', 1)->sum('value');

        $arr_replace_root = ['bank_name','method','created_at','item_code'];
        if(!empty($data['data'])){
            foreach ($data['data'] as $k => $v){
                $transaction_info = DB::connection($v['dbname'])->collection($v['dbtable'])->where('_id', $v['dbtableid'])->first();
                if($transaction_info){
                    $transaction_info['created_at'] = date('H:i d-m-Y', parseTimestamp($transaction_info['created_at']));
                    foreach ($arr_replace_root as $item){
                        $arr_replace[] = '{'.$item.'}';
                        $arr_value[] = $transaction_info[$item] ?? '';
                    }
                    $data['data'][$k]['description'] = (!empty($data['other']['type']) && !empty($data['data'][$k]['type'])) ? str_replace($arr_replace, $arr_value, $data['other']['type'][$data['data'][$k]['type']]['description']) : '';
                }
            }
        }
        return response_pagination($data);
    }

    public static function scopeFilter($query)
    {
        $query->when(!empty(request('keyword')) ?? null, function ($query){
            $keyword = explode_custom(request('keyword'),' ');
            $query->whereHas('user_info', function($q) use($keyword){
                if($keyword){
                    foreach ($keyword as $item){
                        $q->orWhere('full_name', 'LIKE', '%' . $item . '%');
                    }
                }
                $q->orWhere('phone', 'LIKE', '%'.request('keyword').'%')
                    ->orWhere('email', 'LIKE', '%'.request('keyword').'%');
            })->orWhere('item_code', 'LIKE', '%'.request('keyword').'%');
        })
            ->when(!empty(request('date_start')) ?? null, function ($query){
                $date_start = convert_date_search(request('date_start'));
                $query->whereDate("created_at", ">=", $date_start);
            })
            ->when(!empty(request('date_end')) ?? null, function ($query){
                $date_end = convert_date_search(request('date_end'));
                $query->whereDate("created_at", "<=", $date_end);
            })
            ->when(!empty(request('item')) ?? null, function ($query){
                $query->where('user_id', request('item'));
            });
    }

    // Thêm điểm user
    function adminAddMinusPointUser(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('arr_data'))){
            $arr_data = json_decode(request('arr_data'), true);
            if($arr_data){
                if(!empty($arr_data['user_id'])){
                    $user = User::where('_id', $arr_data['user_id'])->first();
                    if($user){
                        $user = $user->toArray();
                        if(!empty($arr_data['type']) && in_array($arr_data['type'], ['admin_add','admin_minus'])){
                            $value_type = ($arr_data['type'] == 'admin_add') ? 1 : -1;
                            if(!empty($arr_data['value']) || !is_numeric($arr_data['value'])){
                                $add_wallet_point_log = [
                                    'dbname' => 'sky_user',
                                    'dbtable' => 'wallet_point_log',
                                    'dbtableid' => '',
                                    'type' => $arr_data['type'],
                                    'item_code' => '',
                                    'value_type' => $value_type,
                                    'value' => (float)$arr_data['value'],
                                    'value_before' => (float)$user['wallet_point'],
                                    'value_after' => (float)($user['wallet_point'] + $arr_data['value']),
                                    'user_id' => $arr_data['user_id'] ?? '',
                                    'is_status' => 1,
                                    'is_show' => 1,
                                    'lang' => 'vi',
                                    'created_at' => mongo_time(),
                                    'updated_at' => mongo_time(),
                                ];
                                $ok2 = Wallet_Point_Log::insert($add_wallet_point_log);
                                if($ok2){
                                    $update_user = [
                                        'wallet_point' => (float)($user['wallet_point'] + $arr_data['value']),
                                        'wallet_point_total' => (float)($user['wallet_point_total'] + $arr_data['value']),
                                        'wallet_point_change' => 1
                                    ];
                                    User::where('_id', $user['_id'])->update($update_user);
                                    $device_token_user = DeviceToken::where('user_id', $user['_id'])->pluck('device_token');
                                    if($device_token_user){
                                        $target_notic = Config('Api_app').'/firebase/api/messaging';
                                        foreach ($device_token_user as $item){
                                            $data_notic_user = [
                                                'token' => $item,
                                                'template' => 'addSpointCustomerRateToUser',
                                                'arr_replace' => [
                                                    'body' => [
                                                        'spoint' => number_format($spoint_bonus_customer,0,',','.'),
                                                        'booking' => $data['booking']['item_code']
                                                    ]
                                                ],
                                                'push_data' => [
                                                    'type' => 'addSpointFromAdmin'
                                                ]
                                            ];
                                            Http::post($target_notic, $data_notic_user)->json();
                                        }
                                    }
                                }
                            }else{
                                return response_custom('Vui lòng nhập số điểm cộng!', 1);
                            }
                        }
                    }else{
                        return response_custom('Không tìm thấy thành viên!', 1);
                    }
                }else{
                    return response_custom('Vui lòng chọn thành viên!', 1);
                }
            }
        }else{
            return response_custom('Không tìm thấy dữ liệu!', 1);
        }
        return response_custom('Thao tác không thành công!', 1);
    }
}
