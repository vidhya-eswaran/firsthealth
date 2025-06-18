<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripStatusLog extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'trip_id',
        'status',
        'status_updated_at',
        'time_taken'
    ];

}
