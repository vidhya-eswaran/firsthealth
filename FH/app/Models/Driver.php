<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'current_lat',
        'current_long',
        'address',
        'email',
        'phone_number',
        'id_proof',
        'license_number',
        //'guarantor_name',
        //'guarantor_phone_number',
        'license_issue_date',
        'license_valid_from',
        'license_valid_upto',
        'driver_country_code',
       // 'guarantor_country_code',
       // 'rfid_tracking_id',
        'status',
        'vehicle_number',
        'passport_number',
        'hospital_name',
        'hospital_id',
        'zoho_record_id'
    ];
}

