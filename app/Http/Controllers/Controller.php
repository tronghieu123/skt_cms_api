<?php

namespace App\Http\Controllers;

use App\Models\Sky\Gateway\Operation_History_Cms;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $func;
    protected $db;

    public function Handle(){
        Config::set('lang_cur', (!empty(request('lang')) ? request('lang') : 'vi'));
        Config::set('admin_id', '656840d9a7955ceb440160f4');
        Config::set('Api_app', config('app.Api_app'));
        Config::set('Api_link', config('app.Api_link'));
        Config::set('app.timezone', 'Asia/Bangkok');

        if(!request()->has('root') || !request()->has('mod') || !request()->has('act')){
            return response_custom('Không tìm thấy module hoặc action!', 1);
        }
        if(!in_array(request('root'), ['sky','report','system','booking','shopping','fnb','coach','hotel','cleaning','airplaneticket','englishpractice','smarthome'])){
            return response_custom('Không tìm thấy hành động!', 1);
        }
        (new Operation_History_Cms())->log(); // Lưu log gọi api

        $root = (request('mod') == 'config' && in_array(request('act'), ['menu','location'])) ? 'sky' : request('root');
        $check = DB::table('gateway')
            ->where('type', $root)
            ->where('module', request('mod'))
            ->where('action', request('act'))
            ->where('is_show', 1)
            ->first();
        if(!$check){
            return response_custom('Không tìm thấy api!', 1);
        }

        // Các trường cần lấy
        $fillable = !empty(request('fillable')) ? explode_custom(request('fillable')) : [];
        Config::set('fillable', $fillable);
        // -------------------- Dành cho phân trang --------------------
        // Số item phân trang
        $num_list = DB::table('sky_setting')->where('setting_key', 'admin_nlist')->where('is_show', 1)->first(['setting_value']);
        $num_list = !empty($num_list['setting_value']) ? (int)$num_list['setting_value'] : 30;
        Config::set('per_page', $num_list);
        // Trang hiện tại
        $current_page = !empty(request('page')) ? (int)request('page') : 1;
        Config::set('current_page', $current_page);
        // -------------------- Dành cho phân trang --------------------

        $check_ok = 0; // Vào được các api dùng chung hay không
        if(!empty(request('sub'))){
            if(!empty($check['exclude_general_api'])){
                $arr_valid_api = is_array($check['exclude_general_api']) ? $check['exclude_general_api'] : [];
                if(in_array('all', $arr_valid_api)){ // Chặn tất cả
                }elseif(!in_array(request('sub'), $arr_valid_api)){
                    $check_ok = 1;
                }
            }else{
                $check_ok = 1;
            }
        }
        if($check_ok){
            if(!Config('database.connections.'.$check['database'])){
                return response_custom('Không tìm thấy database!', 1);
            }
            if(!Schema::connection($check['database'])->hasTable($check['table'])){
                return response_custom('Không tìm thấy bảng!', 1);
            }
            if(!request()->has('sub')){
                return response_custom('Không tìm thấy module hoặc action!', 1);
            }
            if(!in_array(request('sub'), ['add','edit','update','trash','restore','delete','manage','manage_trash','detail','setting','listSetting'])){
                return response_custom('Không tìm thấy hành động!', 1);
            }
            $this->db = DB::connection($check['database']);
            switch (request('sub')) {
                case 'add':
                case 'edit':
                    return $this->createUpdate($check); // Thêm, cập nhật nội dung
                    break;
                case 'update':
                    return $this->update($check); // Cập nhật nhiều item ngoài trang danh sách
                    break;
                case 'trash':
                case 'restore':
                case 'delete':
                    return $this->trashRestoreDelete($check); // Ẩn, khôi phục, xóa
                    break;
                case 'manage':
                case 'manage_trash':
                    return $this->doList($check); // Danh sách
                    break;
                case 'detail':
                    return $this->doDetail($check); // Lấy nội dung chi tiết item để edit
                    break;
                case 'setting':
                    return $this->doSetting($check);
                    break;
                case 'listSetting':
                    return $this->doListSetting($check);
                    break;
                default:
                    return response_custom('Thao tác không thành công',1);
                    break;
            }
        }else{
            return $this->includeFile();
        }
    }

    function includeFile(){
        $root = ucwords(request('root'));
        if(request('mod') == 'config' && request('act') == 'menu'){
            $root = 'Sky';
        }
        $mod = implode('_', array_map('ucwords', explode('_',request('mod'))));
        $act = implode('_', array_map('ucwords', explode('_', request('act'))));

        $api = request()->segment(count(request()->segments()));

        $func = "\\App\\Models\\$root\\$mod\\$act";
        if(method_exists($func, $api)){
            return app()->call($func . '@' . $api, [request()]);
        }

        return response_custom('Api không khả dụng!', 1);
    }

    public function createUpdate($check){
        $db = $this->db->collection($check['table']);
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1,[],405);
        }
        if(!request()->has('arr_element') || !request()->has('arr_data')){
            return response_custom('Dữ liệu rỗng!', 1);
        }else{
            $arr_element = json_decode(trim(request('arr_element')), true);
            $arr_data = json_decode(trim(request('arr_data')), true);
            $arr_in = get_arr_in($arr_element, $arr_data);
            if(trim(request('sub')) == 'add'){
                $ok = $db->insert($arr_in);
                if($ok){
                    if(!empty($arr_in['friendly_link'])){
                        $id = $db->where('friendly_link', $arr_in['friendly_link'])->first(['_id']);
                        $update_link = array(
                            'table_id' => mongodb_id($id['_id'])
                        );
                        $this->db->collection('friendly_link')->where('friendly_link',$arr_in['friendly_link'])->update($update_link);
                    }
                    return response_custom('Thêm dữ liệu thành công!');
                }
            }elseif(trim(request('sub')) == 'edit'){
                if(empty(trim(request('item')))){
                    return response_custom('Không tìm thấy item cập nhật!',1);
                }else{
                    unset($arr_in['created_at'], $arr_in['admin_id'], $arr_in['show_order']);
                    $ok = $db->where('_id', trim(request('item')))->update($arr_in);
                    if($ok){
                        return response_custom('Cập nhật thành công!');
                    }
                }
            }
        }
        return response_custom('Thao tác thất bại!',1);
    }

    function update($check){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!',1,[],405);
        }
        if(!(request()->has('arr_element')) || !(request()->has('arr_data'))){
            return response_custom('Dữ liệu rỗng!',1);
        }else{
            $arr_element = json_decode(request('arr_element'), true);
            $arr_data = json_decode(request('arr_data'), true);
            $arr_element_tmp = array();
            foreach ($arr_element as $k => $item){
                if(empty($item['more'])){
                    $arr_element_tmp[$k] = $item;
                }else{
                    $arr_element_tmp = array_merge($arr_element_tmp, $item['more']);
                    unset($item['more']);
                    $arr_element_tmp[$k] = $item;
                }
            }
            $ok = 0;
            foreach ($arr_data as $id => $item){
                $arr_in = get_arr_in($arr_element_tmp, $item, 'edit_list');
                $ok = $this->db->collection($check['table'])->where('_id', $id)->update($arr_in);
            }
            if($ok){
                return response_custom('Cập nhật thành công!');
            }
        }
        return response_custom('Thao tác thất bại!',1);
    }

    public function trashRestoreDelete($check){
        $db = $this->db->collection($check['table']);
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!',1,[],405);
        }
        if(!(request()->has('arr_item'))){
            return response_custom('Dữ liệu rỗng!',1);
        }else{
            $arr_item = json_decode(request('arr_item'), true);
            switch (request('sub')){
                case 'trash':
                    $trash = [
                        'is_show' => 0,
                        'updated_at' => mongo_time(),
                    ];
                    $ok = $db->whereIn('_id', $arr_item)->update($trash);
                    break;
                case 'restore':
                    $restore = [
                        'is_show' => 1,
                        'updated_at' => mongo_time()
                    ];
                    $ok = $db->whereIn('_id', $arr_item)->update($restore);
                    break;
                case 'delete':
                    $ok = $db->whereIn('_id', $arr_item)->delete();
                    // Xóa friendly_link
                    $this->db->collection('friendly_link')
                        ->where('module', $check['module'])
                        ->where('action', $check['action'])
                        ->where('table', $check['table'])
                        ->whereIn('table_id', $arr_item)->delete();
                    break;
            }
            if($ok){
                return response_custom('Cập nhật thành công!');
            }else{
                return response_custom('Thao tác thất bại!',1);
            }
        }
    }

    public function doList($check)
    {
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!',1,[],405);
        }
        $root = ucwords(request('root'));
        $mod = ucwords(request('mod'));
        $act = ucwords(request('act'));
        $this->func = '\\App\\Models\\' .$root.'\\'.$mod.'\\'.$act;
        // Danh sách các trường chọn id từ bảng khác sẽ được thay thế bằng title của dữ liệu đó trong bảng đã chọn
        $replace_data = [];
        $arr_convert = [];
        if(request()->has('arr_element')){
            $arr_element = json_decode(request('arr_element'), true);
            foreach ($arr_element as $k => $item){
                if(!empty($item['more'])){
                    foreach ($item['more'] as $k => $item_more){
                        $item_more['field'] = !empty($item_more['field']) ? $item_more['field'] : '_id';
                        if(!empty($item_more['table']) && !empty($item_more['field'])){
                            if(isset($item_more['db'])){
                                $replace_data[$k] = DB::connection($item_more['db'])->collection($item_more['table'])
                                    ->pluck('title', $item_more['field'])->toArray();
                            }else{
                                $replace_data[$k] = $this->db->collection($item_more['table'])
                                    ->pluck('title', $item_more['field'])->toArray();
                            }
                            if(isset($item_more['multiple']) && $item_more['multiple'] == 1){
                                $replace_data[$k]['multiple'] = 1;
                            }
                        }else{
                            if(isset($item_more['multiple']) && $item_more['multiple'] == 1){
                                $replace_data[$k]['multiple_select'] = 1;
                            }
                            if(!empty($item_more['data'])){
                                $replace_data[$k] = $item_more['data'];
                            }
                        }
                    }
                }else{
                    if(isset($item['form_type']) && in_array($item['form_type'], ['datepicker','datetimepicker'])){
                        $arr_convert['date'][] = $k;
                    }
                }
            }
        }
        // Bộ lọc tìm kiếm
        $filter = '';
        if((request('arr_filter'))){
            $arr_filter = json_decode(request('arr_filter'), true);
            $keyword_for = !empty(request('keyword_for')) ? trim(request('keyword_for')) : 'title';
            $filter = scopeFilter($arr_filter, $arr_element, $keyword_for);
        }
        if($check['has_parent'] == 1){
            try {
                $data = (new $this->func)->manager(1, $filter);
                return $data;
            } catch (\Throwable $th) {
                $data = (new $this->func)::when(request('sub') == 'manage', function ($query){
                    $query->where(['is_show' => 1, 'parent_id' => '']);
                })
                    ->when(request('sub') == 'manage_trash', function ($query){
                        $query->where('is_show', 0)->without('sub');
                    })
                    ->when(!empty($filter), function($query) use ($filter){ // Lọc các trường khác theo bộ lọc
                        foreach ($filter as $item){
                            eval($item);
                        }
                    })
                    ->orderBy('show_order', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->paginate(Config('per_page'), Config('fillable'))->toArray();
                $data['data'] = formatData($data['data'], $arr_convert, $replace_data);
                $mess = $data['data']['mess'];
                unset($data['data']['mess']);
                return response_pagination($data, $mess);
            }
        }else{
            try {
                $data = (new $this->func)->manager(1, $filter);
                return $data;
            } catch (\Throwable $th) {
                $data = $this->db->collection($check['table'])
                    ->when(request('sub') == 'manage', function ($query){
                        $query->where('is_show', 1);
                    })
                    ->when(request('sub') == 'manage_trash', function ($query){
                        $query->where('is_show', 0);
                    })
                    ->when(!empty($filter), function($query) use ($filter){ // Lọc các trường khác theo bộ lọc
                        foreach ($filter as $item){
                            eval($item);
                        }
                    })
                    ->orderBy('show_order', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->paginate(Config('per_page'), Config('fillable'))->toArray();
                $data['data'] = formatData($data['data'], $arr_convert, $replace_data);
                $mess = $data['data']['mess'];
                unset($data['data']['mess']);
                return response_pagination($data, $mess);
            }
        }
    }

    public function doDetail($check){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!',1,[],405);
        }
        $data = array(
            'arr_element' => array(),
        );
        $ok = 0;
        if(request()->has('item')){
            $ok = 1;
            $item = $this->db->collection($check['table'])->where('_id', request('item'))->first(config('fillable')); // Chi tiết item
        }
        $arr_convert = [];
        if(request()->has('arr_element')){
            $ok = 1;
            $arr_element = json_decode(request('arr_element'), true);
            foreach ($arr_element as $k => $v){
                $data['arr_element'][$k] = $v;
                if(isset($v['form_type'])){
                    if($v['form_type'] == 'group'){
                        $check['pluck1'] = !empty($arr_element['pluck'][0]) ? $arr_element['pluck'][0] : 'title';
                        $check['pluck2'] = !empty($arr_element['pluck'][1]) ? $arr_element['pluck'][1] : '_id';
                        $check['table_group'] = !empty($v['table']) ? $v['table'] : (!empty($check['table_group']) ? $check['table_group'] : $check['table']);
                        $data['arr_element'][$k]['data'] = $this->doListGroup($check);
                    }elseif ($v['form_type'] == 'select'){
                        if(!empty($v['data'])){
                            $data['arr_element'][$k]['data'] = $v['data'];
                        }elseif (!empty($v['table'])){
                            if(!empty($v['pluck_multi'])){ // Lấy danh sách cho select multi dạng
                                $get = !empty($v['pluck_multi'][0]) ? explode_custom($v['pluck_multi'][0]) : [];
                                $key = !empty($v['pluck_multi'][1]) ? trim($v['pluck_multi'][1]) : '_id';

                                $choosed = !empty($v['only_choosed']) ? (!empty($item[$k]) ? $item[$k] : ['no_data']) : []; // Chỉ lấy danh sách đã chọn
                                if(!empty($v['db'])){
                                    $data_option = DB::connection($v['db'])->collection($v['table'])
                                        ->when(!empty($choosed), function ($query) use ($key, $choosed){
                                            $query->whereIn($key, $choosed);
                                        })
                                        ->get($get)
                                        ->keyBy($key)
                                        ->toArray();
                                }else{
                                    $data_option = $this->db->collection($v['table'])
                                        ->when(!empty($choosed), function ($query) use ($key, $choosed){
                                            $query->whereIn($key, $choosed);
                                        })
                                        ->get($get)
                                        ->keyBy($key)
                                        ->toArray();
                                }
                                $data_tmp = [];
                                if($data_option){
                                    foreach ($data_option as $k_option => $v_option){
                                        $value = [];
                                        foreach ($get as $item_get){
                                            if(!empty($v_option[$item_get])){
                                                $value[] = $v_option[$item_get];
                                            }
                                        }
                                        $data_tmp[$k_option] = implode(' - ', $value);
                                    }
                                }
                                $data['arr_element'][$k]['data'] = $data_tmp;
                            }else{
                                $pluck1 = !empty($v['pluck'][0]) ? trim($v['pluck'][0]) : 'title';
                                $pluck2 = !empty($v['pluck'][1]) ? trim($v['pluck'][1]) : '_id';
                                if(!empty($v['db'])){
                                    $data['arr_element'][$k]['data'] = DB::connection($v['db'])->collection($v['table'])->pluck($pluck1, $pluck2)->toArray();
                                }else{
                                    $data['arr_element'][$k]['data'] = $this->db->collection($v['table'])->pluck($pluck1, $pluck2)->toArray();
                                }
                            }
                        }
                    }elseif ($v['form_type'] == 'select_js' && !empty($v['list_key'])){
                        foreach ($v['list_key'] as $k1 => $location){
                            if(!isset($location['parent'])){
                                // Lấy Danh Sách select khi không phụ thuộc vào cấp cha được chọn
                                if(!empty($location['db'])){ // Lấy theo database khai báo
                                    $data['arr_element'][$k]['list_key'][$k1]['data'] = DB::connection($location['db'])
                                        ->collection($location['table'])
                                        ->where('is_show', 1)
                                        ->orderBy('title', 'asc')
                                        ->pluck($location['pluck'][0], $location['pluck'][1])
                                        ->toArray();
                                }else{ // Lấy theo database đang kết nối hiện tại
                                    $data['arr_element'][$k]['list_key'][$k1]['data'] = $this->db
                                        ->collection($location['table'])
                                        ->where('is_show', 1)
                                        ->orderBy('title', 'asc')
                                        ->pluck($location['pluck'][0], $location['pluck'][1])
                                        ->toArray();
                                }
                            }else{ // Lấy Danh Sách select khi phụ thuộc vào cấp cha được chọn
                                $current = (!empty($item[$location['parent_value']])) ? $item[$location['parent_value']] : '';
                                if($current){
                                    if(!empty($location['db'])){ // Lấy theo database khai báo
                                        $data['arr_element'][$k]['list_key'][$k1]['data'] = DB::connection($location['db'])
                                            ->collection($location['table'])
                                            ->where('is_show', 1)
                                            ->where($location['parent'], $current)
                                            ->orderBy('title', 'asc')
                                            ->pluck($location['pluck'][0], $location['pluck'][1])
                                            ->toArray();
                                    }else{ // Lấy theo database đang kết nối hiện tại
                                        $data['arr_element'][$k]['list_key'][$k1]['data'] = $this->db
                                            ->collection($location['table'])
                                            ->where('is_show', 1)
                                            ->where($location['parent'], $current)
                                            ->orderBy('title', 'asc')
                                            ->pluck($location['pluck'][0], $location['pluck'][1])
                                            ->toArray();
                                    }
                                }else{ // Khi cấp cha rỗng
                                    $data['arr_element'][$k]['list_key'][$k1]['data'] = [];
                                }
                            }
                        }
                    }elseif ($v['form_type'] == 'arr_custom' && !empty($v['list_key'])){
                        foreach ($v['list_key'] as $k_custom => $v_custom){
                            if($v_custom['form_type'] == 'select' && !empty($v_custom['table'])){
                                $pluck1 = !empty($v_custom['pluck'][0]) ? trim($v_custom['pluck'][0]) : 'title';
                                $pluck2 = !empty($v_custom['pluck'][1]) ? trim($v_custom['pluck'][1]) : '_id';
                                if(!empty($v_custom['db'])){
                                    $data['arr_element'][$k]['list_key'][$k_custom]['data'] = DB::connection($v_custom['db'])->collection($v_custom['table'])->pluck($pluck1, $pluck2)->toArray();
                                }else{
                                    $data['arr_element'][$k]['list_key'][$k_custom]['data'] = $this->db->collection($v_custom['table'])->pluck($pluck1, $pluck2)->toArray();
                                }
                            }
                        }
                    }
                    // Mảng danh sách biến đổi kiểu dữ liệu
                    switch ($v['form_type']){
//                        case 'select':
//                            if(isset($v['multiple']) && $v['multiple'] == true){
//                                $arr_convert['comma'][] = $k; // Đổi thành mảng từ kiểu lưu phẩy phẩy
//                            }
//                            break;
                        case 'arr_custom':
                        case 'arr_picture':
                            $arr_convert['json'][] = $k; // Đổi thành mảng từ json trong db
                            break;
                        case 'datepicker':
                        case 'datetimepicker':
                            $arr_convert['date'][] = $k; // Đổi thành timestamp từ mongotime trong db
                            break;
                    }
                }
            }
        }
        if(!empty($item)){
            $item = formatData(array($item), $arr_convert); // Định dạng lại dữ liệu
            $data['item_detail'] = $item[0];
        }

        if($ok == 1){
            $mess = !empty($item['mess']) ? $item['mess'] : '';
            return response_custom($mess, 0, $data);
        }else{
            return response_custom('Không tìm thấy dữ liệu',1);
        }
    }

    function doSetting($check){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!',1,[],405);
        }
        if(!empty(request('arr_data'))){
            if(!empty(request('arr_element'))){
                $arr_element = json_decode(request('arr_element'), true);
                $arr_data = json_decode(request('arr_data'),true);
                $arr_data = get_arr_in($arr_element, $arr_data);
                unset($arr_data['admin_id']);
                foreach ($arr_data as $k => $v){
                    $data = [
                        'setting_key' => $k,
                        'setting_value' => $v,
                        'lang' => Config('lang_cur'),
                        'is_show' => 1,
                        'show_order' => 0,
                        'created_at' => mongo_time(),
                        'updated_at' => mongo_time(),
                        'admin_id' => Config('admin_id')
                    ];
                    $check_exist = $this->db->collection($check['table'])->where('setting_key', $k)
                        ->where('is_show', 1)
                        ->where('lang', Config('lang_cur'))
                        ->first(['_id']);
                    if($check_exist){
                        // Cập nhật
                        $id = mongodb_id($check_exist['_id']);
                        unset($data['setting_key'], $data['created_at'], $data['show_order'], $data['is_show']);
                        $this->db->collection($check['table'])->where('_id', $id)->update($data);
                    }else{
                        // Thêm mới
                        $this->db->collection($check['table'])->insert($data);
                    }
                }
                return response_custom('Cập nhật thành công!');
            }else{
                return response_custom('Dữ liệu truyền thiếu!',1);
            }
        }else{
            return response_custom('Dữ liệu rỗng!',1);
        }
    }

    function dolistSetting($check){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!',1,[],405);
        }
        $data = $this->db->collection($check['table'])
            ->where('is_show', 1)
            ->where('lang', Config('lang_cur'))
            ->pluck('setting_value','setting_key')
            ->toArray();
        $arr_convert = [];
        if(!empty(request('arr_element'))){
            $arr_element = json_decode(request('arr_element'),true);
            foreach ($arr_element as $k => $v){
                if(!empty($v['form_type'])){
                    switch ($v['form_type']){
//                        case 'select':
//                            if(isset($v['multiple']) && $v['multiple'] == true){
//                                $arr_convert['comma'][] = $k; // Đổi thành mảng từ kiểu lưu phẩy phẩy
//                            }
//                            break;
                        case 'arr_custom':
                        case 'arr_picture':
                            $arr_convert['json'][] = $k; // Đổi thành mảng từ json trong db
                            break;
                        case 'datepicker':
                        case 'datetimepicker':
                            $arr_convert['date'][] = $k; // Đổi thành timestamp từ mongotime trong db
                            break;
                    }
                }
            }
        }
        if(!empty(request('filter'))){
            $filter = explode_custom(request('filter'));
            foreach ($data as $k => $v){
                if(!in_array($k, $filter)){
                    unset($data[$k]);
                }
            }
        }
        $data = formatData([$data], $arr_convert);
        return response_custom('',0, $data[0]);
    }

    function doListGroup($check, $data = array()){
        $db = DB::connection($check['database']);
        if($data){
            foreach ($data as $k => $v){
                $sub = $db->collection($check['table_group'])
                    ->when((request('mod') == 'config' && request('act') == 'menu') ?? null, function ($query){
                        $query->where('type', request('root'));
                    })
                    ->where('parent_id', $k)
                    ->get([$check['pluck1']])
                    ->keyBy($check['pluck2'])
                    ->toArray();
                if($sub){
                    $data[$k]['sub'] = $this->doListGroup($check, $sub);
                }
            }
        }else{
            $sub = $db->collection($check['table_group'])
                ->when((request('mod') == 'config' && request('act') == 'menu') ?? null, function ($query){
                    $query->where('type', request('root'));
                })
                ->where('parent_id', '')
                ->get([$check['pluck1']])
                ->keyBy($check['pluck2'])
                ->toArray();
            if($sub){
                $data = $this->doListGroup($check, $sub);
            }
        }
        return $data;
    }
}
