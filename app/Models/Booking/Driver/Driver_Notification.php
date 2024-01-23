<?php

namespace App\Models\Booking\Driver;
use Illuminate\Support\Facades\Http;
use MongoDB\Laravel\Eloquent\Model;
//use App\Models\CustomCasts\jsonToArray;

class Driver_Notification extends Model{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'driver_notification';

    // --------- Thêm thông báo tài xế ---------
    function addNotificationDriver(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('arr_data'))){
            $data = json_decode(request('arr_data'),true);
            $data['type_of'] = 'system';
            $data['short'] = !empty($data['short']) ? htmlspecialchars($data['short']) : '';
            $data['content'] = !empty($data['content']) ? htmlspecialchars($data['content']) : '';
            $data['is_show'] = 1;
            $data['show_order'] = 0;
            $data['created_at'] = mongo_time();
            $data['updated_at'] = mongo_time();
            $data['admin_id'] = Config('admin_id');
            if($data['type'] == 'all'){
                $data['driver_id'] = [];
                $ok = Driver_Notification::insertGetId($data);
                if($ok){
                    $id = mongodb_id($ok);
                    $data_autosend = [
                        'noti_id' => $id,
                        'total_driver' => 0,
                        'total_send' => 0,
                        'is_complete' => 0,
                        'is_show' => 1,
                        'created_at' => mongo_time(),
                        'updated_at' => mongo_time()
                    ];
                    Driver_Notification_Autosend::insert($data_autosend);
                    return response_custom('Thêm thông báo thành công!');
                }
            }elseif($data['type'] == 'each'){ // Gửi thông báo cho từng tài xế
                if(empty($data['driver_id'])){
                    return response_custom('Chưa chọn tài xế gửi!',1);
                }
                $data['driver_id'] = array_values(array_unique(array_filter($data['driver_id'])));
                $id = Driver_Notification::insertGetId($data);
                $id = mongodb_id($id);
                // Gửi thông báo đến các thiết bị của từng tài xế
                $target_notic = Config('Api_app').'/firebase/api/messaging';
                foreach ($data['driver_id'] as $item){
                    $device_token = Driver_Token::where('driver_id', $item)->pluck('device_token');
                    if($device_token){
                        foreach ($device_token as $token){
                            $notic = [
                                'token' => $token,
                                'push_noti' => [
                                    'title' => $data['title'],
                                    'body' => short($data['short'])
                                ],
                                'push_data' => [
                                    '_id' => $id,
                                    'type' => 'newNotification'
                                ]
                            ];
                            Http::post($target_notic, $notic)->json();
                        }
                    }
                }
                return response_custom('Thêm thông báo thành công!');
            }
        }else{
            return response_custom('Dữ liệu rỗng',1);
        }
    }
}
