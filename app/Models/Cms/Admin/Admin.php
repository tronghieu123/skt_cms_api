<?php

namespace App\Models\Cms\Admin;

use Illuminate\Http\Request;

//use Illuminate\Support\Facades\Hash;
//use League\Flysystem\Config;
use MongoDB\Laravel\Eloquent\Model;
//use App\Http\Token;
//use Illuminate\Support\Facades\Http;
//use Illuminate\Support\Facades\DB;

class Admin extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_cms';
    protected $table = 'admin';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    public function info_admin(Request $request) {
        $user = $request->user();
        $user->login_at = parseTimestamp($user->login_at);
        return response_custom($user);
    }


    public function updateAdmin() {
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(request()->has('arr_data') && request()->has('item')){
            $check = Admin::where('_id', request('item'))->value('_id');
            if($check){
                $user = json_decode(request()->arr_data, true);
                $update = [];
                if(!empty($user['username'])){
                    $check_dup = Admin::where('_id', '!=', request('item'))->where('username', $user['username'])->value('_id');
                    if($check_dup){
                        return response_custom('Tên đăng nhập đã tồn tại!', 1);
                    }else{
                        $update['username'] = $user['username'];
                    }
                }else{
                    return response_custom('Tên đăng nhập không được để trống!', 1);
                }
                if(!empty($user['email'])){
                    $check_dup = Admin::where('_id', '!=', request('item'))->where('email', $user['email'])->value('_id');
                    if($check_dup){
                        return response_custom('Email đã tồn tại!', 1);
                    }else{
                        $update['email'] = $user['email'];
                    }
                }else{
                    return response_custom('Email không được để trống!', 1);
                }
                if(!empty($user['full_name'])){
                    $update['full_name'] = $user['full_name'];
                }else{
                    return response_custom('Họ tên không được để trống!', 1);
                }
                if(!empty($user['password'])){
                    $update['password'] = bcrypt($user['password']);
                }
                if(!empty($user['admin_group'])){
                    $update['admin_group'] = $user['admin_group'];
                }
                $update['picture'] = !empty($user['picture']) ? $user['picture'] : '';
                $update['updated_at'] = mongo_time();
                if($update) {
                    $ok = Admin::where('_id', request('item'))->update($update);
                    if($ok) {
                        return response_custom('Cập nhật thành công!');
                    }
                }
            }
        }
        return response_custom('Thao tác thất bại!', 1);
    }

    public function addAdmin() {
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(request()->has('arr_data')){
            $user = json_decode(request('arr_data'), true);
            $arr_in = [];
            if(!empty($user['username'])){
                $check_dup = Admin::where('username', $user['username'])->value('_id');
                if($check_dup){
                    return response_custom('Tên đăng nhập đã tồn tại!', 1);
                }else{
                    $arr_in['username'] = $user['username'];
                }
            }else{
                return response_custom('Tên đăng nhập không được để trống!', 1);
            }
            if(!empty($user['full_name'])){
                $arr_in['full_name'] = $user['full_name'];
            }else{
                return response_custom('Họ tên không được để trống!', 1);
            }
            if(!empty($user['email'])){
                $check_dup = Admin::where('email', $user['email'])->value('_id');
                if($check_dup){
                    return response_custom('Email đã tồn tại!', 1);
                }else{
                    $arr_in['email'] = $user['email'];
                }
            }else{
                return response_custom('Email không được để trống!', 1);
            }
            if(!empty($user['password'])){
                $arr_in['password'] = bcrypt($user['password']);
            }else{
                return response_custom('Mật khẩu không được để trống!', 1);
            }
            if(!empty($user['group_id'])){
                $arr_in['group_id'] = $user['group_id'];
            }else{
                return response_custom('Nhóm admin không được để trống!', 1);
            }
            $arr_in['picture'] = !empty($user['picture']) ? $user['picture'] : '';
            $arr_in['is_show'] = 1;
            $arr_in['lang'] = Config('lang_cur');
            $arr_in['is_ims'] = 0;
            $arr_in['show_order'] = 0;
            $arr_in['created_at'] = mongo_time();
            $arr_in['updated_at'] = mongo_time();
            if($arr_in) {
                $ok = Admin::insert($arr_in);
                if($ok) {
                    return response_custom('Thêm admin thành công!');
                }
            }
        }
        return response_custom('Thao tác thất bại!', 1);
    }
}
