<?php

namespace App\Models\Booking\Support;

use MongoDB\Laravel\Eloquent\Model;

class Driver_Support_Group extends Model
{
    public $timestamps = false;

    protected $connection = 'sky_booking';

    protected $table = 'driver_support_group';

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
        return $this->hasMany(Driver_Support_Group::class, 'parent_id', '_id')
            ->where('is_show', 1)
            ->orderBy('show_order', 'asc')
            ->orderBy('created_at', 'asc')
            ->select(config('fillable'))
            ->with(!empty(config('fillable')) ? 'sub:'.implode(',', config('fillable')) : 'sub');
    }

    function manager($paginate = 0, $filter = []) {
        $paginate = (!empty(request('paginate')) && request('paginate') == 1) ? request('paginate') : $paginate;
        if($paginate == 1){
            $data = Driver_Support_Group::when(request('sub') == 'manage' ?? null, function ($query){
                    $query->where('is_show', 1)->where('parent_id','');
                })
                ->when(request('sub') == 'manage_trash' ?? null, function ($query){
                    $query->where('is_show', 0)->without('sub');
                })
                ->when($filter ?? null, function($query) use ($filter){ // Lọc các trường khác theo bộ lọc
                    foreach ($filter as $item){
                        eval($item);
                    }
                })
                ->orderBy('show_order', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))->toArray();
            return response_pagination($data);
        }else{
            $data = Driver_Support_Group::when(request('sub') == 'manage' ?? null, function ($query){
                    $query->where('is_show', 1)->where('parent_id','');
                })
                ->when(request('sub') == 'manage_trash' ?? null, function ($query){
                    $query->where('is_show', 0)->without('sub');
                })
                ->when($filter ?? null, function($query) use ($filter){ // Lọc các trường khác theo bộ lọc
                    foreach ($filter as $item){
                        eval($item);
                    }
                })
                ->orderBy('show_order', 'desc')
                ->orderBy('created_at', 'desc')
                ->get(config('fillable'))->toArray();
            return response_pagination($data);
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
