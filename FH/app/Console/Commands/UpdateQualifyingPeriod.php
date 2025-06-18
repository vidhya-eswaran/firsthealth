<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\UserSubscription;

class UpdateQualifyingPeriod extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:qualifying-period';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update is_qualifying_period to false after 14 days of start_date';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now();

        // Fetch subscriptions where the qualifying period is still true and 14 days have passed
        $subscriptions = UserSubscription::where('is_qualifying_period', true)
            ->whereDate('start_date', '<=', $today->subDays(14))
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->is_qualifying_period = false;
            $subscription->save();

            $this->info("Updated subscription ID {$subscription->id}.");
        }

        $this->info('Qualifying period update completed.');

        return Command::SUCCESS;
    }
}
