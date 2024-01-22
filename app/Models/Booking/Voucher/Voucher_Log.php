<?php

namespace App\Models\Booking\Voucher;

use MongoDB\Laravel\Eloquent\Model;
use App\Models\Booking\Booking\Booking;


class Voucher_Log extends Model{
    protected $connection = 'sky_voucher';
    protected $table = 'voucher_log';
    protected $with = ['voucher_info'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'date_start' => 'timestamp',
        'date_end' => 'timestamp'
    ];

    function voucher_info (){
        return $this->hasOne(Voucher::class, 'promotion_id',  'promotion_id');
    }

    function booking (){
        return $this->hasOne(Booking::class, '_id',  'type_id');
    }

    function voucherLog(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = Voucher_Log::filter()
            ->where('type', 'booking')
            ->orWhere('type', 'delivery')
            ->with(['booking' => function ($query) {
                $query->with(['user_info' => function ($user){
                    $user->select('phone', 'full_name', 'email', 'picture');
                }]);
            }])
            ->where('is_show', 1)
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        return response_pagination($data);
    }

    public static function scopeFilter($query)
    {
        $filter = !empty(request('arr_filter')) ? json_decode(request('arr_filter'), true) : [];
        $query->when($filter['keyword'] ?? null, function ($query) use ($filter){
                $keyword = explode_custom($filter['keyword'],' ');
                if(!empty($keyword)){
                    $query->orWhereHas('booking', function($q) use($keyword, $filter) {
                        foreach ($keyword as $item){
                            $q->orWhere('full_name', 'LIKE', '%' . $item . '%');
                        }
                        $q->orWhere('phone', 'LIKE', '%'.$filter['keyword'].'%');
                    });
                }
                $query->orWhere('promotion_id', 'LIKE', '%'.$filter['keyword'].'%');
            })
            ->when(!empty($filter['date_start']) ?? null, function ($query) use ($filter){
                $date_start = convert_date_search($filter['date_start']);
                $query->whereDate("created_at", ">=", $date_start);
            })
            ->when(!empty(request('date_end')) ?? null, function ($query) use ($filter){
                $date_end = convert_date_search($filter['date_end']);
                $query->whereDate("created_at", "<=", $date_end);
            });
    }
}
