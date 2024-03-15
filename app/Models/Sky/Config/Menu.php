<?php

namespace App\Models\Sky\Config;

use App\Models\Cms\Gateway\Gateway;
use MongoDB\Laravel\Eloquent\Model;

class Menu extends Model{
    public $timestamps = false;
    protected $connection = 'sky_cms';
    protected $table = 'admin_menu';
    protected $with = ['sub'];

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    protected $dates = ['created_at'];


    public function gateway() {
        return $this->hasOne(Gateway::class, 'menu_id',  '_id');
    }

    public function sub()
    {
        return $this->hasMany(Menu::class, 'parent_id', '_id')
            ->where('is_show', 1)
            ->when(empty(Config('admin_info')['is_ims']), function ($q){
                $q->where('is_ims', 0);
            })
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
                ->when(empty(Config('admin_info')['is_ims']), function ($q){
                    $q->where('is_ims', 0);
                })
                ->filter()
                ->orderBy('show_order','asc')
                ->orderBy('created_at','asc')
                ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))->toArray();
            return response_pagination($data);
        }else{
            $data = Menu::where('type', request('root'))
                ->when(request('sub') == 'manage' ?? null, function ($query){
                    $query->where('is_show', 1)->where('parent_id','');
                })
                ->when(request('sub') == 'manage_trash' ?? null, function ($query){
                    $query->where('is_show', 0)->without('sub');
                })
                ->filter()
                ->orderBy('show_order','asc')
                ->orderBy('created_at','asc')
                ->get(config('fillable'))->toArray();
            return response_custom('',0, $data);
        }
    }

    public static function scopeFilter($query)
    {
        $filters = request()->except('mod', 'act', 'sub', 'id');
        $query->when(!empty($filters['search']), function ($q) use ($filters) {
            $q->whereRaw('(title like "%' . $filters['title'] . '%")');
        });
    }
}
