<?php

namespace App\Models\Booking\Driver;

use App\Models\Booking\Booking\Booking_Setting;
use Illuminate\Support\Facades\Http;
use MongoDB\Laravel\Eloquent\Model;

class Almost_Expired_Registration extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'driver';
    protected $hidden = ['password'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'updated_at_online' => 'timestamp',
        'updated_at_position' => 'timestamp',
        'code_expiry' => 'timestamp',
        'locked_at' => 'timestamp',
        'unlocked_at' => 'timestamp',
        'rank_expiration' => 'timestamp',
        'approved_complete_at' => 'timestamp'
    ];

    public function info() {
        return $this->hasOne(Driver_Info::class, 'driver_id',  '_id')
            ->select('vehicle_registration_expired','driver_id','avatar','vehicle_brand','vehicle_picture','vehicle_license_plates');
    }

    public function vehicle_type() {
        return $this->hasOne(Vehicle::class, '_id',  'vehicle')->select('name_action','picture','title');
    }

    public function partner() {
        return $this->hasOne(Partner::class, '_id',  'partner_id')->select('phone', 'email', 'full_name');
    }

    public function listAlmostExpiredRegistration() {
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $expire = Booking_Setting::where('setting_key', 'driver_registration_expire')->value('setting_value');
        if(!$expire){
            $expire = 30;
        }
        $date_begin = date('Y-m-d');
        $expire = strval(now()->addDays($expire)->getTimestamp());
        $date_expire = date('Y-m-d', $expire);

        $type = !empty(request('type')) ? request('type') : 'all';
        $data = Almost_Expired_Registration::when($type == 'all', function ($query) use ($date_begin, $date_expire){
                $query->whereHas('info', function($q) use ($date_begin, $date_expire){
                    $q->whereDate('vehicle_registration_expired', '<=', $date_expire);
                });
            })
            ->when($type == 'almost_expired', function ($query) use ($date_begin, $date_expire){
                $query->whereHas('info', function($q) use ($date_begin, $date_expire){
                    $q->whereDate('vehicle_registration_expired', '>=', $date_begin)
                        ->whereDate('vehicle_registration_expired', '<=', $date_expire);
                });
            })
            ->when($type == 'expired', function ($query) use ($date_begin, $date_expire){
                $query->whereHas('info', function($q) use ($date_begin, $date_expire){
                    $q->whereDate('vehicle_registration_expired', '<', $date_begin);
                });
            })
            ->with('info', 'vehicle_type', 'partner')
            ->filter()
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'))
            ->toArray();

        if(!empty($data['data'])){
            foreach ($data['data'] as $k => $v){
                if($v['info']['vehicle_registration_expired'] < time()){
                    $data['data'][$k]['status'] = color_status_expired(2);
                }elseif ($v['info']['vehicle_registration_expired'] >= time() && $v['info']['vehicle_registration_expired'] <= $expire){
                    $data['data'][$k]['status'] = color_status_expired(1);
                }else{
                    $data['data'][$k]['status'] = color_status_expired();
                }
            }
        }
        $data['other']['counter'] = $this->tabListDriver($date_begin, $date_expire);
        return response_pagination($data);
    }

    function tabListDriver($date_begin, $date_expire){
        $data['all'] = Almost_Expired_Registration::whereHas('info', function($q) use ($date_begin, $date_expire){
            $q->whereDate('vehicle_registration_expired', '<=', $date_expire);
        })->filter()->count();

        $data['almost_expired'] = Almost_Expired_Registration::whereHas('info', function($q) use ($date_begin, $date_expire){
            $q->whereDate('vehicle_registration_expired', '>=', $date_begin)
                ->whereDate('vehicle_registration_expired', '<=', $date_expire);
        })->filter()->count();

        $data['expired'] = Almost_Expired_Registration::whereHas('info', function($q) use ($date_begin, $date_expire){
            $q->whereDate('vehicle_registration_expired', '<', $date_begin);
        })->filter()->count();

        return $data;
    }

    public static function scopeFilter($query)
    {
        $query->when(!empty(request('keyword')) ?? null, function ($q){
            $keyword = explode_custom(request('keyword'), ' ');
            if($keyword){
                foreach ($keyword as $item){
                    $q->orWhere('full_name', 'LIKE', '%'.$item.'%');
                }
            }
            $q->orWhere('phone', 'LIKE', '%'.request('keyword').'%')
            ->orWhere('email', 'LIKE', '%'.request('keyword').'%');
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

    function sendNotifExpiredRegistration(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        $expire = Booking_Setting::where('setting_key', 'driver_registration_expire')->value('setting_value');
        if(!$expire){
            $expire = 30;
        }
        $date_begin = time();
        $expire = strval(now()->addDays($expire)->getTimestamp());

        if(!empty(request('item'))){
            $driver = Almost_Expired_Registration::where('_id', request('item'))->with('info')->first();
            if($driver){
                $driver = $driver->toArray();
                if(!empty($driver['info']['vehicle_registration_expired'])){
                    if($driver['info']['vehicle_registration_expired'] <= $expire){
                        if($driver['info']['vehicle_registration_expired'] < $date_begin){ // Đã hết hạn
                            $template = 'expiredRegistration';
                            $this->sendNotifRegistration($driver, $template);
                        }elseif($driver['info']['vehicle_registration_expired'] >= $date_begin && $driver['info']['vehicle_registration_expired'] <= $expire){ // Sắp hết hạn
                            $template = 'almostExpiredRegistration';
                            $this->sendNotifRegistration($driver, $template);
                        }
                        return response_custom('Gửi thông báo thành công!');
                    }else{
                        return response_custom('Đăng kiểm chưa tới hạn!', 1);
                    }
                }else{
                    return response_custom('Tài xế chưa cập nhật đăng kiểm!', 1);
                }
            }else{
                return response_custom('Không tìm thấy tài xế!', 1);
            }
        }
        return response_custom('Thao tác thất bại!', 1);
    }

    function sendNotifRegistration($driver, $template){
        $device_token = Driver_Token::where('driver_id', $driver['_id'])->pluck('device_token');
        if($device_token){
            dd($device_token);
            $target_notic = Config('Api_app').'/firebase/api/messaging';
            foreach ($device_token as $item){
                $data = [
                    'token' => $item,
                    'template' => $template,
                    'arr_replace' => [
                        'title' => [
                            'full_name' => $driver['full_name'],
                        ],
                        'body' => [
                            'vehicle_registration_expired' => date('H:i d/m/Y', $driver['info']['vehicle_registration_expired']),
                        ]
                    ]
                ];
                Http::post($target_notic, $data)->json();
            }
        }
    }
}
