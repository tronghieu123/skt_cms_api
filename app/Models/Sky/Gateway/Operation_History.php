<?php

namespace App\Models\Sky\Gateway;

use http\Env\Request;
use Illuminate\Support\Facades\Hash;
use MongoDB\Laravel\Eloquent\Model;
use App\Http\Token;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Validator;
//use App\Models\Sky\User\UserRequest;

class Operation_History extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_gateway';
    protected $table = 'operation_history';
    protected $casts = [
        'created_at' => 'timestamp',
    ];

    function manager($has_parent, $filter){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = Operation_History::when($filter ?? null, function ($query) use($filter){
                foreach ($filter as $item){
                    eval($item);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        return response_pagination($data);
    }
    public static function scopeFilter($query)
    {
//        $query->when($filters['search'] ?? null, function ($query) use ($filters) {
//            $query->whereRaw('(title like "%' . $filters['title'] . '%")');
//        });

        $query->when(request('type') == 'wait_approve' ?? null, function ($query){
            $query->where('is_show', 0); // Đợi duyệt
        })
        ->when(request('type') == 'approved' ?? null, function ($query){
            $query->where('is_show', 1); // Đã duyệt
        })
        ->when(request('type') == 'banned' ?? null, function ($query){
            $query->where('is_show', 2); // Đã cấm
        });
    }
}
