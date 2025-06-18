<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserSubscription;
use App\Models\RenewalRecord; // Ensure this model is created if not already
use Carbon\Carbon;

class RenewSubscriptionPlans extends Command
{
    protected $signature = 'subscriptions:renew';
    protected $description = 'Check and renew subscription plans if they have expired';

    public function handle()
    {
        $expiredSubscriptions = UserSubscription::where('end_date', '<', Carbon::now())
            ->where('is_dependent', 0)
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $renewalExists = RenewalRecord::where('user_id', $subscription->user_id)
                ->whereDate('renewal_date', Carbon::today())
                ->exists();

            if (!$renewalExists) {
                $subscription->start_date = Carbon::now();
                $subscription->end_date = Carbon::now()->addYear();
                $subscription->is_renewed = false;
                $subscription->save();

                RenewalRecord::create([
                    'user_id' => $subscription->user_id,
                    'renewal_date' => Carbon::now(),
                ]);

                $dependents = UserSubscription::where('referral_id', $subscription->user_id)
                    ->where('is_dependent', 1)
                    ->where('is_removed', 0)
                    ->get();

                foreach ($dependents as $dependent) {
                    $dependent->start_date = Carbon::now();
                    $dependent->end_date = Carbon::now()->addYear();
                    $dependent->is_renewed = false;
                    $dependent->save();
                }
            }
        }

        $this->info('Expired subscriptions have been renewed.');
    }
}
