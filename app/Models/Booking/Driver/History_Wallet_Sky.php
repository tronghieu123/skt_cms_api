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
        $data['other']['minus_total'] = History_Wallet_Sky::filter()->where('value_type', -1)->where('is_status', '>', 0)->sum('value');
        $data['other']['add_total'] = History_Wallet_Sky::filter()->where('value_type', 1)->where('is_status', '>', 0)->sum('value');

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
                $query->whereHas('driver', function($q) use($keyword){
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
}
