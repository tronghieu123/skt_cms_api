<?php

namespace App\Models\Booking\Vehicle;

use MongoDB\Laravel\Eloquent\Model;

class Vehicle_Models extends Model{
    protected $connection = 'sky_booking';
    protected $table = 'vehicle_models';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'date_start' => 'timestamp',
        'date_end' => 'timestamp'
    ];

    function loadServiceBooking(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('vehicle_id'))){
            $data = Service_Booking::where('vehicle_id', request('vehicle_id'))->pluck('title','_id')->toArray();
            return response_custom('',0, $data);
        }else{
            return response_custom('Không tìm thấy phương tiện!', 1);
        }
    }

    function detailVehicleModels(){
        if(request()->method() != 'POST'){
            return response_custom('Sai phương thức!', 1, [],405);
        }
        if(!empty(request('item'))){
            $data = Vehicle_Models::where('_id', request('item'))->first();
            if($data){
                $data = $data->toArray();
                if(!empty($data['list_service'])){
                    $data['service_selected'] = Service_Booking::whereIn('_id', $data['list_service'])->pluck('title', '_id')->toArray();
                }
                return response_custom('',0, $data);
            }else{
                return response_custom('Không tìm thấy dữ liệu!', 1);
            }
        }else{
            return response_custom('Không tìm thấy phương tiện!', 1);
        }
    }
}
