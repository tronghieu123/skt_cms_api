<?php

namespace App\Models\Booking\Driver;

use App\Http\Token;
use App\Models\System\Driver\Driver_Contract_Template;
//use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Driver extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'driver';
    protected $with = ['info','vehicle_type','partner','approve'];
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
//        DB::connection($this->connection)->enableQueryLog();
        $data = Driver::when(request('type') == 'pending' ?? null, function ($query){
                $query->where('is_approve', 0)->orWhere('is_approve', null); // Đợi duyệt
            })
            ->when(request('type') == 'approved' ?? null, function ($query){
                $query->where('is_approve', 1); // Đã duyệt
            })
            ->when(request('type') == 'banned' ?? null, function ($query){
                $query->where('is_approve', 2); // Đã khóa
            })
            ->filter()
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
//        $q = DB::connection($this->connection)->getQueryLog();
//        dd($q);
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
        $data['other']['counter'] = $this->tabListDriver();
        return response_pagination($data);
    }

    function tabListDriver(){
        $data['all'] = Driver::filter()->count();
        $data['pending'] = Driver::filter()->where('is_approve', 0)->orWhere('is_approve', null)->count();
        $data['approved'] = Driver::filter()->where('is_approve', 1)->count();
        $data['banned'] = Driver::filter()->where('is_approve', 2)->count();
        return $data;
    }

    public static function scopeFilter($query)
    {
        $query->when(!empty(request('keyword')), function ($q){
            $keyword = explode_custom(request('keyword'), ' ');
            if($keyword){
                $q->where(function($q1) use ($keyword){
                    foreach ($keyword as $item){
                        $q1->orWhere(function($q2) use ($item){
                            $q2->where('full_name', 'LIKE', '%'.$item.'%')->orWhere('full_name', 'LIKE', '%'.Str::ascii($item).'%');
                        });
                    }
                });
            }
            $q->orWhere('phone', 'LIKE', '%'.request('keyword').'%')
            ->orWhere('email', 'LIKE', '%'.request('keyword').'%');
        })
        ->when(!empty(request('date_start')), function ($query){
            $date_start = convert_date_search(request('date_start'));
            $query->whereDate("created_at", ">=", $date_start);
        })
        ->when(!empty(request('date_end')), function ($query){
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

    // Ghi chú tài xế
    function noteDriver(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('arr_user'))){
            $arr_user = json_decode(request('arr_user'),true);
            foreach ($arr_user as $user_id => $item){
                $check_user = Driver::where('_id', $user_id)->value('phone');
                if($check_user){
                    $check_exist = Driver_Info::where('driver_id', $user_id)->value('_id');
                    if($check_exist){
                        $update = [
                            'note' => $item['note'],
                            'noted_at' => mongo_time()
                        ];
                        Driver_Info::where('_id', $check_exist)->update($update);
                    }else{
                        $insert = [
                            'driver_id' => $user_id,
                            'note' => $item['note'],
                            'noted_at' => mongo_time(),
                            'created_at' => mongo_time(),
                            'updated_at' => mongo_time(),
                        ];
                        Driver_Info::insert($insert);
                    }
                }
            }
            return response_custom('Thêm ghi chú thành công!');
        }else{
            return response_custom('Không tìm thấy tài xế!',1);
        }
    }

    // Lấy chi tiết tài xế
    function detailDriver(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(empty(request('item'))){
            return response_custom('Không tìm thấy tài xế!', 1);
        }
        $data = Driver::where('_id', request('item'))->first();
        if($data){
            $data = $data->toArray();
            $api_data_root = [
                'root' => 'sky',
                'mod' => 'config',
                'act' => 'location',
                'country' => 'vi'
            ];
            $api_link = Config('Api_link').'/getLocation';
            $result = Http::post($api_link, $api_data_root)->json();
            // Tỉnh thành quận huyện thường trú
            $data['location_permanent_province'] = !empty($result['data']) ? $result['data'] : [];
            if(!empty($data['info']['permanent_province'])){
                $api_data = array_merge($api_data_root, ['province' => (string)$data['info']['permanent_province']]);
                $result = Http::post($api_link, $api_data)->json();
                $data['location_permanent_district'] = !empty($result['data']) ? $result['data'] : [];
            }else{
                $data['location_permanent_district'] = [];
            }
            if(!empty($data['info']['permanent_district'])){
                $api_data = array_merge($api_data_root, ['district' => (string)$data['info']['permanent_district']]);
                $result = Http::post($api_link, $api_data)->json();
                $data['location_permanent_ward'] = !empty($result['data']) ? $result['data'] : [];
            }else{
                $data['location_permanent_ward'] = [];
            }
            // Tỉnh thành quận huyện tạm trú
            $data['location_temporary_residence_province'] = $data['location_permanent_province'];
            if(!empty($data['info']['temporary_residence_province'])){
                $api_data = array_merge($api_data_root, ['province' => (string)$data['info']['temporary_residence_province']]);
                $result = Http::post($api_link, $api_data)->json();
                $data['location_temporary_residence_district'] = !empty($result['data']) ? $result['data'] : [];
            }else{
                $data['location_temporary_residence_district'] = [];
            }
            if(!empty($data['info']['temporary_residence_district'])){
                $api_data = array_merge($api_data_root, ['district' => (string)$data['info']['temporary_residence_district']]);
                $result = Http::post($api_link, $api_data)->json();
                $data['location_temporary_residence_ward'] = !empty($result['data']) ? $result['data'] : [];
            }else{
                $data['location_temporary_residence_ward'] = [];
            }

            // Các bước duyệt tài xế
            $check = 0;
            $check_reapprove = 0;
            if(!empty($data['approve'])){
                $approve = [];
                foreach ($data['approve'] as $k => $v){
                    $status_info = color_status($v['status']);
                    $approve[$v['step']] = array_merge($data['approve'][$k], $status_info);
                    if($v['status'] == 0){
                        $check++;
                    }elseif ($v['status'] == 3){
                        $check_reapprove++;
                    }
                }
                $data['approve'] = $approve;
            }
            if($check){
                $data['is_status'] = 0; // Chưa duyệt
            }elseif($check_reapprove){
                $data['is_status'] = 3; // Chờ duyệt lại
            }
            if(!empty($data['info']['vehicle_brand'])){
                $data['info']['vehicle_brand'] = Vehicle_Brand::where('_id', $data['info']['vehicle_brand'])->value('title');
            }
            if(!empty($data['info']['vehicle_model'])){
                $data['info']['vehicle_model'] = Vehicle_Models::where('_id', $data['info']['vehicle_model'])->value('title');
            }
            $data['vehicle_type'] = !empty($data['vehicle_type']['name_action']) ? $data['vehicle_type']['name_action'] : '';
            $data['driver_contract'] = Driver_Contract::where('driver_id', request('item'))->get()->toArray();
            $contract_status = -2;
            if($data['driver_contract']){
                $contract_status = -1;
                $driver_contract_status = Driver_Contract_Status::where('is_show', 1)
                    ->where('lang', Config('lang_cur'))->get(['title','color_text','color_background','value'])->keyBy('value')->toArray();
                $count_approve_contract = 0;
                foreach ($data['driver_contract'] as $k => $item){
                    if($item['status'] == 2){
                        $count_approve_contract++; // Tổng hợp đồng đã duyệt
                    }
                    switch ($item['type']){
                        case 'vehicle':
                            if(!empty($data['info']['vehicle'])){
                                $item['contract'] = Driver_Contract_Template::where('type_id', $data['info']['vehicle'])->value('content');
                            }
                            if(isset($driver_contract_status[$item['status']])){
                                unset($driver_contract_status[$item['status']]['value']);
                                $item = array_merge($item,$driver_contract_status[$item['status']]);
                            }
                            break;
                    }
                    $data['driver_contract'][$k] = $item;
                }
                if($count_approve_contract == count($data['driver_contract'])){
                    $contract_status = 1; // Tất cả hợp đồng đã được duyệt
                }
            }
            $data['approve']['contract'] = color_status($contract_status);
            return response_custom('',0, $data);
        }else{
            return response_custom('Không tìm thấy tài xế!',1);
        }
    }

    // Lưu thông tin tài xế
    function saveDriver(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(request()->has('arr_data')){
            if(!request()->has('item')){
                return response_custom('Không tìm thấy tài xế',1);
            }
            $check = Driver::where('_id', request('item'))->first();
            if($check){
                $check = $check->toArray();
                $check_info = Driver_Info::where('driver_id', request('item'))->value('_id'); // Kiểm tra có thông tin hay chưa

                $type = (!empty($check['vehicle_type']['name_action']) && in_array($check['vehicle_type']['name_action'],['car','truck'])) ? 2 : 1;
                $arr_data = json_decode(request('arr_data'),true);

                $ok = 0;
                $info = [];
                $info['driver_id'] = request('item');
                $info['identification'] = !empty($arr_data['identification']) ? $arr_data['identification'] : '';
                if(!empty($arr_data['birthday'])){
                    $info['birthday'] = convert_date($arr_data['birthday']);
                }
                if(!empty($arr_data['identification_issuancedate'])){
                    $info['identification_issuancedate'] = convert_date($arr_data['identification_issuancedate']);
                }
                $info['gender'] = !empty($arr_data['gender']) ? (int)$arr_data['gender'] : 0;
                $info['identification_front'] = !empty($arr_data['identification_front']) ? $arr_data['identification_front'] : '';
                $info['identification_backside'] = !empty($arr_data['identification_backside']) ? $arr_data['identification_backside'] : '';
                $info['temporary_residence_province'] = !empty($arr_data['temporary_residence_province']) ? $arr_data['temporary_residence_province'] : '';
                $info['temporary_residence_district'] = !empty($arr_data['temporary_residence_district']) ? $arr_data['temporary_residence_district'] : '';
                $info['temporary_residence_ward'] = !empty($arr_data['temporary_residence_ward']) ? $arr_data['temporary_residence_ward'] : '';
                $info['temporary_residence_address'] = !empty($arr_data['temporary_residence_address']) ? $arr_data['temporary_residence_address'] : '';
                $info['permanent_province'] = !empty($arr_data['permanent_province']) ? $arr_data['permanent_province'] : '';
                $info['permanent_district'] = !empty($arr_data['permanent_district']) ? $arr_data['permanent_district'] : '';
                $info['permanent_ward'] = !empty($arr_data['permanent_ward']) ? $arr_data['permanent_ward'] : '';
                $info['permanent_address'] = !empty($arr_data['permanent_address']) ? $arr_data['permanent_address'] : '';
                $info['vehicle_license_plates'] = !empty($arr_data['vehicle_license_plates']) ? $arr_data['vehicle_license_plates'] : '';
                $info['vehicle_picture'] = !empty($arr_data['vehicle_picture'][0]) ? json_encode(explode(',',$arr_data['vehicle_picture'][0])) : json_encode([]);
                $info['avatar'] = !empty($arr_data['avatar']) ? $arr_data['avatar'] : '';
                if(!empty($arr_data['insurance_expiration_date'])){
                    $info['insurance_expiration_date'] = convert_date($arr_data['insurance_expiration_date']);
                }
                $info['insurance_picture'] = !empty($arr_data['insurance_picture'][0]) ? json_encode(explode(',',$arr_data['insurance_picture'][0])) : json_encode([]);
                $info['mainsim_picture'] = !empty($arr_data['mainsim_picture']) ? $arr_data['mainsim_picture'] : '';
                $info['other_documents'] = !empty($arr_data['other_documents'][0]) ? json_encode(explode(',',$arr_data['other_documents'][0])) : json_encode([]);
                $info['license_front'] = !empty($arr_data['license_front']) ? $arr_data['license_front'] : '';
                $info['license_backside'] = !empty($arr_data['license_backside']) ? $arr_data['license_backside'] : '';
                if($type == 2){ // Thêm thông tin của xe 4 bánh
                    if(!empty($arr_data['license_expiration_date'])){
                        $info['license_expiration_date'] = convert_date($arr_data['license_expiration_date']);
                    }
                    if(!empty($arr_data['vehicle_registration_expired'])){
                        $info['vehicle_registration_expired'] = convert_date($arr_data['vehicle_registration_expired']);
                    }
                    $info['vehicle_yearmanufacture'] = !empty($arr_data['vehicle_yearmanufacture']) ? (int)$arr_data['vehicle_yearmanufacture'] : 0;
                    $info['vehicle_registration_picture'] = !empty($arr_data['vehicle_registration_picture']) ? $arr_data['vehicle_registration_picture'] : '';
                    if(!empty($arr_data['contract_badges_expiration_date'])){
                        $info['contract_badges_expiration_date'] = convert_date($arr_data['contract_badges_expiration_date']);
                    }
                    $info['contract_badges_picture'] = !empty($arr_data['contract_badges_picture'][0]) ? json_encode(explode(',',$arr_data['contract_badges_picture'][0])) : json_encode([]);
                    if(!empty($arr_data['health_certificate_dateofissue'])){
                        $info['health_certificate_dateofissue'] = convert_date($arr_data['health_certificate_dateofissue']);
                    }
                    $info['vehicle_license_plates_picture'] = !empty($arr_data['vehicle_license_plates_picture'][0]) ? json_encode(explode(',',$arr_data['vehicle_license_plates_picture'][0])) : json_encode([]);
                }
                $info['updated_at'] = mongo_time();
                if(!empty($info)){
                    if($check_info){
                        unset($info['driver_id']);
                        $ok = Driver_Info::where('driver_id', request('item'))->update($info);
                    }else{
                        $info['created_at'] = mongo_time();
                        $ok = Driver_Info::insert($info);
                    }
                }
                if($ok){
                    return response_custom('Cập nhật tài xế thành công!');
                }else{
                    return response_custom('Cập nhật tài xế thất bại!',1);
                }
            }else{
                return response_custom('Không tìm thấy tài xế!',1);
            }
        }else{
            return response_custom('Dữ liệu rỗng!',1);
        }
    }

    function banOrUnbanDriver(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(request()->has('item')){
            if(!request()->has('action')){
                return response_custom('Không tìm thấy hành động!', 1);
            }elseif(!in_array(request('action'), ['lock','unlock'])){
                return response_custom('Thao tác không hợp lệ!', 1);
            }
            $driver = Driver::where('_id', request('item'))->first();
            if($driver){
                $driver = $driver->toArray();
                switch ($driver['is_approve']){
                    case 0:
                        return response_custom('Tài xế chưa được duyệt!', 1);
                        break;
                    case 1:
                        if(request('action') == 'lock'){
                            if(request()->has('reason')){
                                $update = [
                                    'is_show' => 2,
                                    'is_approve' => 2,
                                    'reason' => request('reason'),
                                    'locked_at' => mongo_time()
                                ];
                                $ok = Driver::where('_id', request('item'))->update($update);
                                if($ok){
                                    // Gửi thông báo đến tài khoản bị khóa
                                    $device_token = Driver_Token::where('driver_id', request('item'))->pluck('device_token');
                                    if($device_token){
                                        $target_notic = Config('Api_app').'/firebase/api/messaging';
                                        foreach ($device_token as $item){
                                            $data = [
                                                'token' => $item,
                                                'template' => 'driverLocked',
                                                'arr_replace' => [
                                                    'body' => [
                                                        'created_at' => date('H:i d/m/Y'),
                                                        'reason' => request('reason')
                                                    ]
                                                ],
                                                'push_data' => [
                                                    'type' => 'driverLocked'
                                                ]
                                            ];
                                            Http::post($target_notic, $data)->json();
                                        }
                                        // Log out tài khoản ra mọi thiết bị
                                        $target = Config('Api_app').'/booking/api/logout';
                                        $token = (new Token)->getToken($target, request('item'));
                                        Http::withToken($token)->post($target, ['logout_all' => 1, 'user_id' => request('item')])->json();
                                    }
                                    return response_custom('Khóa tài xế thành công!');
                                }
                            }else{
                                return response_custom('Vui lòng nhập lí do khóa!', 1);
                            }
                        }else{
                            return response_custom('Tài xế chưa bị khóa!', 1);
                        }
                        break;
                    case 2:
                        if(request('action') == 'unlock'){
                            $update = [
                                'is_show' => 1,
                                'is_approve' => 1,
                                'unlocked_at' => mongo_time()
                            ];
                            $ok = Driver::where('_id', request('item'))->update($update);
                            if($ok){
                                return response_custom('Mở khóa tài xế thành công!');
                            }
                        }else{
                            return response_custom('Tài xế đã bị khóa rồi!', 1);
                        }
                        break;
                    case 3:
                        return response_custom('Tài xế đang chờ duyệt lại!', 1);
                        break;
                }
            }else{
                return response_custom('Không tìm thấy tài xế!', 1);
            }
        }else{
            return response_custom('Không tìm thấy tài xế!', 1);
        }
        return response_custom('Có lỗi xảy ra!', 1);
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

    // --------- Duyệt hợp đồng tài xế ---------
    function approveContractDriver(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(request()->has('accept')){
            if(!empty(request('item'))){
                $contract = Driver_Contract::where('_id', request('item'))->first();
                if($contract){
                    $contract = $contract->toArray();
                    if($contract['status'] == 1){
                        switch (request('accept')){
                            case 0:
                                // Từ chối duyệt hợp đồng
                                if(!empty(request('reason'))){
                                    $update = [
                                        'is_sign_contract' => 0,
                                        'status' => -1,
                                        'reason' => request('reason'),
                                        'updated_at' => mongo_time()
                                    ];
                                    $ok = Driver_Contract::where('_id', request('item'))->update($update);
                                    if($ok){
                                        $device_token_driver = Driver_Token::where('driver_id', $contract['driver_id'])->pluck('device_token');
                                        if($device_token_driver){
                                            $target_notic = Config('Api_app').'/firebase/api/messaging';
                                            foreach ($device_token_driver as $item){
                                                $data_notic_driver = [
                                                    'token' => $item,
                                                    'template' => 'rejectContractDriver',
                                                    'arr_replace' => [
                                                        'body' => [
                                                            'item_code' => $contract['item_code'],
                                                            'reason' => request('reason'),
                                                            'rejected_at' => date('H:i d/m/Y')
                                                        ]
                                                    ]
                                                ];
                                                Http::post($target_notic, $data_notic_driver)->json();
                                            }
                                        }
                                        return response_custom('Từ chối duyệt hợp đồng thành công!');
                                    }
                                }else{
                                    return response_custom('Vui lòng nhập lí do từ chối!', 1);
                                }
                                break;
                            case 1:
                                // Duyệt hợp đồng
                                $update = [
                                    'is_sign_contract' => 1,
                                    'status' => 2,
                                    'updated_at' => mongo_time()
                                ];
                                $ok = Driver_Contract::where('_id', request('item'))->update($update);
                                if($ok){
                                    $device_token_driver = Driver_Token::where('driver_id', $contract['driver_id'])->pluck('device_token');
                                    if($device_token_driver){
                                        $target_notic = Config('Api_app').'/firebase/api/messaging';
                                        foreach ($device_token_driver as $item){
                                            $data_notic_driver = [
                                                'token' => $item,
                                                'template' => 'approveContractDriver',
                                                'arr_replace' => [
                                                    'body' => [
                                                        'item_code' => $contract['item_code'],
                                                        'approved_at' => date('H:i d/m/Y')
                                                    ]
                                                ]
                                            ];
                                            Http::post($target_notic, $data_notic_driver)->json();
                                        }
                                    }
                                    return response_custom('Duyệt hợp đồng thành công!');
                                }
                                break;
                        }
                    }elseif($contract['status'] == 2){
                        if(request('accept') == 2){
                            if(!empty(request('reason'))){
                                $update = [
                                    'is_sign_contract' => 0,
                                    'status' => 3,
                                    'reason' => request('reason'),
                                    'updated_at' => mongo_time()
                                ];
                                $ok = Driver_Contract::where('_id', request('item'))->update($update);
                                if($ok){
                                    $device_token_driver = Driver_Token::where('driver_id', $contract['driver_id'])->pluck('device_token');
                                    if($device_token_driver){
                                        $target_notic = Config('Api_app').'/firebase/api/messaging';
                                        foreach ($device_token_driver as $item){
                                            $data_notic_driver = [
                                                'token' => $item,
                                                'template' => 'reapproveContractDriver',
                                                'arr_replace' => [
                                                    'body' => [
                                                        'item_code' => $contract['item_code'],
                                                        'reason' => request('reason'),
                                                        'rejected_at' => date('H:i d/m/Y')
                                                    ]
                                                ]
                                            ];
                                            Http::post($target_notic, $data_notic_driver)->json();
                                        }
                                    }
                                    return response_custom('Yêu cầu duyệt lại hợp đồng thành công!');
                                }
                            }else{
                                return response_custom('Vui lòng nhập lí do yêu cầu duyệt lại hợp đồng!', 1);
                            }
                        }else{
                            return response_custom('Hợp đồng đã được duyệt rồi!', 1);
                        }
                    }elseif($contract['status'] == 3){
                        return response_custom('Hợp đồng đang chờ duyệt lại!', 1);
                    }elseif($contract['status'] == 0){
                        return response_custom('Hợp đồng chưa được kí!', 1);
                    }elseif($contract['status'] == -1){
                        return response_custom('Hợp đồng đã từ chối duyệt!', 1);
                    }
                }
            }
        }else{
            return response_custom('Không tìm thấy hành động!', 1);
        }
        return response_custom('Thao tác thất bại!', 1);
    }
}
