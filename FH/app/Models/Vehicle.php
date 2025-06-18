<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'vehicle_number',
        'type',
        'status',
        'hospital_id',
        'hospital_name',
        'vehicle_name',
        'ambulance_life_support'
    ];
}
