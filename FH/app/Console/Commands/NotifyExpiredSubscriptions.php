<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubscriptionService;

class NotifyExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:notify-expired';
    protected $description = 'Notify users about subscription expiry';

    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    public function handle()
    {
        // Call the service method to notify users about expired subscriptions
        $response = $this->subscriptionService->notifyExpiredSubscriptions();

        if ($response['status'] === 'success') {
            $this->info($response['message']);
        } else {
            $this->error($response['message']);
        }
    }
}
