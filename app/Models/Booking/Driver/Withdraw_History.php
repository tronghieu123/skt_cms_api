<?php

namespace App\Models\Booking\Driver;

use Illuminate\Support\Facades\Http;
use MongoDB\Laravel\Eloquent\Model;

class Withdraw_History extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_payment';
    protected $table = 'withdraw_history';

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function driver() {
        return $this->hasOne(Driver::class, 'partner_id',  'partner_id');
    }

    public function getListWithdrawHistory(){

        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }

        $data = Withdraw_History::when(request('type') == 'pending' ?? null, function ($query){
                $query->where('is_status', 0); // Đợi duyệt
            })
            ->when(request('type') == 'approved' ?? null, function ($query){
                $query->where('is_status', 1); // Đã duyệt
            })
            ->when(request('type') == 'reject' ?? null, function ($query){
                $query->where('is_status', 2); // từ chối
            })
            ->with(['driver' => function ($query) {
                $query->with(['partner' => function ($partner){
                    $partner->select('phone', 'full_name', 'email');
                }])->with(['info' => function ($info){
                    $info->select('avatar', '_id', 'driver_id');
                }])->without(['vehicle_type', 'approve'])
                ->select('partner', 'partner_id');
            }])
            ->filter()
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();

        $data['other']['counter'] = $this->counter();

        return response_pagination($data);
    }

    function counter() {
        $data['all'] = Withdraw_History::filter()->count();
        $data['pending'] = Withdraw_History::filter()->where('is_status', 0)->count();
        $data['approved'] = Withdraw_History::filter()->where('is_status', 1)->count();
        $data['reject'] = Withdraw_History::filter()->where('is_status', 2)->count();
        return $data;
    }

    public static function scopeFilter($query){
        $query->where('type', 'driver')
            ->where('is_show', 1)
            ->when(!empty(request('keyword')) ?? null, function ($query){
                $keyword = explode_custom(request('keyword'),' ');
                $query->whereHas('driver.partner', function($q) use($keyword)
                {
                    if($keyword){
                        foreach ($keyword as $item){
                            $q->orWhere('full_name', 'LIKE', '%' . $item . '%');
                        }
                    }
                    $q->orWhere('phone', 'LIKE', '%'.request('keyword').'%')
                        ->orWhere('email', 'LIKE', '%'.request('keyword').'%');
                })->orWhere('item_code', 'LIKE', '%' . request('keyword') . '%');
            })
            ->when(!empty(request('date_start')) ?? null, function ($query){
                $date_start = convert_date_search(request('date_start'));
                $query->whereDate("created_at", ">=", $date_start);
            })
            ->when(!empty(request('date_end')) ?? null, function ($query){
                $date_end = convert_date_search(request('date_end'));
                $query->whereDate("created_at", "<=", $date_end);
            });
    }

    // Xét duyệt rút tiền của khách hàng
    function approvalWithdrawDriver(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('item'))){
            $withdraw = Withdraw_History::where('_id', request('item'))->where('is_show', 1)->where('type', 'driver')->first();
            if($withdraw){
                $withdraw = $withdraw->toArray();
                $partner = Partner::where('_id', $withdraw['partner_id'])->first();
                if($partner){
                    $partner = $partner->toArray();
                    $wallet_sky = !empty($partner['wallet_sky']) ? $partner['wallet_sky'] : 0;
                    if($withdraw['is_approve'] == 1){
                        return response_custom('Yêu cầu rút tiền đã được duyệt!', 1);
                    }elseif ($withdraw['is_approve'] == 2){
                        return response_custom('Yêu cầu rút tiền đã từ chối!', 1);
                    }
                    if(request()->has('approve')){
                        switch (request('approve')){
                            case 0:
                                if(!empty(request('reason'))){
                                    $reject = [
                                        'is_approve' => 2,
                                        'is_status' => 2,
                                        'reason' => request('reason'),
                                        'rejected_at' => mongo_time()
                                    ];
                                    $ok = Withdraw_History::where('_id', request('item'))->update($reject);
                                    if($ok){
                                        $history_wallet_sky = [
                                            'dbname' => $this->connection,
                                            'dbtable' => $this->table,
                                            'dbtableid' => $withdraw['_id'],
                                            'type' => 'refund_reject_withdraw',
                                            'value_type' => 1,
                                            'value' => (double)$withdraw['value'],
                                            'value_before' => (double)$wallet_sky,
                                            'value_after' => (double)($wallet_sky + $withdraw['value']),
                                            'partner_id' => $partner['_id'],
                                            'is_show' => 1,
                                            'is_status' => 1,
                                            'item_code' => $withdraw['item_code'],
                                            'created_at' => mongo_time(),
                                            'updated_at' => mongo_time()
                                        ];
                                        $ok1 = History_Wallet_Sky::insert($history_wallet_sky);
                                        if($ok1){
                                            $update_partner = [
                                                'wallet_sky' => (double)($wallet_sky + $withdraw['value'])
                                            ];
                                            Partner::where('_id', $partner['_id'])->update($update_partner);
                                            $this->sendNotificationWithdraw($withdraw);
                                            return response_custom('Từ chối rút tiền thành công!');
                                        }
                                    }
                                }else{
                                    return response_custom('Vui lòng nhập lí do từ chối!', 1);
                                }
                                break;
                            case 1:
                                $accept = [
                                    'is_approve' => 1,
                                    'is_status' => 1,
                                    'approved_at' => mongo_time()
                                ];
                                $ok = Withdraw_History::where('_id', request('item'))->update($accept);
                                if($ok){
                                    $this->sendNotificationWithdraw($withdraw);
                                    return response_custom('Duyệt rút tiền thành công!');
                                }
                                break;
                        }
                    }else{
                        return response_custom('Không tìm thấy hành động!', 1);
                    }
                }else{
                    return response_custom('Không tìm thấy thành viên!', 1);
                }
            }
        }
        return response_custom('Thao tác không thành công!', 1);
    }

    function sendNotificationWithdraw($withdraw){
        $device_token = Driver_Token::where('driver_id', request('item'))->pluck('device_token');
        if($device_token){
            $template = (request('approve') == 1) ? 'acceptWithdrawDriver' : 'rejectWithdrawDriver';
            $target_notic = Config('Api_app').'/firebase/api/messaging';
            foreach ($device_token as $token){
                $notification = [
                    'token' => $token,
                    'template' => $template,
                    'arr_replace' => [
                        'body' => [
                            'created_at' => date('H:i d-m-Y', $withdraw['created_at']),
                            'value' => formatNumber($withdraw['value']),
                            'time' => date('H:i d-m-Y'),
                            'reason' => request('reason')
                        ]
                    ],
                    'push_data' => [
                        'type' => 'approvalWithdraw'
                    ]
                ];
                Http::post($target_notic, $notification)->json();
            }
        }
    }
}
