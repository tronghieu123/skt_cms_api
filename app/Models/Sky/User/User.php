<?php

namespace App\Models\Sky\User;

use Illuminate\Support\Facades\Hash;
use MongoDB\Laravel\Eloquent\Model;
use App\Http\Token;
use Illuminate\Support\Facades\Http;

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

    public function list_token(){
        return $this->hasMany(DeviceToken::class, 'user_id', '_id')->select(['device_name','user_id','device_token']);
    }
    public function user_rank(){
        return $this->hasOne(User_Rank::class, '_id', 'rank')->select('title','picture');
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
                                            'template' => 'userLocked',
                                            'arr_replace' => [
                                                'title' => [
                                                    'created_at' => date('H:i d-m-Y')
                                                ],
                                                'body' => [
                                                    'created_at' => date('H:i d/m/Y'),
                                                    'reason' => request('reason')
                                                ]
                                            ],
                                            'push_data' => [
                                                'type' => 'userLocked'
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
            ->with('user_rank')
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
        $data['other']['rank'] = User_Rank::where('is_show', 1)->orderBy('order_level','asc')->pluck('title','_id');
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

    // ------------------- Thay đổi hạng thành viên -------------------
    function changeRankUser(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('item'))){
            $user = User::where('_id', request('item'))->first();
            if($user){
                $user_rank = User_Rank::where('is_show', 1)->get(['point_min','point_max','title'])->keyBy('_id')->toArray();
                $user = $user->toArray();
                if(!empty(request('rank')) && in_array(request('rank'), array_keys($user_rank))){
                    if(!empty($user['rank'])){
                        if($user['rank'] == request('rank')){
                            return response_custom('Thành viên đã là hạng '.$user_rank[$user['rank']]['title'].' rồi!', 1);
                        }else{
                            $update_rank = [
                                'rank' => request('rank'),
                                'wallet_point_total' => $user_rank[request('rank')]['point_min'],
                                'wallet_point_change' => 1
                            ];
                            $ok = User::where('_id', request('item'))->update($update_rank);
                            if($ok){
                                return response_custom('Cập nhật hạng thành công!');
                            }
                        }
                    }else{
                        $update_rank = [
                            'rank' => request('rank'),
                            'wallet_point_total' => $user_rank[request('rank')]['point_min'],
                            'wallet_point_change' => 1
                        ];
                        $ok = User::where('_id', request('item'))->update($update_rank);
                        if($ok){
                            return response_custom('Cập nhật hạng thành công!');
                        }
                    }
                }else{
                    return response_custom('Hạng không hợp lệ!', 1);
                }
            }
        }
        return response_custom('Thao tác không thành công!', 1);
    }
}
