<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionBenefit extends Model
{
    protected $fillable = ['subscription_id', 'benefit_id'];
}
