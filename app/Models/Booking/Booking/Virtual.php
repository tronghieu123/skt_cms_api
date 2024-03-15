<?php

namespace App\Models\Booking\Booking;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\Http;

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

class Virtual extends Model
{

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
        'date_end' => 'timestamp',
        'schedule_time' => 'timestamp'
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
        return $this->hasOne(Driver::class, '_id',  'driver_id');
    }

    public function status_info()
    {
        return $this->hasOne(Booking_Status::class, 'value', 'is_status')->withCasts(['created_at' => 'timestamp', 'updated_at' => 'timestamp'])->select('title', 'color_background', 'color_text', 'value');
    }

    public function method_info()
    {
        return $this->hasOne(Method_Payment::class, '_id', 'method')->withCasts(['created_at' => 'timestamp', 'updated_at' => 'timestamp'])->select('title', 'picture');
    }

    public function getReasonAttribute($value)
    {
        $output = $value ?? '';
        if(!empty($this->reason_id)) {
            $output = Booking_Reason_Cancel::find($this->reason_id)->value('title');
        }
        return $output;
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


    public function get_location_start()
    {
        if (request()->method() != 'POST') {
            return response_custom('Sai phương thức!', 1, [], 405);
        }

        $infoBooking = Virtual::where('_id', request()->item)
            ->first()
            ->value('origin');
        if (!$infoBooking) {
            return response_custom('Không tìm thấy đơn hàng!', 1);
        }
        $logFind = Booking_Log_Find::where('booking_id', request()->item)->orderBy('date_create', 'desc')->first();
        if($logFind) {
            $tmp = $logFind->send;
            $send = ims_json_decode($tmp);

            $infoDriver = Driver::find($send['info_driver']['_id']);
            $from['lat'] = $infoBooking['detail']['lat'] ?? '';
            $from['lng'] = $infoBooking['detail']['lng'] ?? '';
            $to['lat'] = $send['info_driver']['latitude'] ?? '';
            $to['lng'] = $send['info_driver']['longitude'] ?? '';

            $output = $this->get_address_goong($from, $to);
            $output['lat'] = $to['lat'];
            $output['lng'] = $to['lng'];
            $output['driver_distance'] = $send['distance'] ?? '';
            $output['driver_full_name'] = $infoDriver['full_name'];
            $output['driver_phone'] = $infoDriver['phone'];
            $output['type'] = 'Tự nhận chuyến';
            return $output;
        }
    }

    public function get_location_complete()
    {
        if (request()->method() != 'POST') {
            return response_custom('Sai phương thức!', 1, [], 405);
        }

        $infoBooking = Virtual::where('_id', request()->item)
            ->select('origin', 'latitude_driver_complete', 'longitude_driver_complete', 'driver_full_name', 'driver_phone', 'driver_distance')
            ->first();
        if (!$infoBooking) {
            return response_custom('Không tìm thấy đơn hàng!', 1);
        }

        $from['lat'] = $infoBooking['latitude_driver_complete'] ?? '';
        $from['lng'] = $infoBooking['longitude_driver_complete'] ?? '';
        $to['lat'] = $infoBooking['latitude_driver_complete'] ?? '';
        $to['lng'] = $infoBooking['longitude_driver_complete'] ?? '';

        $output = $this->get_address_goong($from, $to);
        $output['lat'] = $to['lat'];
        $output['lng'] = $to['lng'];
        return $output;
    }

    public function virtual()
    {
        if (request()->method() != 'POST') {
            return response_custom('Sai phương thức!', 1, [], 405);
        }

        $data = Virtual::filter()
            ->where([
                'is_show' => 1,
                'is_complete' => 1,
                'is_cancel' => 0,
                'is_status' => 3,
                'is_virtual' => 1,
            ])->where('driver_id', '!=', '')
            ->where('driver_id', '!=', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        // dd($data);
        // $data['data'] = collect($data['data'])->map(function($row){
        //     $row['driver_info']['full_name'] = 'An';
        //     return $row;
        // });
        return response_pagination($data);
    }

    public static function scopeFilter($query)
    {
        $query->when(request('keyword') ?? null, function ($query) { // Hủy đơn
            $query->where(function ($q) {
                $q->where('_id', request('keyword'))
                    ->orWhere('item_code', 'like', '%' . request('keyword') . '%')
                    ->orWhere('full_name', 'like', '%' . request('keyword') . '%')
                    ->orWhere('phone', 'like', '%' . request('keyword') . '%')
                    ->orWhere('driver_full_name', 'like', '%' . request('keyword') . '%')
                    ->orWhere('driver_phone', 'like', '%' . request('keyword') . '%');
            });
        })->when(!empty(request('item')) ?? null, function ($query){
            $query->where('_id', request('item'));
        });
    }

    public function get_address_goong($from = [], $to = [])
    {
        $setting = Booking_Setting::pluck('setting_value', 'setting_key');
        $output = [
            'address' => '',
            'link_map' => 'https://www.google.com/maps/dir/'
        ];

        $data = [
            'latlng' => $from['lat'] . ',' . $from['lng'],
            'api_key' => $setting['goong_api_key']
        ];
        $resp = Http::get('https://rsapi.goong.io/Geocode', $data)->json();

        $output['address'] = $resp['results'][0]['formatted_address'] ?? '';
        $output['link_map'] = 'https://www.google.com/maps/dir/' . $from['lat'] . ',' . $from['lng'] . '/' . $to['lat'] . ',' . $to['lng'] . '/@' . $from['lat'] . ',' . $from['lng'] . ',20z';

        return $output;
    }
}
