<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionMaster extends Model
{
    protected $table = 'subscription_masters';

    protected $fillable = ['plan', 'price', 'eligible', 'free_plan', 'usual_price', 'key_benefits'];

    public function benefits()
    {
        return $this->belongsToMany(BenefitMaster::class, 'subscription_benefits', 'subscription_id', 'benefit_id');
    }
}
