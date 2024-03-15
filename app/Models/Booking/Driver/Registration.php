<?php

namespace App\Models\Booking\Driver;

use App\Http\Token;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Registration extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'driver';
    protected $hidden = ['password'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'updated_at_online' => 'timestamp',
        'updated_at_position' => 'timestamp',
        'code_expiry' => 'timestamp',
        'locked_at' => 'timestamp',
        'unlocked_at' => 'timestamp',
        'rank_expiration' => 'timestamp',
        'approved_complete_at' => 'timestamp'
    ];

    public function info() {
        return $this->hasOne(Driver_Info::class, 'driver_id',  '_id');
    }

    public function vehicle_type() {
        return $this->hasOne(Vehicle::class, '_id',  'vehicle')->select('name_action','picture','title');
    }

    public function partner() {
        return $this->hasOne(Partner::class, '_id',  'partner_id');
    }

    public function approve() {
        return $this->hasMany(Driver_Register_Step::class, 'driver_id',  '_id')
            ->orderBy('step','asc');
    }

    public function listDriver() {
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = Driver::whereHas('info', function($query){
                $query->where('vehicle_registration_expired', '<=', strval(now()->addDays(30)->getTimestamp()));
            })
            ->filter()
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();

        if(!empty($data['data'])){
            foreach ($data['data'] as $k => $v){
                $partner_id = !empty($v['partner']['_id']) ? $v['partner']['_id'] : '';
                if($partner_id){
                    $v['total_wallet_cash'] = History_Wallet_Sky::where('partner_id', $partner_id)->where('is_show', 1)->where('is_status', 1)->where('value_type', 1)->sum('value');
                    $v['total_withdraw'] = Withdraw_History::where('partner_id', $partner_id)->where('is_show', 1)->where('type', 'driver')->where('is_status', 1)->sum('value');
                    $v['total_recharge'] = Recharge_History::where('partner_id', $partner_id)->where('is_show', 1)->where('type', 'driver')->where('is_status', 1)->sum('value');
                    $data['data'][$k] = $v;
                }
            }
        }

        return response_pagination($data);
    }

    public static function scopeFilter($query)
    {
        $query->when(!empty(request('keyword')) ?? null, function ($q){
            $keyword = explode_custom(request('keyword'), ' ');
            if($keyword){
                foreach ($keyword as $item){
                    $q->orWhere('full_name', 'LIKE', '%'.$item.'%');
                }
            }
            $q->orWhere('phone', 'LIKE', '%'.request('keyword').'%')
            ->orWhere('email', 'LIKE', '%'.request('keyword').'%');
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

    // Duyệt tài xế
    function approveDriver(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('item'))){
            $driver = Driver::where('_id', request('item'))->first();
            if($driver){
                if($driver['is_approve'] == 2){
                    return response_custom('Tài xế đã bị khóa!', 1);
                }
                $driver = $driver->toArray();
                // -------------- Xe 2 bánh, 3 bánh --------------
                $total_step = 7;
                $arr_step = ['all',1,2,3,4,5,6,7];
                $arr_title = [
                    1 => 'CMND/CCCD/Hộ chiếu',
                    2 => 'Bằng lái xe 2 bánh',
                    3 => 'Giấy đăng ký xe',
                    4 => 'Ảnh đại diện',
                    5 => 'Bảo hiểm',
                    6 => 'Thông tin sim chính chủ',
                    7 => 'Giấy tờ khác',
                ];
                // -------------- Xe 4 bánh, xe tải --------------
                if(!empty($driver['vehicle_type']['name_action'])){
                    if(in_array($driver['vehicle_type']['name_action'],['car','truck'])){
                        $total_step = 11;
                        $arr_step = ['all',1,2,3,4,5,6,7,8,9,10,11];
                        $arr_title = [
                            1 => 'CMND/CCCD/Hộ chiếu',
                            2 => 'Bằng lái xe',
                            3 => 'Giấy đăng ký xe',
                            4 => 'Ảnh đại diện',
                            5 => 'Bảo hiểm',
                            6 => 'Đăng kiểm xe loại kinh doanh',
                            7 => 'Phù hiệu hợp đồng',
                            8 => 'Giấy khám sức khỏe lái xe',
                            9 => 'Thông tin sim chính chủ',
                            10 => 'Giấy tờ khác',
                            11 => 'Biển số xe vàng'
                        ];
                    }
                }

                $approve = Driver_Register_Step::where('driver_id', request('item'))
                    ->orderBy('step','asc')
                    ->get()
                    ->keyBy('_id')->toArray();
                if(!empty($approve)){
                    if(!request()->has('step') || !in_array(request('step'), $arr_step)){
                        return response_custom('Bước duyệt không hợp lệ!',1);
                    }
                    if(!request()->has('accept') || !in_array(request('accept'),[0,1,2])){
                        return response_custom('Trạng thái duyệt không hợp lệ!',1);
                    }
                    if(request('step') == 'all'){
                        if(request('accept') == 1){
                            $count_accept = 0;
                            foreach ($approve as $k => $v){
                                if($v['status'] == 0){
                                    $update = [
                                        'status' => 1,
                                        'updated_at' => mongo_time()
                                    ];
                                    Driver_Register_Step::where('_id', $k)->update($update);
                                    $count_accept++;
                                }
                            }
                            if($count_accept == 0){
                                return response_custom('Các bước đã được duyệt rồi!',1);
                            }else{
                                $check_total = Driver_Register_Step::where('driver_id', request('item'))
                                    ->where('status', 1)
                                    ->count();
                                if($check_total == $total_step){ // Cập nhật trạng thái khi Tất cả các bước đã được duyệt
                                    $update_driver = [
                                        'is_approve' => 1,
                                        'approved_complete_at' => mongo_time()
                                    ];
                                    Driver::where('_id', request('item'))->update($update_driver);
                                    // Gửi thông báo đã duyệt cho tài xế
                                    $data = [
                                        'template' => 'registrationApproved'
                                    ];
                                    $this->notifyApproveDriver($driver, $data);
                                }
                                return response_custom('Duyệt tất cả thành công!');
                            }
                        }else{
                            return response_custom('Trạng thái duyệt không hợp lệ!',1);
                        }
                    }else{
                        $ok = 0;
                        foreach ($approve as $k => $v){
                            if(request('step') == $v['step']){
                                switch ($v['status']){
                                    case 0:
                                        if(request('accept') == 0){ // Từ chối duyệt
                                            if(empty(request('reason'))){
                                                return response_custom('Vui lòng nhập lí do từ chối duyệt!',1);
                                            }else{
                                                $update = [
                                                    'status' => 2,
                                                    'reason' => request('reason'),
                                                    'updated_at' => mongo_time()
                                                ];
                                                Driver_Register_Step::where('_id', $k)->update($update);
                                                // Gửi thông báo từ chối duyệt đến các thiết bị tài khoản đang đăng nhập
                                                $data = [
                                                    'template' => 'rejectStepDriver',
                                                    'step' => $arr_title[request('step')],
                                                    'reason' => request('reason')
                                                ];
                                                $this->notifyApproveDriver($driver, $data);
                                                return response_custom('Từ chối '.$arr_title[request('step')].' thành công!',0, color_status(2));
                                            }
                                        }elseif(request('accept') == 1){
                                            $update = [
                                                'status' => 1,
                                                'updated_at' => mongo_time()
                                            ];
                                            $ok = 1;
                                            Driver_Register_Step::where('_id', $k)->update($update);
                                        }elseif(request('accept') == 2){ // Duyệt lại
                                            return response_custom($arr_title[request('step')].' chưa được duyệt!', 1);
                                        }
                                        break;
                                    case 1:
                                        if(request('accept') == 2){ // Duyệt lại
                                            if(empty(request('reason'))){
                                                return response_custom('Vui lòng nhập lí do yêu cầu duyệt lại!',1);
                                            }else{
                                                $update_step = [
                                                    'status' => 3,
                                                    'reason' => request('reason'),
                                                    'updated_at' => mongo_time()
                                                ];
                                                Driver_Register_Step::where('_id', $k)->update($update_step);
                                                $update_driver = [
                                                    'is_approve' => 3,
                                                    'updated_at' => mongo_time()
                                                ];
                                                Driver::where('_id', request('item'))->update($update_driver);
                                                // Gửi thông báo duyệt lại cho tài xế
                                                $data = [
                                                    'template' => 'reapproveStepDriver',
                                                    'step' => $arr_title[request('step')],
                                                    'reason' => request('reason')
                                                ];
                                                $this->notifyApproveDriver($driver, $data);
                                                // Log out tài khoản ra mọi thiết bị
                                                $target = Config('Api_app').'/booking/api/logout';
                                                $token = (new Token)->getToken($target, request('item'));
                                                Http::withToken($token)->post($target, ['logout_all' => 1, 'user_id' => request('item')])->json();
                                                return response_custom('Yêu cầu duyệt lại '.$arr_title[request('step')].' thành công!',0, color_status(3));
                                            }
                                        }else{
                                            return response_custom($arr_title[request('step')].' đã duyệt rồi!',1);
                                        }
                                        break;
                                    case 2:
                                        return response_custom($arr_title[request('step')].' đã từ chối duyệt rồi!',1);
                                        break;
                                    case 3:
                                        return response_custom($arr_title[request('step')].' đang chờ duyệt lại!',1);
                                        break;
                                }
                                break;
                            }
                        }
                        if($ok == 1){
                            $check_total = Driver_Register_Step::where('driver_id', request('item'))
                                ->where('status', 1)
                                ->count();
                            if($check_total == $total_step){ // Cập nhật trạng thái khi Tất cả các bước đã được duyệt
                                $update_driver = [
                                    'is_approve' => 1,
                                    'approved_complete_at' => mongo_time()
                                ];
                                Driver::where('_id', request('item'))->update($update_driver);
                                // Gửi thông báo đã duyệt cho tài xế
                                $data = [
                                    'template' => 'registrationApproved'
                                ];
                                $this->notifyApproveDriver($driver, $data);
                            }
                            return response_custom('Duyệt '.$arr_title[request('step')].' thành công!',0, color_status(1));
                        }else{
                            return response_custom('Tài xế chưa xác thực '.$arr_title[request('step')].'!',1);
                        }
                    }
                }else{
                    if(request('step') == 'all'){
                        return response_custom('Tài xế chưa xác thực thông tin!',1);
                    }else{
                        return response_custom('Tài xế chưa xác thực '.$arr_title[request('step')].'!',1);
                    }
                }
            }else{
                return response_custom('Không tìm thấy tài xế!',1);
            }
        }else{
            return response_custom('Không tìm thấy tài xế!',1);
        }
    }

    function notifyApproveDriver($driver, $data){
        $device_token = Driver_Token::where('driver_id', $driver['_id'])->pluck('device_token');
        $step_name = !empty($data['step'])  ? $data['step'] : '';
        if($device_token){
            $target_notic = Config('Api_app').'/firebase/api/messaging';
            foreach ($device_token as $item){
                $data = [
                    'token' => $item,
                    'template' => $data['template'],
                    'arr_replace' => [
                        'title' => [
                            'full_name' => $driver['full_name'],
                            'step' => $step_name
                        ],
                        'body' => [
                            'created_at' => date('H:i d/m/Y'),
                            'step' => $step_name,
                            'reason' => !empty($data['reason']) ? $data['reason'] : ''
                        ]
                    ],
                    'push_data' => [
                        'type' => $data['template']
                    ]
                ];
                Http::post($target_notic, $data)->json();
            }
        }
    }

    // Tìm kiếm tài xế
    function searchDriver(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('keyword'))){
            $driver = Driver::without('info','approve','vehicle_type','partner')->where('phone', 'LIKE', '%'.request('keyword').'%')
                ->orWhere('full_name', 'LIKE', '%'.request('keyword').'%')
                ->get(['phone','full_name'])
                ->keyBy('_id')
                ->toArray();
            $data = [];
            if($driver){
                $get = ['phone', 'full_name'];
                foreach ($driver as $k => $v){
                    $value = [];
                    foreach ($get as $item){
                        if(!empty($v[$item])){
                            $value[] = $v[$item];
                        }
                    }
                    $data[$k] = implode(' - ',$value);
                }
            }
            return response_custom('',0, $data);
        }else{
            return response_custom('Không tìm thấy từ khóa',1);
        }
    }

}
