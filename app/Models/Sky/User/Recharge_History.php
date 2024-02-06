<?php

namespace App\Models\Sky\User;

use App\Models\Sky\Partner\History_Wallet_Sky_Status;
use App\Models\Sky\Payment\Method_Recharge;
use MongoDB\Laravel\Eloquent\Model;

class Recharge_History extends Model{
    public $timestamps = false;
    protected $connection = 'sky_payment';
    protected $table = 'recharge_history';

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    public function methodRecharge() {
        return $this->hasOne(Method_Recharge::class, 'name_action', 'method')->select('name_action', 'title');
    }

    public function user() {
        return $this->hasOne(User::class, '_id', 'partner_id')->select('full_name', 'phone', 'email', 'picture');
    }

    public function getListRechargeHistory() {
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $method_recharge = Method_Recharge::where('is_show', 1)
            ->where('lang', Config('lang_cur'))
            ->pluck('title','name_action');
        $data = Recharge_History::with('user')
            ->filter()
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        if(!empty($data['data'])){
            foreach ($data['data'] as $k => $v){
                $data['data'][$k]['method_recharge'] = !empty($method_recharge[$v['method']]) ? $method_recharge[$v['method']] : '';
            }
        }
        $data['other']['status'] = History_Wallet_Sky_Status::where('is_show', 1)->where('lang', Config('lang_cur'))->get(['title','bg_color','text_color','class','value'])->keyBy('value');
        $data['other']['total_recharge'] = Recharge_History::filter()->sum('value');
        // đếm số lượng các tab
//        $data['other']['counter'] = $this->counter();

        return response_pagination($data);
    }

//    function counter() {
//        $data['all'] = Recharge_History::filter()->count();
//        $data['pending'] = Recharge_History::filter()->where('is_status', 0)->count();
//        $data['approved'] = Recharge_History::filter()->where('is_status', 1)->count();
//        $data['reject'] = Recharge_History::filter()->where('is_status', 2)->count();
//        return $data;
//    }

    public static function scopeFilter($query){
        $query
            ->when(!empty(request('keyword')) ?? null, function ($query){
                $keyword = explode_custom(request('keyword'),' ');
                $query->orWhere('item_code', 'LIKE', '%'.request('keyword').'%')
                    ->orWhereHas('user', function($q) use($keyword){
                        if($keyword){
                            foreach ($keyword as $item){
                                $q->orWhere('full_name', 'LIKE', '%'.$item.'%');
                            }
                        }
                        $q->orWhere('phone', 'LIKE', '%'.request('keyword').'%')
                        ->orWhere('email', 'LIKE', '%'.request('keyword').'%');
                    }
                );
            })
            ->when(!empty(request('item')), function ($query){
                $query->where('partner_id', request('item')); // user_id
            })
            ->when(!empty(request('date_start')) ?? null, function ($query){
                $date_start = convert_date_search(request('date_start'));
                $query->whereDate("created_at", ">=", $date_start);
            })
            ->when(!empty(request('date_end')) ?? null, function ($query){
                $date_end = convert_date_search(request('date_end'));
                $query->whereDate("created_at", "<=", $date_end);
            })
            ->where('is_status', 1)
            ->where('is_show', 1)
            ->where('type', 'user');
    }
}
