<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ic_number',
        'are_u_foreigner',
        'passport_no',
        'phone_number',
        'email',
        'address',
        'postcode',
        'city',
        'state',
        'country',
        'is_covered',
        'password',
        'medical_info',
        'referral_number',
        'first_name',
        'last_name',
        'race',
        'gender',
        'nationality',
        'dob',
        'address2',
        'heart_problems',
        'diabetes',
        'allergic',
        'allergic_medication_list',
        'longitude',
        'latitude',
        'zoho_record_id',
    ];

    protected $hidden = [
        'password',
    ];
}
