<?php

namespace App\Models\Sky\User;

//use http\Env\Request;
use Illuminate\Support\Facades\Hash;
use MongoDB\Laravel\Eloquent\Model;
use App\Http\Token;
use Illuminate\Support\Facades\Http;
//use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Validator;
//use App\Models\Sky\User\UserRequest;

class User extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_user';
    protected $table = 'user';
    protected $hidden = ['password'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'birthday' => 'timestamp',
        'banned_at' => 'timestamp',
        'unbanned_at' => 'timestamp',
        'approved_at' => 'timestamp',
        'deleted_at' => 'timestamp'
    ];

//    public function parent(){
//        return $this->belongsTo(Admin_Menu::class, 'parent_id');
//    }
    public function list_token()
    {
        return $this->hasMany(DeviceToken::class, 'user_id', '_id')->select(['device_name','user_id','device_token']);
    }

    function addEditUser(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = json_decode(request('data'), true);
        $user_id = !empty(request('item')) ? request('item') : '';
        if(empty($data)){
            return response_custom('Dữ liệu rỗng', 1);
        }else{
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return response_custom('Email không hợp lệ', 1);
            }
            if (!preg_match('/(((\+|)84)|0)(3|5|7|8|9)+([0-9]{8})\b/', $data['phone'])) {
                return response_custom('Số điện thoại không hợp lệ', 1);
            }
            if (!preg_match('/(((\+|)84)|0)(3|5|7|8|9)+([0-9]{8})\b/', $data['username'])) {
                return response_custom('Tên đăng nhập không hợp lệ', 1);
            }
            if(!empty($data['re_password']) && !empty($data['password'])){
                if($data['re_password'] != $data['password']){
                    return response_custom('Mật khẩu nhập lại không khớp', 1);
                }
                $data['password'] = Hash::make($data['password']);
                unset($data['re_password']);
            }else{
                unset($data['re_password'], $data['password']);
            }

            $check = User::where('username', $data['username'])
                ->orWhere('phone', $data['username'])
                ->when(request()->has('item') ?? null, function ($query){
                    $query->where('_id', '!=' , request('item'));
                })
                ->value('_id');
            if($check){
                return response_custom('Tên đăng nhập đã có người sử dụng', 1);
            }
            $check = User::where('username', $data['email'])
                ->orWhere('email', $data['email'])
                ->when(request()->has('item') ?? null, function ($query){
                    $query->where('_id', '!=' , request('item'));
                })
                ->value('_id');
            if($check){
                return response_custom('Email đã có người sử dụng', 1);
            }
            $check = User::where('username', $data['phone'])
                ->orWhere('phone', $data['phone'])
                ->when(request()->has('item') ?? null, function ($query){
                    $query->where('_id', '!=' , request('item'));
                })
                ->value('_id');
            if($check){
                return response_custom('Số điện thoại đã có người sử dụng', 1);
            }
            $data['created_at'] = mongo_time();
            $data['updated_at'] = mongo_time();
            $data['is_show'] = 1;
            $data['birthday'] = !empty($data['birthday']) ? convert_date($data['birthday']) : "";
            $data['province'] = !empty($data['province']) ? convert_column_type($data['province']) : "";
            $data['district'] = !empty($data['district']) ? convert_column_type($data['district']) : "";
            $data['ward'] = !empty($data['ward']) ? convert_column_type($data['ward']) : "";
            if(empty($user_id)){
                $ok = User::insert($data);
                if($ok){
                    return response_custom('Thêm mới thành công!');
                }
            }else{
                $ok = User::where('_id', $user_id)->update($data);
                if($ok){
                    return response_custom('Cập nhật thành công!');
                }
            }
            return response_custom('Thao tác thất bại', 1);
        }
    }

    // Khóa hoặc mở khóa tk
    function banUser(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(request()->has('user')){
            $check = User::where('_id', request('user'))->first(['is_show']);
            if($check){
                $check = $check->toArray();
                if(request('action') == 'delete'){ // Khóa tài khoản
                    if(request()->has('reason') && request('reason') != ''){
                        if($check['is_show'] == 2){
                            return response_custom('Tài khoản đã bị khóa rồi!', 1);
                        }else{
                            $update = [
                                'is_show' => 2,
                                'banned_reason' => request('reason'),
                                'banned_at' => mongo_time()
                            ];
                            $ok = User::where('_id', request('user'))->update($update);
                            if($ok){
                                $device_token = DeviceToken::where('user_id', request('user'))->pluck('device_token');
                                if($device_token){
                                    $target_notic = Config('Api_app').'/firebase/api/messaging';
                                    foreach ($device_token as $item){
                                        $data = [
                                            'token' => $item,
                                            'push_noti' => [
                                                'title' => 'Thông báo khóa tài khoản',
                                                'body' => request('reason')
                                            ]
                                        ];
                                        Http::post($target_notic, $data)->json(); // Gửi thông báo đến tài khoản bị khóa
                                    }
                                    $target = Config('Api_app').'/user/api/logout'; // Log out tài khoản ra mọi thiết bị
                                    $token = (new Token)->getToken($target, request('user'));
                                    Http::withToken($token)->post($target, ['logout_all' => 1, 'user_id' => request('user')])->json();
                                }
                                return response_custom('Khóa tài khoản thành công!');
                            }
                        }
                    }else{
                        return response_custom('Vui lòng nhập lí do khóa tài khoản!', 1);
                    }
                }elseif (request('action') == 'restore'){ // Mở khóa tài khoản
                    if($check['is_show'] == 1){
                        return response_custom('Tài khoản chưa bị khóa!', 1);
                    }else{
                        $update = [
                            'is_show' => 1,
                            'unbanned_at' => mongo_time()
                        ];
                        $ok = User::where('_id', request('user'))->update($update);
                        if($ok){
                            return response_custom('Khôi phục tài khoản thành công!');
                        }
                    }
                }
            }else{
                return response_custom('Không tìm thấy tài khoản!', 1);
            }
        }else{
            return response_custom('Không tìm thấy tài khoản!', 1);
        }
    }

    // Duyệt tài khoản
    function approveUser(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1);
        }
        if(request()->has('user')){
            $check = User::where('_id', request('user'))->first(['is_show']);
            if($check){
                $check = $check->toArray();
                if($check['is_show'] == 1){
                    return response_custom('Tài khoản đã được duyệt!', 1);
                }else{
                    $update = [
                        'is_show' => 1,
                        'approved_at' => mongo_time()
                    ];
                    $ok = User::where('_id', request('user'))->update($update);
                    if($ok){
                        return response_custom('Duyệt tài khoản thành công!');
                    }
                }
            }else{
                return response_custom('Không tìm thấy tài khoản!', 1);
            }
        }else{
            return response_custom('Không tìm thấy tài khoản!', 1);
        }
    }

    // Danh sách user
    function listUser(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = User::with('list_token')
            ->when(request('type') == 'pending' ?? null, function ($query){
                $query->where('is_show', 0)->orWhere('is_show', null); // Đợi duyệt
            })
            ->when(request('type') == 'approved' ?? null, function ($query){
                $query->where('is_show', 1); // Đã duyệt
            })
            ->when(request('type') == 'banned' ?? null, function ($query){
                $query->where('is_show', 2); // Đã cấm
            })
            ->filter()
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        $data['other']['counter'] = $this->tabListUser();
        return response_pagination($data);
    }
    function tabListUser(){
        $data['all'] = User::filter()->count();
        $data['pending'] = User::filter()->where('is_show', 0)->orWhere('is_show', null)->count();
        $data['approved'] = User::filter()->where('is_show', 1)->count();
        $data['banned'] = User::filter()->where('is_show', 2)->count();
        return $data;
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

    // --------- Thêm thông báo user ---------
    function addNotificationUSer(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('arr_data'))){
            $data = json_decode(request('arr_data'),true);
            $data['type_of'] = 'system';
            $data['short'] = !empty($data['short']) ? htmlspecialchars($data['short']) : '';
            $data['content'] = !empty($data['content']) ? htmlspecialchars($data['content']) : '';
            $data['is_show'] = 1;
            $data['show_order'] = 0;
            $data['created_at'] = mongo_time();
            $data['updated_at'] = mongo_time();
            $data['admin_id'] = Config('admin_id');
            if($data['type'] == 'all'){
                $data['user_id'] = [];
                $ok = Notification::insertGetId($data);
                if($ok){
                    $id = mongodb_id($ok);
                    $data_autosend = [
                        'noti_id' => $id,
                        'total_driver' => 0,
                        'total_send' => 0,
                        'is_complete' => 0,
                        'is_show' => 1,
                        'created_at' => mongo_time(),
                        'updated_at' => mongo_time()
                    ];
                    User_Notification_Autosend::insert($data_autosend);
                    return response_custom('Thêm thông báo thành công!');
                }
            }elseif($data['type'] == 'each'){ // Gửi thông báo cho từng tài xế
                if(empty($data['user_id'])){
                    return response_custom('Chưa chọn tài xế gửi!',1);
                }
                $data['user_id'] = array_filter($data['user_id']);
                $id = Notification::insertGetId($data);
                $id = mongodb_id($id);
                // Gửi thông báo đến các thiết bị của từng tài xế
                $target_notic = Config('Api_app').'/firebase/api/messaging';
                foreach ($data['user_id'] as $item){
                    $device_token = DeviceToken::where('user_id', $item)->pluck('device_token');
                    if($device_token){
                        foreach ($device_token as $token){
                            $notic = [
                                'token' => $token,
                                'push_noti' => [
                                    'title' => $data['title'],
                                    'body' => short($data['short'])
                                ],
                                'push_data' => [
                                    '_id' => $id
                                ]
                            ];
                            Http::post($target_notic, $notic)->json();
                        }
                    }
                }
                return response_custom('Thêm thông báo thành công!');
            }
        }else{
            return response_custom('Dữ liệu rỗng',1);
        }
    }

    // ------------------- Tìm kiếm user theo tên hoặc sđt -----------------
    function searchUser(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('keyword'))){
            $driver = User::where('phone', 'LIKE', '%'.request('keyword').'%')
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
