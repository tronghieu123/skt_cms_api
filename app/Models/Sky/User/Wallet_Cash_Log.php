<?php

namespace App\Models\Sky\User;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Wallet_Cash_Log extends Model{
    public $timestamps = false;
    protected $connection = 'sky_user';
    protected $table = 'wallet_cash_log';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function user_info(){
        return $this->hasOne(User::class, '_id', 'user_id')->select(['full_name','phone','picture', 'email']);
    }

    // Danh sách user
    function listWalletCashLog(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = Wallet_Cash_Log::filter()
            ->with('user_info')
            ->where('is_show', 1)
            ->where('is_status', 1)
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        $data['other']['type'] = Wallet_Cash_Type::where('is_show', 1)->get(['title','bg_color','text_color','class','name_action','description'])->keyBy('name_action');
        $data['other']['status'] = Wallet_Cash_Status::where('is_show', 1)->get(['title','bg_color','text_color','class','value'])->keyBy('value');
        $data['other']['minus_total'] = Wallet_Cash_Log::filter()->where('value_type', -1)->where('is_status', 1)->sum('value');
        $data['other']['add_total'] = Wallet_Cash_Log::filter()->where('value_type', 1)->where('is_status', 1)->sum('value');

        $arr_replace_root = ['bank_name','method','created_at','item_code'];
        if(!empty($data['data'])){
            foreach ($data['data'] as $k => $v){
                $transaction_info = DB::connection($v['dbname'])->collection($v['dbtable'])->where('_id', $v['dbtableid'])->first();
                if($transaction_info){
                    $transaction_info['created_at'] = date('H:i d-m-Y', parseTimestamp($transaction_info['created_at']));
                    foreach ($arr_replace_root as $item){
                        $arr_replace[] = '{'.$item.'}';
                        $arr_value[] = $transaction_info[$item] ?? '';
                    }
                    $data['data'][$k]['description'] = (!empty($data['other']['type']) && !empty($data['data'][$k]['type'])) ? str_replace($arr_replace, $arr_value, $data['other']['type'][$data['data'][$k]['type']]['description']) : '';
                }
            }
        }
        return response_pagination($data);
    }

    public static function scopeFilter($query){
        $query->when(!empty(request('keyword')) ?? null, function ($query){
            $keyword = explode_custom(request('keyword'),' ');
            $query->whereHas('user_info', function($q) use($keyword){
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
                $query->where('user_id', request('item'));
            });
    }
}
