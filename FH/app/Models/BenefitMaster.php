<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenefitMaster extends Model
{
    protected $fillable = ['benefit_description'];

    public function subscriptions()
    {
        return $this->belongsToMany(SubscriptionMaster::class, 'subscription_benefits', 'benefit_id', 'subscription_id');
    }
}
