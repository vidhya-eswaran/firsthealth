<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\UserSubscription;

class UpdatePlanExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:plan-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update is_plan_expired to true when the end_date has passed';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now();

        // Fetch subscriptions where the plan is not expired and the end date has passed
        $subscriptions = UserSubscription::where('is_plan_expired', false)
            ->whereDate('end_date', '<', $today)
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->is_plan_expired = true;
            $subscription->save();

            $this->info("Updated subscription ID {$subscription->id} to expired.");
        }

        $this->info('Plan expired update completed.');

        return Command::SUCCESS;
    }
}
