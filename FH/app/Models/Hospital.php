<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hospital extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'address',
        'phone_number',
        'latitude',
        'longitude',
        'zoho_record_id'
    ];
}
