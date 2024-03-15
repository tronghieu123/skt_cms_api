<?php

namespace App\Models\Booking\Driver;
use MongoDB\Laravel\Eloquent\Model;
use App\Models\CustomCasts\jsonToArray;

class Driver_Info extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_booking';
    protected $table = 'driver_info';
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'noted_at' => 'timestamp',
        'birthday' => 'timestamp',
        'identification_issuancedate' => 'timestamp',
        'insurance_expiration_date' => 'timestamp',
        'license_expiration_date' => 'timestamp',
        'vehicle_registration_expired' => 'timestamp',
        'contract_badges_expiration_date' => 'timestamp',
        'health_certificate_dateofissue' => 'timestamp',
        'vehicle_picture' => jsonToArray::class,
        'other_documents' => jsonToArray::class,
        'insurance_picture' => jsonToArray::class,
        'contract_badges_picture' => jsonToArray::class,
        'health_certificate_picture' => jsonToArray::class,
        'vehicle_license_plates_picture' => jsonToArray::class
    ];
    protected $with = ['vehicle_brand_info'];

    public function vehicle_brand_info() {
        return $this->hasOne(Vehicle_Brand::class, '_id',  'vehicle_brand')->select('title');
    }
}
