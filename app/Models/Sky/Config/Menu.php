<?php

namespace App\Models\Sky\Config;

use MongoDB\Laravel\Eloquent\Model;

class Menu extends Model
{
    public $timestamps = false;

    protected $connection = 'sky_cms';

    protected $table = 'admin_menu';

    protected $with = ['sub'];

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    protected $dates = ['created_at'];

//    public function parent(){
//        return $this->belongsTo(Admin_Menu::class, 'parent_id');
//    }

    public function sub()
    {
        return $this->hasMany(Menu::class, 'parent_id', '_id')
            ->where('is_show', 1)
            ->orderBy('show_order', 'asc')
            ->orderBy('created_at', 'asc')
            ->select(config('fillable'))
            ->with(!empty(config('fillable')) ? 'sub:'.implode(',', config('fillable')) : 'sub');
    }

    function menuAdmin($paginate = 0) {
        $paginate = (!empty(request('paginate')) && request('paginate') == 1) ? request('paginate') : $paginate;
        if($paginate == 1){
            $data = Menu::where('type', request('root'))
                ->when(request('sub') == 'manage' ?? null, function ($query){
                    $query->where('is_show', 1)->where('parent_id','');
                })
                ->when(request('sub') == 'manage_trash' ?? null, function ($query){
                    $query->where('is_show', 0)->without('sub');
                })
                ->orderBy('show_order','asc')
                ->orderBy('created_at','asc')
                ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))->toArray();
            //$data['data'] = formatData($data['data']);
            return response()->json([
                    'error' => 0,
                    'code' => 200,
                    'message' => '',
                    'data' => $data['data'],
                    'from' => $data['from'],
                    'to' => $data['to'],
                    'total' => $data['total'],
                    'current_page' => $data['current_page'],
                    'links' => $data['links']
                ],200);
        }else{
            $data = Menu::where('type', request('root'))
                ->when(request('sub') == 'manage' ?? null, function ($query){
                    $query->where('is_show', 1)->where('parent_id','');
                })
                ->when(request('sub') == 'manage_trash' ?? null, function ($query){
                    $query->where('is_show', 0)->without('sub');
                })
                ->orderBy('show_order','asc')
                ->orderBy('created_at','asc')
                ->get(config('fillable'))->toArray();
            //$data = formatData($data);
            return response()->json([
                    'error' => 0,
                    'code' => 200,
                    'message' => '',
                    'data' => $data
                ],200);
        }
    }

    public static function scopeFilter($query)
    {
        $filters = request()->except('mod', 'act', 'sub', 'id');

        $query->when($filters['search'] ?? null, function ($query) use ($filters) {
            $query->whereRaw('(title like "%' . $filters['title'] . '%")');
        });
    }
}
