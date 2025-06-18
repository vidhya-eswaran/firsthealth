<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\NotificationUser;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\FirebasePushNotificationService;

class SubscriptionService
{
    protected $firebaseService;

    public function __construct(FirebasePushNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }


    public function notifyExpiredSubscriptions()
    {
        try {
            // Get all user subscriptions, grouped by user_id
            $userSubscriptions = UserSubscription::select('user_id', 'start_date', 'end_date')->get()->groupBy('user_id');

            foreach ($userSubscriptions as $userId => $subscriptions) {
                // Get the user to notify
                $notifyUser = User::find($userId);

                if ($notifyUser && $notifyUser->device_token) {
                    foreach ($subscriptions as $userSubscription) {
                        $startDate = $userSubscription->start_date;
                        $endDate = $userSubscription->end_date;
                        $currentDate = now();
                        $diffInDays = $currentDate->diffInDays($endDate, false);

                        // Initialize the body message based on the days left before expiration
                        $body = null;
                        if ($diffInDays == 30) {
                            $body = "Your First Health subscription is expiring in 30 days. Don’t miss out—renew now to keep enjoying your current benefits.";
                        } elseif ($diffInDays == 14) {
                            $body = "Your First Health subscription expires in 14 days! Renew now to keep enjoying your benefits without interruption.";
                        }

                        // If a notification message exists, proceed with creating a notification
                        if ($body) {
                            // Create a notification for the user
                            NotificationUser::create([
                                'form_user_id' => $userId,
                                'to_user_id' => $userId,
                                'title' => '⏰ Renewal Alert',
                                'type' => 'notification',
                                'body' => $body,
                                'is_sent' => 1,
                                'created_by' => $userId,
                            ]);

                            $deviceToken = $notifyUser->device_token;

                            // Send push notification if device token exists
                            if ($deviceToken) {
                                $title = '⏰ Renewal Alert';
                                $this->firebaseService->sendNotification($deviceToken, $title, $body);
                                $currentTime = date('Y-m-d H:i:s');
                                Log::info("Renewal Alert push notification sent successfully to User ID {$userId} with device token at {$currentTime}.");
                            } else {
                                Log::warning("User ID {$userId} does not have a device token. Notification not sent.");
                            }
                        }
                    }
                }
            }

            return [
                'status' => 'success',
                'message' => 'Subscription notifications sent successfully.'
            ];
        } catch (Exception $e) {
            Log::error('Failed to send subscription expiration notifications: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to process subscription notifications.',
                'error' => $e->getMessage()
            ];
        }
    }
}
