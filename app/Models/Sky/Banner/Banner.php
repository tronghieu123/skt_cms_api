<?php

namespace App\Models\Sky\Banner;

use http\Env\Request;
use Illuminate\Support\Facades\Hash;
use League\Flysystem\Config;
use MongoDB\Laravel\Eloquent\Model;
use App\Http\Token;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Validator;
//use App\Models\Sky\User\UserRequest;

class Banner extends Model
{
    public $timestamps = false;
    protected $connection = 'sky';
    protected $table = 'banner';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'date_start' => 'timestamp',
        'date_end' => 'timestamp'
    ];


    function addEditBanner(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('arr_data'))){
            $arr_data = json_decode(request('arr_data'), true);
            $data = [
                'group_name' => $arr_data['group_name'] ?? '',
                'title' => $arr_data['title'] ?? '',
                'link_type' => $arr_data['link_type'] ?? 'site',
                'target' => $arr_data['target'] ?? '_self',
                'link' => $arr_data['link'] ?? '',
                'type' => $arr_data['type'] ?? 'image',
                'content' => ($arr_data['type'] == 'content') ? htmlspecialchars($arr_data['content']) : ($arr_data['content'] ?? ''),
                'date_begin' => !empty($arr_data['date_begin']) ? convert_date_time($arr_data['date_begin']) : '',
                'date_end' => !empty($arr_data['date_end']) ? convert_date_time($arr_data['date_end']) : '',
                'short' => !empty($arr_data['short']) ? htmlspecialchars($arr_data['short']) : '',
                'is_show' => 1,
                'lang' => Config('lang_cur'),
                'show_order' => 0,
                'created_at' => mongo_time(),
                'updated_at' => mongo_time(),
                'admin_id' => Config('admin_id')
            ];
            if(!empty(request('item'))){
                $check = Banner::where('_id', request('item'))->value('_id');
                if($check){
                    unset($data['is_show'], $data['lang'], $data['show_order'], $data['created_at'], $data['admin_id']);
                    $ok = Banner::where('_id', request('item'))->update($data);
                    if($ok){
                        return response_custom('Cập nhật banner thành công!');
                    }
                }else{
                    return response_custom('Không tìm thấy banner cập nhật!', 1);
                }
            }else{
                $ok = Banner::insert($data);
                if($ok){
                    return response_custom('Thêm banner thành công!');
                }
            }
        }else{
            return response_custom('Không tìm thấy dữ liệu!', 1);
        }
        return response_custom('Thao tác thất bại!', 1);
    }
}
