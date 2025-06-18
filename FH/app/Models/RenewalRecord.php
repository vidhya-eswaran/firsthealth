<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalRecord extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'renewal_date'];
}

