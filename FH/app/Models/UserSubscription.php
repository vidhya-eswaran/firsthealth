<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    protected $fillable = ['user_id', 'subscription_id', 'referral_no', 'referral_id', 'is_accepted', 'is_qualifying_period', 'count', 'adult_count', 'senior_count', 'child_count', 'free_plan', 'is_dependent', 'is_plan_expired', 'is_manual', 'is_removed', 'is_paid', 'amount','transaction_id', 'type_dependant', 'reg_id', 't_emergency_calls', 'r_emergency_calls', 't_clinic_calls', 'r_clinic_calls', 'start_date', 'end_date','zoho_record_id', 'is_active' ];
    
    public function subscriptionMaster()
    {
        return $this->belongsTo(SubscriptionMaster::class, 'subscription_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id'); // Adjust column names as necessary
    }
    
    public function dependentDetails()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }


}
