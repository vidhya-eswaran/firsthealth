<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverDeclinedReason extends Model
{
    use HasFactory;

    protected $table = 'driver_declined_reason';

    protected $fillable = [
        'driver_id',
        'driver_name',
        'phone_number',
        'declined_reason',
        'trip_id'
    ];
}
