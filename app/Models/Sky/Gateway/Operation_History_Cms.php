<?php

namespace App\Models\Sky\Gateway;

use MongoDB\Laravel\Eloquent\Model;

class Operation_History_Cms extends Model{
    public $timestamps = false;
    protected $connection = 'sky_gateway';
    protected $table = 'operation_history_cms';
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
}
