<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ambulance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driver_id',
        'driver',
        'zoho_record_id',
        'patient_name',
        'age',
        'gender',
        'phone_number',
        'pickup_date',
        'hospital_id',
        'hospital',
        'diagnosis',
        'careoff',
        'notes',
        'clinical_info',
        'registered_address',
        'reg_lat',
        'reg_long',
        'location',
        'location_name',
        'trip',
        'status',
        'manual_username',
        'reg_id',
        'trip_status',
        'assigned_trip_status',
        'pcr_file',
        'created_at',
        'updated_at'
    ];
    
    public function userSubscription()
    {
        return $this->hasOne(UserSubscription::class, 'user_id', 'user_id');
    }


}
