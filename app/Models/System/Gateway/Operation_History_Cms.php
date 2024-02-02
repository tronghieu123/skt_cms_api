<?php

namespace App\Models\System\Gateway;

use MongoDB\Laravel\Eloquent\Model;

class Operation_History_Cms extends Model{
    public $timestamps = false;
    protected $connection = 'sky_gateway';
    protected $table = 'operation_history_cms';
    protected $casts = [
        'created_at' => 'timestamp'
    ];

    function manager($has_parent, $filter){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $data = Operation_History_Cms::when($filter ?? null, function ($query) use($filter){
                foreach ($filter as $item){
                    eval($item);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        return response_pagination($data);
    }

    public function log(){
        Operation_History_Cms::where('date_create', '<=', (time() - 86400))->delete();
        if (request()->method() != 'GET') {
            $bearer = request()->server('HTTP_AUTHORIZATION');
            if($bearer){
                $bearer = str_replace('Bearer ', '', $bearer);
            }
            $post = request()->post();
            unset($post['root'], $post['mod'], $post['act'], $post['sub']);
            $data = [
                'method' => request()->method(),
                'root' => request('root'),
                'mod' => request('mod'),
                'act' => request('act'),
                'sub' => request('sub') ?? '',
//                'id_login' => auth()->id(),
                'ip' => request()->ip(),
                'url' => request()->path(),
                'bearer' => request()->bearerToken() ?? $bearer,
                'data_post' => json_encode($post),
                'date_create' => time(),
                'created_at' => mongo_time(),
            ];
            Operation_History_Cms::insert($data);
        }
    }
}
