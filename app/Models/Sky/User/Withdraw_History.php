<?php

namespace App\Models\Sky\User;

use MongoDB\Laravel\Eloquent\Model;
use function Illuminate\Database\enableQueryLog;
use function Illuminate\Database\getQueryLog;

class Withdraw_History extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_payment';
    protected $table = 'withdraw_history';

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function user() {
        return $this->hasOne(User::class, '_id', 'partner_id');
    }

    public function getListWithdrawHistory(){

        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }

        $data = Withdraw_History::where('type', 'user')
                ->when(request('type') == 'pending' ?? null, function ($query){
                    $query->where('is_status', 0); // Đợi duyệt
                })
                ->when(request('type') == 'approved' ?? null, function ($query){
                    $query->where('is_status', 1); // Đã duyệt
                })
                ->when(request('type') == 'refuse' ?? null, function ($query){
                    $query->where('is_status', 2); // từ chối
                })
                ->with('user')
                ->filter()
                ->orderBy('created_at', 'desc')
                ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
                ->toArray();

        $data['other']['counter'] = $this->counter();

        return response_pagination($data);
    }

    function counter() {
        $query = Withdraw_History::filter();
        $data['all'] = $query->where('type', 'user')->count();
        $data['pending'] = $query->where('type', 'user')->where('is_status', 0)->count();
        $data['approved'] = $query->where('type', 'user')->where('is_status', 1)->count();
        $data['refuse'] = $query->where('type', 'user')->where('is_status', 2)->count();
        return $data;
    }

    public static function scopeFilter($query)
    {
        $query->when(!empty(request('keyword')) ?? null, function ($query){
                $keyword = explode_custom(request('keyword'),' ');
                $query->whereHas('user', function($q) use($keyword) {
                    if($keyword){
                        foreach ($keyword as $item){
                            $q->orWhere('full_name', 'LIKE', '%' . $item . '%');
                        }
                    }
                    $q->orWhere('phone', 'LIKE', '%'.request('keyword').'%')
                        ->orWhere('email', 'LIKE', '%'.request('keyword').'%');
                })->orWhere('item_code', 'LIKE', '%' .request('keyword') . '%');
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
}
