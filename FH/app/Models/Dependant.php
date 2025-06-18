<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dependant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'ic_number',
        'phone_number',
        'email',
        'dob',
        'race',
        'gender',
        'nationality',
        'heart_problems',
        'diabetes',
        'allergic',
        'allergic_medication_list',
        'type',
        'status',
    ];

}
