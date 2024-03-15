<?php

namespace App\Models\Cms\Admin;

use App\Models\Sky\Config\Menu;
use App\Models\Cms\Gateway\Gateway;
use Illuminate\Support\Facades\Config;
use MongoDB\Laravel\Eloquent\Model;
use function Brick\Math\remainder;

class Admin_Group extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_cms';
    protected $table = 'admin_group';
    protected $root = ['sky' => 'Sky',
                        'report' => 'Thống kê báo cáo',
                        'system' => 'Cấu hình chung',
                        'booking' => 'Đặt xe',
                        'shopping' => 'Mua sắm',
                        'fnb' => 'Ăn uống',
                        'coach' => 'Xe khách',
                        'hotel' => 'Khách sạn',
                        'cleaning' => 'Dọn vệ sinh',
                        'airplaneticket' => 'Vé máy bay',
                        'englishpractice' => 'Luyện thi tiếng anh',
                        'smarthome' => 'Nhà thông minh'
                    ];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    function detailAdminGroup(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $fillable = ['_id','title','parent_id','is_ims'];
        Config::set('fillable', $fillable);

        $data = [];
        if(!empty(request('item'))){
            $data = Admin_Group::where('_id', request('item'))->first();
            if($data){
                $data = $data->toArray();
            }
        }

        $menu = [];
        foreach ($this->root as $k => $item){
            $menu[$k] = [
                'title' => $item,
                'data' => Menu::where('type', $k)->where('is_ims', 0)->where('parent_id','')->get($fillable)->toArray()
            ];
        }
        if($menu){
            foreach ($menu as $k => $v){
                if(!empty($v['data'])){
                    foreach ($v['data'] as $k1 => $v1){
                        if(!empty($v1['sub'])){
                            foreach ($v1['sub'] as $k2 => $v2){
                                if(!empty($v2['sub'])){
                                    foreach ($v2['sub'] as $k3 => $v3){
                                        $permission = [];
                                        $gateway = Gateway::where('menu_id', $v3['_id'])->first(['permission','_id']);
                                        if($gateway){
                                            $gateway = $gateway->toArray();
                                            $menu[$k]['data'][$k1]['sub'][$k2]['sub'][$k3]['gateway_id'] = $gateway['_id'];
                                            if(!empty($gateway['permission'])){
                                                foreach ($gateway['permission'] as $vp){
                                                    $permission[$vp['api']] = $vp['title'];
                                                }
                                            }
                                        }
                                        $menu[$k]['data'][$k1]['sub'][$k2]['sub'][$k3]['permission'] = $permission;
                                        $menu[$k]['data'][$k1]['sub'][$k2]['sub'][$k3]['permission_selected'] = !empty($data['data'][$v3['_id']]['api_access']) ? $data['data'][$v3['_id']]['api_access'] : [];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        unset($data['data']);
        $data['menu'] = $menu;
        return response_custom('',0, $data);
    }

    function addEditAdminGroup(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }

        if(!empty(request('arr_data'))){
            $data = json_decode(request('arr_data'),true);
            foreach ($data['data'] as $k => $v){
                if(empty($v['api_access']) || !is_array($v['api_access'])){
                    $data['data'][$k]['api_access'] = [];
                }
            }
            $data['is_show'] = 1;
            $data['created_at'] = mongo_time();
            $data['updated_at'] = mongo_time();
            $data['admin_id'] = Config('admin_id');
            if(empty(request('item'))){
                $ok = Admin_Group::insert($data);
                if($ok){
                    return response_custom('Thêm mới thành công!');
                }
            }else{
                $check = Admin_Group::where('_id', request('item'))->value('_id');
                if($check){
                    $ok = Admin_Group::where('_id', request('item'))->update($data);
                    if($ok){
                        return response_custom('Cập nhật thành công!');
                    }
                }else{
                    return response_custom('Không tìm thấy nhóm!', 1);
                }
            }
        }
        return response_custom('Thao tác thất bại!', 1);
    }
}
