<?php

namespace App\Models\Booking\Driver;

use App\Models\Sky\Partner\History_Wallet_Sky_Status;
use App\Models\Sky\Partner\History_Wallet_Sky_Type;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class History_Wallet_Sky extends Model{
    public $timestamps = false;
    protected $connection = 'sky_partner';
    protected $table = 'history_wallet_sky';

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function driver() {
        return $this->hasOne(Driver::class, 'partner_id',  'partner_id')->without('approve');
    }

    public function listHistoryWalletSky(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }

        $data = History_Wallet_Sky::filter()
            ->with(['driver' => function ($query) {
                $query->with(['partner' => function ($partner){
                    $partner->select('phone', 'full_name', 'email');
                }])->with(['info' => function ($info){
                    $info->select('avatar', '_id', 'driver_id');
                }])->without(['vehicle_type', 'approve'])
                ->select('partner', 'partner_id');
            }])
            ->where('is_status', '>', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        $data['other']['type'] = History_Wallet_Sky_Type::where('is_show', 1)->get(['title','bg_color','text_color','class','name_action','description'])->keyBy('name_action');
        $data['other']['status'] = History_Wallet_Sky_Status::where('is_show', 1)->get(['title','bg_color','text_color','class','value'])->keyBy('value');
        $data['other']['minus_total'] = History_Wallet_Sky::filter()->where('value_type', -1)->where('is_status', 1)->sum('value');
        $data['other']['add_total'] = History_Wallet_Sky::filter()->where('value_type', 1)->where('is_status', 1)->sum('value');

        $arr_replace_root = ['bank_name','method','created_at','item_code'];
        if(!empty($data['data'])){
            foreach ($data['data'] as $k => $v){
                $arr_replace = $arr_value = [];
                if(!in_array($v['type'], ['admin_add','admin_minus'])){
                    $transaction_info = DB::connection($v['dbname'])->collection($v['dbtable'])->where('_id', $v['dbtableid'])->first();
                    if($transaction_info){
                        $transaction_info['created_at'] = date('H:i d-m-Y', parseTimestamp($transaction_info['created_at']));
                        foreach ($arr_replace_root as $item){
                            $arr_replace[] = '{'.$item.'}';
                            $arr_value[] = $transaction_info[$item] ?? '';
                        }
                        $data['data'][$k]['description'] = (!empty($data['other']['type']) && !empty($data['data'][$k]['type'])) ? str_replace($arr_replace, $arr_value, $data['other']['type'][$data['data'][$k]['type']]['description']) : '';
                    }
                }else{
                    $v['created_at'] = date('H:i d-m-Y', $v['created_at']);
                    foreach ($arr_replace_root as $item){
                        $arr_replace[] = '{'.$item.'}';
                        $arr_value[] = !empty($v[$item]) ? $v[$item] : '';
                    }
                    $data['data'][$k]['description'] = (!empty($data['other']['type']) && !empty($data['data'][$k]['type'])) ? str_replace($arr_replace, $arr_value, $data['other']['type'][$data['data'][$k]['type']]['description']) : '';
                }
            }
        }
        return response_pagination($data);
    }

    public static function scopeFilter($query){
        $query
            ->when(!empty(request('keyword')) ?? null, function ($query){
                $keyword = explode_custom(request('keyword'),' ');
                    $query->whereHas('driver.partner', function($q) use($keyword){
                        if($keyword){
                            foreach ($keyword as $item){
                                $q->orWhere('full_name', 'LIKE', '%' . $item . '%');
                            }
                        }
                        $q->orWhere('phone', 'LIKE', '%'.request('keyword').'%')
                            ->orWhere('email', 'LIKE', '%'.request('keyword').'%');
                    })->orWhere('item_code', 'LIKE', '%'.request('keyword').'%');
                })
            ->when(!empty(request('date_start')) ?? null, function ($query){
                $date_start = convert_date_search(request('date_start'));
                $query->whereDate("created_at", ">=", $date_start);
            })
            ->when(!empty(request('date_end')) ?? null, function ($query){
                $date_end = convert_date_search(request('date_end'));
                $query->whereDate("created_at", "<=", $date_end);
            })
            ->when(!empty(request('item')) ?? null, function ($query){
                $query->where('partner_id', request('item'));
            })
            ->where('is_show', 1);
    }

    // Cộng trừ tiền partner
    function adminAddMinusWalletPartner(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('arr_data'))){
            $arr_data = json_decode(request('arr_data'), true);
            if($arr_data){
                if(!empty($arr_data['driver_id'])){
                    $driver = Driver::where('_id', $arr_data['driver_id'])->without('info','vehicle_type','approve')->first();
                    if($driver){
                        $driver = $driver->toArray();
                        $partner = !empty($driver['partner']) ? $driver['partner'] : [];
                        if(!empty($partner)){
                            if(!empty($arr_data['type']) && in_array($arr_data['type'], ['admin_add','admin_minus'])){
                                if(!empty($arr_data['value']) && is_numeric($arr_data['value'])){
                                    $value = abs($arr_data['value']);
                                    $data = [
                                        "partner_id" => $partner['_id'],
                                        "item_code" => "",
                                        "dbtable" => $this->table,
                                        "dbtableid" => (new \MongoDB\BSON\ObjectID())->jsonSerialize()['$oid'],
                                        "dbname" => $this->connection,
                                        "type" => $arr_data['type'],
                                        "money_pay" => $value,
                                        'content' => !empty($arr_data['content']) ? $arr_data['content'] : ''
                                    ];
                                    $type = ($arr_data['type'] == 'admin_add') ? 'repay' : 'pay';
                                    $data['other']['api_link'] = Config('Api_app').'/partner/api/'.$type;
                                    $data['other']['type'] = $type;
                                    return response_custom('',0, $data);
                                }else{
                                    return response_custom('Vui lòng nhập số điểm cộng!', 1);
                                }
                            }
                        }else{
                            return response_custom('Không tìm thấy partner!', 1);
                        }
                    }else{
                        return response_custom('Không tìm thấy tài xế!', 1);
                    }
                }else{
                    return response_custom('Vui lòng chọn tài xế!', 1);
                }
            }
        }else{
            return response_custom('Không tìm thấy dữ liệu!', 1);
        }
        return response_custom('Thao tác không thành công!', 1);
    }
}
