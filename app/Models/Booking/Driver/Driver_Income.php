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
use App\Models\Booking\Shared_Rate\Shared_Rate;
use App\Models\Sky\User\User;
use App\Models\Sky\User\DeviceToken;
use App\Models\Sky\Config\Setting;
use Google\Cloud\Core\Timestamp;

//use function League\Flysystem\map;

class Driver_Income extends Model{
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
        return $this->hasOne(Shared_Rate::class, 'type_id',  '_id')->where('type','customer_rate');
    }

    public function driver_rated()
    {
        return $this->hasOne(Shared_Rate::class, 'type_id',  '_id')->where('type','driver_rate');
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


    public function get_location_start()
    {
        if (request()->method() != 'POST') {
            return response_custom('Sai phương thức!', 1, [], 405);
        }

        $infoBooking = Direction_Booking::where('_id', request()->item)
            ->first()
            ->value('origin');
        if (!$infoBooking) {
            return response_custom('Không tìm thấy đơn hàng!', 1);
        }
        $logFind = Booking_Log_Find::where('booking_id', request()->item)->orderBy('date_create', 'desc')->first();
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

    public function get_location_complete()
    {
        if (request()->method() != 'POST') {
            return response_custom('Sai phương thức!', 1, [], 405);
        }

        $infoBooking = Direction_Booking::where('_id', request()->item)
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

    public function direction_booking()
    {
        if (request()->method() != 'POST') {
            return response_custom('Sai phương thức!', 1, [], 405);
        }

        $data = Direction_Booking::filter()
            ->when(!empty(request('item')) ?? null, function ($query){
                $query->where('_id', request('item'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(Config('per_page'), Config('fillable'), 'page', Config('current_page'))
            ->toArray();
        // $data['data'] = collect($data['data'])->map(function($row){
        //     $row['driver_info']['full_name'] = 'An';
        //     return $row;
        // });
        return response_pagination($data);
    }

    public static function scopeFilter($query)
    {
        $query->when(request('type') == 'pending' ?? null, function ($query) { // Đợi duyệt
            $query->where([
                'is_show' => 1,
                'is_complete' => 0,
                'is_cancel' => 0,
                'is_status' => 0
            ])->where('driver_waiting_confirm', '!=', '')
                ->where('driver_waiting_confirm', '!=', 0)
                ->whereNotNull('driver_waiting_confirm');
            })
            ->when(request('type') == 'confirm' ?? null, function ($query) { // Đang diễn ra
                $query->where([
                    'is_show' => 1,
                    'is_complete' => 0,
                    'is_cancel' => 0,
                ])->where('is_status', '<', 3)
                    ->where('driver_id', '!=', '')
                    ->where('driver_id', '!=', 0);
            })
            ->when(request('type') == 'complete' ?? null, function ($query) { // Hoàn thành
                $query->where([
                    'is_show' => 1,
                    'is_complete' => 1,
                    'is_cancel' => 0,
                    'is_status' => 3,
                ])->where('driver_id', '!=', '')
                    ->where('driver_id', '!=', 0);
            })
            ->when(request('type') == 'cancel' ?? null, function ($query) { // Hủy đơn
                $query->where([
                    'is_show' => 1,
                    'is_cancel' => 1,
                    'is_status' => -1,
                ])->where('driver_id', '!=', '')
                    ->where('driver_id', '!=', 0);
            })
            ->when(request('type') == 'transporting' ?? null, function ($query) { // Hủy đơn
                $query->where([
                    'is_show' => 1,
                    'is_cancel' => 1,
                ])->where('driver_id', '!=', '')
                    ->where('driver_id', '!=', 0);
            })
            ->when(request('type') == 'booking' ?? null, function ($query) { // Hủy đơn
                $query->where([
                    'is_show' => 1,
                    'type' => 'booking',
                ])->where('driver_id', '!=', '')
                    ->where('driver_id', '!=', 0);
            })
            ->when(request('type') == 'delivery' ?? null, function ($query) { // Hủy đơn
                $query->where([
                    'is_show' => 1,
                    'type' => 'delivery',
                ])->where('driver_id', '!=', '')
                    ->where('driver_id', '!=', 0);
            })
            ->when(request('type') == 'food' ?? null, function ($query) { // Hủy đơn
                $query->where([
                    'is_show' => 1,
                    'type' => 'food',
                ])->where('driver_id', '!=', '')
                    ->where('driver_id', '!=', 0);
            })
            ->when(request('type') == 'schedule' ?? null, function ($query) { // Hủy đơn
                $query->where([
                    'is_show' => 1,
                ])->where('schedule_time', '!=', '')
                    ->where('schedule_time', '!=', 0)
                    ->whereNotNull('schedule_time');
            })
            ->when(request('keyword') ?? null, function ($query) { // Hủy đơn
                $query->where(function ($q) {
                    $q->where('_id', request('keyword'))
                        ->orWhere('item_code', request('keyword'))
                        ->orWhere('full_name', 'like', '%' . request('keyword') . '%')
                        ->orWhere('phone', 'like', '%' . request('keyword') . '%')
                        ->orWhere('driver_full_name', 'like', '%' . request('keyword') . '%')
                        ->orWhere('driver_phone', 'like', '%' . request('keyword') . '%');
                });
            });
    }

    public function tab_count()
    {
        $output = [];

        $arr = ['pending', 'confirm', 'complete', 'cancel', 'transporting', 'booking', 'delivery', 'food', 'schedule'];
        foreach ($arr as $tab) {
            request()->request->add(['type' => $tab]);
            $output[$tab] = Direction_Booking::filter()->count();
        }

        return response_custom('', 0, $output);
    }

    public function cancel_booking()
    {
        if (request()->method() != 'POST') {
            return response_custom('Sai phương thức!', 1, [], 405);
        }
        if (empty(request('item'))) {
            return response_custom('Không tìm thấy đơn hàng!', 1);
        }

        $infoBooking = Direction_Booking::find(request()->item);
        if (!$infoBooking) {
            return response_custom('Không tìm thấy đơn hàng!', 1);
        }

        $arr_data = request()->arr_data ?? '';
        $input = ims_json_decode($arr_data);

        $infoUser = User::find($infoBooking['user_id']);

        $update = [
            'is_cancel'    => 1,
            'is_status' => -1,
            'reason' => $input['reason'],
            'reason_id' => request()->reason_id ?? 0,
            'user_cancel' => 'ADMIN',
            'date_cancel' => mongo_time(),
            'driver_cancel_count' => $infoBooking['driver_cancel_count'] + 1,
            'driver_waiting_date' => 0,
            'driver_waiting_expired' => 0,
            'driver_waiting_confirm' => '',
            'driver_waiting_confirm_fullname' => '',
        ];

        $ok = 0;
        if (empty($input['is_test'])) {
            /**** Mở hoàn tiền cho khách nếu thanh toán bằng ví ****/
            $method = Method_Payment::find($infoBooking['method']);
            if ($method['name_action'] == 'wallet' && $infoBooking['wallet_cash_lock'] > 0) {
                $update_lock = $infoBooking;
                $update_lock['money_pay'] = $infoBooking['wallet_cash_lock'];

                $target = Config('Api_app') . '/user/api/repay';
                $token = (new Token)->getToken($target, $infoUser['_id']);
                $id_log = Http::withToken($token)->post($target, $update_lock)->json();
                if (isset($id_log['_id']) && $id_log['_id'] != "") {
                    $update['wallet_id_log'] = $id_log['_id'];
                    $update['wallet_cash_lock'] = 0;
                } else {
                    return response_custom('Có lỗi xảy ra! không thể hoàn tiền lại cho khách.', 1);
                }
            }
            /**** Mở hoàn tiền cho khách nếu thanh toán bằng ví ****/
            $ok = Booking::where(["_id" => $infoBooking['_id']])->update($update);
        }
        if ($ok) {

            /**** Hủy tài xế chờ xác nhận ****/
            Booking_Waiting_Confirm::where(['booking_code' => $infoBooking['item_code']])->delete();
            /**** Hủy tài xế chờ xác nhận ****/

            /**** Hủy đang chạy ****/
            Booking_Running::where(['booking_id' => $infoBooking['_id']])->delete();
            /**** Hủy đang chạy ****/

            // đã có tài xế nhận hoặc có tài xế chờ
            if ($infoBooking['driver_id'] != 0 || $infoBooking['driver_waiting_confirm'] != 0) {
                // đã có tài xế nhận
                if ($infoBooking['driver_id'] != 0) {
                    $device_token_driver = Driver_Token::where(['driver_id' => $infoBooking['driver_id']])->pluck('device_token');
                    // cập nhật lại cho tài xế
                    $update_driver = [];
                    if ($infoBooking['type'] == "booking") {
                        $update_driver['is_running_driver'] = 0;
                    } elseif ($infoBooking['type'] == "delivery") {
                        $explode = explode(',', $infoUser['list_running_booking']);
                        $arrTmp = [];
                        foreach ($explode as $k => $v) {
                            if ($v != $infoBooking['_id']) {
                                $arrTmp = $v;
                            }
                        }
                        if (!empty($arrTmp)) {
                            $update_driver['count_running_booking'] = count($arrTmp);
                            $update_driver['list_running_booking'] = implode(',', $arrTmp);
                        } else {
                            $update_driver['list_running_booking'] = "";
                            $update_driver['count_running_booking'] = 0;
                        }
                        if ($update_driver['list_running_booking'] == "") {
                            $update_driver['is_running_delivery'] = 0;
                            $update_driver['count_running_booking'] = 0;
                        }
                    }
                    $update_driver['updated_at'] = mongo_time();
                    Driver::where(["_id" => $infoBooking['driver_id']])->update($update_driver);
                } elseif ($infoBooking['driver_waiting_confirm'] != 0) {
                    $device_token_driver = Driver_Token::where(['driver_id' => $infoBooking['driver_waiting_confirm']])->pluck('device_token');
                }
                foreach ($device_token_driver as $token) {
                    $firebase = [
                        'token' => $token,
                        'template' => 'adminCancel',
                        'push_data' => [
                            '_id' => $infoBooking['_id'],
                            'type' => 'adminCancel',
                            'type_booking' => $infoBooking['type'],
                        ],
                    ];
                    $target_notic = Config('Api_app') . '/firebase/api/messaging';
                    Http::post($target_notic, $firebase)->json(); // Gửi thông báo đến tài xế
                }
            }

            // bắn thông báo cho khách hàng
            $device_token_user = DeviceToken::where(['user_id' => $infoBooking['user_id']])->pluck('device_token');
            foreach ($device_token_user as $token) {
                $firebase = [
                    'token' => $token,
                    'template' => 'adminCancel',
                    'push_data' => [
                        '_id' => $infoBooking['_id'],
                        'type' => 'adminCancel'
                    ],
                ];
                $target_notic = Config('Api_app') . '/firebase/api/messaging';
                Http::post($target_notic, $firebase)->json(); // Gửi thông báo đến khách hàng
            }
            return response_custom('', 0, $update);
        }
        return response_custom('Không thể hủy!', 1);
    }

    public function get_address_goong($from = [], $to = [])
    {
        $setting = Setting::pluck('setting_value', 'setting_key');
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
