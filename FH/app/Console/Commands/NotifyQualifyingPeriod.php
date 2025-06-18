<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QualifyingPeriodNotificationService;

class NotifyQualifyingPeriod extends Command
{
    // The name and signature of the console command
    protected $signature = 'notify:qualifying-period';

    // The console command description
    protected $description = 'Notify users about their qualifying period for subscriptions';

    protected $qualifyingPeriodNotificationService;

    // Inject the notification service
    public function __construct(QualifyingPeriodNotificationService $qualifyingPeriodNotificationService)
    {
        parent::__construct();
        $this->qualifyingPeriodNotificationService = $qualifyingPeriodNotificationService;
    }

    public function handle()
    {
        // Call the service to notify users
        $this->qualifyingPeriodNotificationService->notifyQualifyingPeriod();

        // Output a success message in the console
        $this->info('Qualifying period notifications have been sent.');
    }
}
