<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'adult_count',
        'senior_count',
        'child_count',
        'status'
    ];

}
