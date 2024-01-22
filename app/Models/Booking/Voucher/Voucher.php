<?php

namespace App\Models\Booking\Voucher;

use App\Models\Booking\Booking\Booking_Method_Payment;
use App\Models\Sky\User\User_Rank;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\Booking\Driver\Vehicle;
use App\Models\Sky\User\User;

class Voucher extends Model{
    protected $connection = 'sky_voucher';
    protected $table = 'voucher';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'date_start' => 'timestamp',
        'date_end' => 'timestamp'
    ];

    function listVoucher(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }

        $data = Voucher::when((request('type') == 'not_used_yet') ?? null, function ($query){
                $query->where('num_use', 0); // Chưa sử dụng
            })
            ->when((request('type') == 'used') ?? null, function ($query){
                $query->where('num_use', '>', 0)->whereRaw(['$expr' => ['$lt' => ['$num_use', '$max_use']]]); // đang sử dụng
            })
            ->when((request('type') == 'out_of_use') ?? null, function ($query){
                $query->whereRaw(['$expr' => ['$gte' => ['$num_use', '$max_use']]]); // Hết lượt sử dụng
            })
            ->when((request('type') == 'expired') ?? null, function ($query){
                $query->whereDate('date_end', '<', date('Y-m-d H:i:s')); // Hết hạn
            })
            ->filter()
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();

        $data['other']['counter'] = $this->counter();

        return response_pagination($data);
    }

    function counter() {
        $data['all'] = Voucher::filter()->count();
        $data['not_used_yet'] = Voucher::filter()->where('num_use', 0)->count();
        $data['used'] = Voucher::filter()->where('num_use', '>', 0)->whereRaw(['$expr' => ['$lt' => ['$num_use', '$max_use']]])->count();
        $data['out_of_use'] = Voucher::filter()->whereRaw(['$expr' => ['$gte' => ['$num_use', '$max_use']]])->count();
        $data['expired'] = Voucher::filter()->whereDate('date_end', '<', date('Y-m-d H:i:s'))->count();
        return $data;
    }


    public static function scopeFilter($query)
    {
        $query->when(!empty(request('keyword')) ?? null, function ($query){
                $keyword = explode_custom(request('keyword'),' ');
                if(!empty($keyword)){
                    foreach ($keyword as $item){
                        $query->orWhere('title', 'LIKE', '%' . $item . '%');
                    }
                }
                $query->orWhere('promotion_id', 'LIKE', '%' . request('keyword') . '%');
            })
            ->when(!empty(request('date_start')) ?? null, function ($query){
                $date_start = convert_date_search(request('date_start'));
                $query->whereDate("created_at", ">=", $date_start);
            })
            ->when(!empty(request('date_end')) ?? null, function ($query){
                $date_end = convert_date_search(request('date_end'));
                $query->whereDate("created_at", "<=", $date_end);
            })->when((request('sub') == 'manage_trash') ?? null, function ($query){
                $query->where('is_show', 0);
            })
            ->when((request('sub') == 'manage') ?? null, function ($query){
                $query->where('is_show', 1);
            });
    }


    function loadDelivery(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('apply_for'))){
            $data = Vehicle::where('type', request('apply_for'))->pluck('title','_id')->toArray();
            return response_custom('',0, $data);
        }else{
            return response_custom('Không tìm thấy loại áp dụng!', 1);
        }
    }

    function addEditVoucher(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $db_info = [
            'database' => 'sky_voucher',
            'table' => 'voucher',
            'field' => 'promotion_id'
        ];

        if(!empty(request('arr_data'))){
            $arr_data = json_decode(request('arr_data'), true);
            if(empty(request('item'))){ // Thêm mới voucher
                if(!empty($arr_data['promotion_id'])){
                    if (!preg_match('/([A-Z0-9])\b/', $arr_data['promotion_id']) || strlen(trim($arr_data['promotion_id'])) < 5) {
                        return response_custom('Mã khuyến mãi không hợp lệ!', 1);
                    }
                    $check = Voucher::where('promotion_id', $arr_data['promotion_id'])->value('_id');
                    if($check){
                        return response_custom('Mã khuyến mãi đã tồn tại!', 1);
                    }
                    if(isset($arr_data['total_voucher']) && $arr_data['total_voucher'] > 1){
                        return response_custom('Mã khuyến mãi tự động tạo khi Số lượng voucher lớn hơn 1. Vui lòng không nhập mã khuyến mãi!', 1);
                    }
                }
                // Thời gian bắt đầu ko được trước ngày tạo, và kết thúc ko được trước bắt đầu
                $date_start = time();
                if(!empty($arr_data['date_start'])){
                    $arr_date = explode(' ', $arr_data['date_start']);
                    $date = explode('-', $arr_date[0]);
                    $date_start = strtotime($date[2].'-'.$date[1].'-'.$date[0].' '.$arr_date[1]);
                    if($date_start < time()){
                        return response_custom('Thời gian bắt đầu không hợp lệ!', 1);
                    }
                }
                if(!empty($arr_data['date_end'])){
                    $arr_date = explode(' ', $arr_data['date_end']);
                    $date = explode('-', $arr_date[0]);
                    $date_end = strtotime($date[2].'-'.$date[1].'-'.$date[0].' '.$arr_date[1]);
                    if($date_end <= $date_start){
                        return response_custom('Thời gian kết thúc không hợp lệ!', 1);
                    }
                }
            }else{
                $promotion = Voucher::where('_id', request('item'))->first(); // Mã item đang chỉnh sửa
                if($promotion){
                    $promotion = $promotion->toArray();
                    if($arr_data['promotion_id'] != $promotion['promotion_id']){
                        return response_custom('Không được thay đổi Mã khuyến mãi!', 1);
                    }
                    if(isset($arr_data['total_voucher']) && $arr_data['total_voucher'] > 1){
                        return response_custom('Số lượng voucher không được lớn hơn 1!', 1);
                    }
                }else{
                    return response_custom('Không tìm thấy dữ liệu!', 1);
                }
                // Kiểm tra thời gian bắt đầu không được trước thời gian tạo mã trước đó
                $date_start_current = $promotion['created_at'];
                $date_start = time();
                if(!empty($arr_data['date_start'])){
                    $arr_date = explode(' ', $arr_data['date_start']);
                    $date = explode('-', $arr_date[0]);
                    $date_start = strtotime($date[2].'-'.$date[1].'-'.$date[0].' '.$arr_date[1]);
                    if($date_start < $date_start_current){
                        return response_custom('Thời gian bắt đầu không được thay đổi nhỏ hơn ban đầu!', 1);
                    }
                }
                if(!empty($arr_data['date_end'])){
                    $arr_date = explode(' ', $arr_data['date_end']);
                    $date = explode('-', $arr_date[0]);
                    $date_end = strtotime($date[2].'-'.$date[1].'-'.$date[0].' '.$arr_date[1]);
                    if($date_end <= $date_start){
                        return response_custom('Thời gian kết thúc không hợp lệ!', 1);
                    }
                }
            }
            if(!empty($arr_data['apply_for'])){
                if($arr_data['apply_for'] != 'all' && empty($arr_data['shipping_type'])){
                    return response_custom('Chưa chọn hình thức vận chuyển!', 1);
                }
            }else{
                return response_custom('Chưa chọn lĩnh vực áp dụng!', 1);
            }
            if(!empty($arr_data['apply_user'])){
                if($arr_data['apply_user'] == 'fixed_user' && empty($arr_data['list_user'])){
                    return response_custom('Chưa chọn danh sách thành viên!', 1);
                }
            }else{
                return response_custom('Chưa chọn thành viên áp dụng!', 1);
            }
            if(!empty($arr_data['title'])){
                $data['title'] = $arr_data['title'];
            }else{
                return response_custom('Vui lòng nhập tiêu đề!', 1);
            }
            if(empty($arr_data['type_promotion'])){
                return response_custom('Chưa chọn kiểu giảm!', 1);
            }
            if(isset($arr_data['value_type'])){
                if($arr_data['value_type'] == 1){ // Giảm theo %
                    if($arr_data['value'] > 100){
                        return response_custom('Mức giảm không hợp lệ!', 1);
                    }
                }
            }else{
                return response_custom('Chưa chọn loại giảm!', 1);
            }
            if(!empty($arr_data['value'])){
                if($arr_data['value'] < 0){
                    return response_custom('Mức giảm không hợp lệ!', 1);
                }
            }else{
                return response_custom('Chưa nhập Mức giảm!', 1);
            }
            if(isset($arr_data['value_max']) && $arr_data['value_max'] < 0){
                return response_custom('Mức giảm tối đa không hợp lệ!', 1);
            }
            if(isset($arr_data['total_voucher']) && $arr_data['total_voucher'] < 0){
                return response_custom('Số lượng voucher không hợp lệ!', 1);
            }

            $data['apply_for'] = $arr_data['apply_for'];
            $data['shipping_type'] = !empty($arr_data['shipping_type']) ? array_values(array_unique(array_filter($arr_data['shipping_type']))) : [];
            $data['apply_user'] = $arr_data['apply_user'];
            $data['banner'] = $arr_data['banner'] ?? '';
            $data['method_type'] = !empty($arr_data['method_type']) ? array_values(array_unique(array_filter($arr_data['method_type']))) : [];
            $data['list_user'] = !empty($arr_data['list_user']) ? array_values(array_unique(array_filter($arr_data['list_user']))) : [];
            $data['type_promotion'] = $arr_data['type_promotion'];
            $data['value_max'] = (float)$arr_data['value_max'] ?? 0;
            $data['value_type'] = (int)$arr_data['value_type'];
            $data['value'] = (int)$arr_data['value'] ?? 0;
            $data['total_min'] = (float)$arr_data['total_min'] ?? 0;
            $data['max_use'] = (int)$arr_data['max_use'] ?? 0;
            $data['num_user_use'] = (int)$arr_data['num_user_use'] ?? 0;
            $data['date_start'] = convert_date_time($arr_data['date_start']) ?? convert_date_time(date('d-m-Y H:i'));
            $data['date_end'] = convert_date_time($arr_data['date_end']) ?? convert_date_time(date('d-m-Y H:i', strtotime('+1 day')));
            $data['short'] = htmlspecialchars($arr_data['short']) ?? '';
            $data['description'] = htmlspecialchars($arr_data['description']) ?? '';
            $data['create_type'] = $arr_data['create_type'] ?? '';
            $data['picture'] = $arr_data['picture'] ?? '';
            $data['is_exchange_points'] = (int)$arr_data['is_exchange_points'] ?? 0;
            $data['exchange_points'] = (int)$arr_data['exchange_points'] ?? 0;
            $data['user_rank'] = $arr_data['user_rank'] ?? '';
            $data['is_show'] = 1;
            $data['lang'] = 'vi';
            $data['created_at'] = mongo_time();
            $data['updated_at'] = mongo_time();
            $data['admin_id'] = Config('admin_id');
            if(empty(request('item'))) { // Thêm mới voucher
                if (isset($arr_data['total_voucher']) && $arr_data['total_voucher'] > 1) {
                    $arr_voucher = [];
                    for ($i = 1; $i <= $arr_data['total_voucher']; $i++) {
                        $data['promotion_id'] = random_str_db($db_info, 9, 'un');
                        $arr_voucher[] = $data;
                    }
                    $vouchers_chunk = array_chunk($arr_voucher, 500);
                    foreach ($vouchers_chunk as $val)
                    {
                        set_time_limit(0);
                        $ok = Voucher::insert($val);
                    }

                    if($ok){
                        return response_custom('Thêm mã khuyến mãi thành công!');
                    }
                } else {
                    if (!empty($arr_data['promotion_id'])) {
                        $data['promotion_id'] = $arr_data['promotion_id'];
                    } else {
                        $data['promotion_id'] = random_str_db($db_info, 9, 'un');
                    }
                    $ok = Voucher::insert($data);
                    if ($ok) {
                        return response_custom('Thêm mã khuyến mãi thành công!');
                    }
                }
            }else{ // Cập nhật voucher
                unset($data['created_at']);
                $ok = Voucher::where('_id', request('item'))->update($data);
                if($ok){
                    return response_custom('Cập nhật mã khuyến mãi thành công!');
                }
            }
        }else{
            return response_custom('Dữ liệu rỗng!', 1);
        }
        return response_custom('Thao tác thất bại. Vui lòng thử lại!', 1);
    }

    function detailVoucher(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }

        if(!empty(request('item'))){
            $data = Voucher::where('_id', request('item'))->first();
            if($data){
                $data = $data->toArray();
                if(!empty($data['apply_for'])){ // Lấy danh sách vận chuyển đã chọn
                    $data['shipping_type_selected'] = Vehicle::where('type', $data['apply_for'])->pluck('title', '_id')->toArray();
                    $data['shipping_type_selected'] = array_merge(['all' => 'Tất cả'], $data['shipping_type_selected']);
                }
                if(!empty($data['list_user'])){ // Lấy danh sách user đã chọn
                    $data['user_selected'] = [];
                    $user_selected = User::whereIn('_id', $data['list_user'])
                        ->get(['phone','full_name'])
                        ->keyBy('_id');
                    if($user_selected){
                        $get = ['phone','full_name'];
                        foreach ($user_selected as $k => $v){
                            $value = [];
                            foreach ($get as $item){
                                if(!empty($v[$item])){
                                    $value[] = $v[$item];
                                }
                            }
                            $data['user_selected'][$k] = implode(' - ', $value);
                        }
                    }
                }
                $data['user_rank_selected'] = User_Rank::where('is_show',1)->pluck('title','_id');
                $data['method_type_selected'] = Booking_Method_Payment::where('is_show', 1)->pluck('title', '_id');
                return response_custom('',0, $data);
            }else{
                return response_custom('Không tìm thấy dữ liệu', 1);
            }
        }else{
            $data = [];
            $data['user_rank'] = User_Rank::where('is_show',1)->pluck('title','_id');
            $data['method_type_selected'] = Booking_Method_Payment::where('is_show', 1)->pluck('title', '_id');
            return response_custom('',0,$data);
        }
    }
}
