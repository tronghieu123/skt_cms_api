<?php

namespace App\Models\Sky\Support;

use App\Models\Booking\Driver\Partner;
use App\Models\Sky\User\User;
use MongoDB\Laravel\Eloquent\Model;

class Request extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_user';
    protected $table = 'support_request';

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function user_info() {
        return $this->hasOne(User::class, '_id',  'user_id')->select('full_name','phone','email');
    }

    public function getSupportRequest() {
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }

        $data = Request::filter()
                ->with('user_info')
                ->orderBy('created_at', 'desc')
                ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
                ->toArray();
        return response_pagination($data);
    }

    public static function scopeFilter($query)
    {
        $filter = !empty(request('arr_filter')) ? json_decode(request('arr_filter'), true) : [];
        $query->when(!empty($filter['keyword']) ?? null, function ($query) use($filter){
            $query->whereHas('user_info', function($q) use($filter) {
                $keyword = explode_custom($filter['keyword'], ' ');
                if($keyword){
                    foreach ($keyword as $item){
                        $q->orWhere('full_name', 'LIKE', '%'.$item.'%');
                    }
                }
                $q->orWhere('phone', 'LIKE', '%'.$filter['keyword'].'%')
                    ->orWhere('email', 'LIKE', '%'.$filter['keyword'].'%');
            });
        })
            ->when(!empty($filter['date_start']) ?? null, function ($query) use($filter){
                $date_start = convert_date_search($filter['date_start']);
                $query->whereDate("created_at", ">=", $date_start);
            })
            ->when(!empty($filter['date_end']) ?? null, function ($query) use($filter){
                $date_end = convert_date_search($filter['date_end']);
                $query->whereDate("created_at", ">=", $date_end);
            });
    }
}
