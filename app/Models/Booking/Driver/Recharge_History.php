<?php

namespace App\Models\Booking\Driver;

use App\Models\Booking\Driver\Driver;
use App\Models\Sky\Payment\Method_Recharge;
use MongoDB\Laravel\Eloquent\Model;

class Recharge_History extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_payment';
    protected $table = 'recharge_history';

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function methodRecharge() {
        return $this->hasOne(Method_Recharge::class, '_id', 'method');
    }

    public function driver() {
        return $this->hasOne(Driver::class, 'partner_id',  'partner_id');
    }

    public function getListRechargeHistory() {
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }

        $data = Recharge_History::where('type', 'driver')
            ->when(request('type') == 'pending' ?? null, function ($query){
                $query->where('is_status', 0); // Đợi duyệt
            })
            ->when(request('type') == 'approved' ?? null, function ($query){
                $query->where('is_status', 1); // Đã duyệt
            })
            ->when(request('type') == 'refuse' ?? null, function ($query){
                $query->where('is_status', 2); // từ chối
            })
            ->with(['driver' => function ($query) {
                $query->with(['partner' => function ($partner){
                    $partner->select('phone', 'full_name', 'email');
                }])
                    ->with(['info' => function ($info){
                    $info->select('avatar', '_id', 'driver_id');
                }])
                    ->without(['vehicle_type', 'approve'])
                    ->select('partner', 'partner_id');
            }])
            ->with('methodRecharge')
            ->filter()
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();

        // đếm số lượng các tab
        $data['other']['counter'] = $this->counter();

        return response_pagination($data);
    }

    function counter() {
        $data['all'] = Recharge_History::filter()->where('type', 'driver')->count();
        $data['pending'] = Recharge_History::filter()->where('type', 'driver')->where('is_status', 0)->count();
        $data['approved'] = Recharge_History::filter()->where('type', 'driver')->where('is_status', 1)->count();
        $data['refuse'] = Recharge_History::filter()->where('type', 'driver')->where('is_status', 2)->count();
        return $data;
    }

    public static function scopeFilter($query){
        $query->when(!empty(request('keyword')) ?? null, function ($query){
                $keyword = explode_custom(request('keyword'),' ');
                $query->whereHas('driver.partner', function($q) use($keyword) {
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
            });
    }
}
