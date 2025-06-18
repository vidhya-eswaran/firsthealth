<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paramedic extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'hospital_id',
        'phone_number',
        'hospital_name',
        'status'
    ];
}
