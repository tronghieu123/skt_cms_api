<?php

namespace App\Models\Booking\Driver;

use App\Http\Token;
use Illuminate\Support\Facades\DB;
use League\Flysystem\Config;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\CustomCasts\jsonToArray;
use Illuminate\Support\Facades\Http;

use App\Models\Booking\Booking\Booking_Status;
use App\Models\Booking\Booking\Booking_Log_Find;
use App\Models\Booking\Booking\Method_Payment;
use App\Models\Booking\Driver\Driver_Info;
use App\Models\Booking\Driver\Vehicle;
use App\Models\Booking\Driver\Driver;
use App\Models\Booking\Driver\Driver_Token;
use App\Models\Booking\Service\Service_Booking;
use App\Models\Booking\Service\Service_Delivery;
use App\Models\Booking\Service\Service_Food;
use App\Models\Booking\Shared_Rate\Shared_Rate_Customer;
use App\Models\Booking\Shared_Rate\Shared_Rate_Driver;
use App\Models\Sky\User\User;
use App\Models\Sky\User\DeviceToken;
use App\Models\Sky\Config\Setting;
use Google\Cloud\Core\Timestamp;

//use function League\Flysystem\map;

class Income extends Model{
    public $timestamps = true;
    protected $connection = 'sky_booking';
    protected $table = 'booking';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'driver_approve_date' => 'timestamp',
        'expired_booking' => 'timestamp',
        'date_cancel' => 'timestamp',
        'date_start' => 'timestamp',
        'date_end' => 'timestamp'
    ];
    protected $with = ['status_info', 'driver_info', 'method_info', 'customer_rated', 'driver_rated'];
    protected $appends = ['vehicle_type', 'user_cancel_fullname'];

    public function customer_rated()
    {
        return $this->hasOne(Shared_Rate_Customer::class, 'type_id',  '_id');
    }

    public function driver_rated()
    {
        return $this->hasOne(Shared_Rate_Driver::class, 'type_id',  '_id');
    }

    public function driver_info()
    {
        switch (request()->type) {
            case 'pending':
                return $this->hasOne(Driver::class, '_id',  'driver_waiting_confirm');
                break;
            default:
                return $this->hasOne(Driver::class, '_id',  'driver_id');
                break;
        }
    }

    public function status_info()
    {
        return $this->hasOne(Booking_Status::class, 'value', 'is_status')->withCasts(['created_at' => 'timestamp', 'updated_at' => 'timestamp'])->select('title', 'color_background', 'color_text', 'value');
    }

    public function method_info()
    {
        return $this->hasOne(Method_Payment::class, '_id', 'method')->withCasts(['created_at' => 'timestamp', 'updated_at' => 'timestamp'])->select('title', 'picture');
    }

    public function getUserCancelFullnameAttribute()
    {
        switch ($this->type_cancel) {
            case 'customer':
                $output = User::where('_id', $this->user_cancel)->value('full_name');
                break;
            case 'driver':
                $output = Driver::where('_id', $this->user_cancel)->value('full_name');
                break;
            default:
                $output = $this->morphTo();
                break;
        }
        return $output;
    }

    public function getVehicleTypeAttribute()
    {
        switch ($this->type) {
            case 'booking':
                $output = Service_Booking::where('_id', $this->service_id)->withCasts(['created_at' => 'timestamp', 'updated_at' => 'timestamp'])->first(['title', 'picture']);
                break;
            case 'delivery':
                $output = Service_Delivery::where('_id', $this->service_id)->withCasts(['created_at' => 'timestamp', 'updated_at' => 'timestamp'])->first(['title', 'picture']);
                $output['short'] = Vehicle::find($this->vehicle_id)->short ?? $output['short'];
                break;
            case 'food':
                $output = Service_Food::where('_id', $this->service_id)->withCasts(['created_at' => 'timestamp', 'updated_at' => 'timestamp'])->first(['title', 'picture']);
                break;
            default:
                $output = [];
                break;
        }
        return $output;
    }

    public function getCustomerRateAttribute()
    {
        switch ($this->type) {
            case 'booking':
                $output = Service_Booking::where('_id', $this->service_id)->withCasts(['created_at' => 'timestamp', 'updated_at' => 'timestamp'])->first();
                break;
            case 'delivery':
                $output = Service_Delivery::where('_id', $this->service_id)->withCasts(['created_at' => 'timestamp', 'updated_at' => 'timestamp'])->first();
                $output['short'] = Vehicle::find($this->vehicle_id)->short ?? $output['short'];
                break;
            case 'food':
                $output = Service_Food::where('_id', $this->service_id)->withCasts(['created_at' => 'timestamp', 'updated_at' => 'timestamp'])->first();
                break;
            default:
                $output = [];
                break;
        }
        return $output;
    }

    public function income()
    {
        $time = '30-01-2024 08:56:00';
        $mongo_time = convert_date_time($time);
        die;
        if (request()->method() != 'POST') {
            return response_custom('Sai phương thức!', 1, [], 405);
        }

        $select = [
            'type', 'vehicle_id', 'service_id', 'item_code', 'driver_id', 'driver_info','method',
            'amount_driver', 'amount_driver_revenue', 'amount_tip',
            'created_at', 'updated_at', 'date_start', 'date_end'
        ];

        $data = Income::filter()
            ->where([
                'is_show' => 1,
                'is_complete' => 1,
                'is_cancel' => 0,
                'is_status' => 3,
            ])
            ->where('driver_id', '!=', '')
            ->where('driver_id', '!=', 0)
            ->when(!empty(request('item')) ?? null, function ($query){
                $query->where('_id', request('item'));
            })
            ->select($select)
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        return response_pagination($data);
    }

    public static function scopeFilter($query)
    {
        $query->when(request('keyword') ?? null, function ($query) { // tìm kiếm
            $query->where(function ($q) {
                $q->where('_id', request('keyword'))
                    ->orWhere('item_code', request('keyword'))
                    ->orWhere('driver_full_name', 'like', '%' . request('keyword') . '%')
                    ->orWhere('driver_phone', 'like', '%' . request('keyword') . '%');
            });
        })
        ->when(request('date_start') ?? null, function ($query){
            $date_start = convert_date_search(request('date_start'));
            $query->whereDate("created_at", ">=", $date_start);
        })
        ->when(request('date_end') ?? null, function ($query){
            $date_end = convert_date_search(request('date_end'));
            $query->whereDate("created_at", "<=", $date_end);
        });
    }
}
