<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoasterMapping extends Model
{
    use HasFactory;

    protected $table = 'roaster_mapping';

    protected $fillable = [
        'hospital', 'hospital_id', 'paramedic_id', 'driver_id',
        'driver_name', 'vehicle','vehicle_id', 'driver_status', 'ride_status','zoho_record_id', 'shift'
    ];
}
