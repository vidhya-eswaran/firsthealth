<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\NotificationUser;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\FirebasePushNotificationService;

class QualifyingPeriodNotificationService
{
    protected $firebaseService;

    public function __construct(FirebasePushNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function notifyQualifyingPeriod()
    {
        try {
            // Get all user subscriptions, grouped by user_id
            $userSubscriptions = UserSubscription::select('user_id', 'start_date', 'end_date', 'created_at')->get()->groupBy('user_id');

            foreach ($userSubscriptions as $userId => $subscriptions) {
                // Get the user to notify
                $notifyUser = User::find($userId);

                if ($notifyUser && $notifyUser->device_token) {
                    foreach ($subscriptions as $userSubscription) {
                        $createdDate = $userSubscription->created_at;
                        $currentDate = now();
                        $daysPassed = $createdDate->diffInDays($currentDate);
                        $body = null;

                        // Check if it's the 1st or 14th day of the qualifying period
                        if ($daysPassed == 1) {
                            $body = "Your 14-day qualifying period starts today. Thank you for being part of the First Health plan.";
                        } elseif ($daysPassed == 14) {
                            $body = "Your 14-day qualifying period has ended. You can now book ambulance rides. Check it out now!";
                        }

                        // If a notification message exists, proceed with creating a notification
                        if ($body) {
                            NotificationUser::create([
                                'form_user_id' => $userId,
                                'to_user_id' => $userId,
                                'title' => 'Qualifying Period Update',
                                'type' => 'notification',
                                'body' => $body,
                                'is_sent' => 1,
                                'created_by' => $userId,
                            ]);

                            $deviceToken = $notifyUser->device_token;

                            // Send push notification if device token exists
                            if ($deviceToken) {
                                $title = 'Qualifying Period Update';
                                $this->firebaseService->sendNotification($deviceToken, $title, $body);
                                $currentTime = date('Y-m-d H:i:s');
                                Log::info("Qualifying Period Update push notification sent successfully to User ID {$userId} with device token at {$currentTime}.");
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
            Log::error('Failed to send subscription notifications: ' . $e->getMessage(), [
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
