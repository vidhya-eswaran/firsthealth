<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebasePushNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $this->messaging = (new Factory)
            ->withServiceAccount(config('services.firebase.credentials'))
            ->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $body, $options = [])
    {
        try {
            $appLogo = "http://stg-api.firsthealthassist.com/images/image.png";
            $notificationLogo = "http://stg-api.firsthealthassist.com/images/notification.png";
            $sound = $options['sound'] ?? 'default';
            //'image' => $appLogo,
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                    'icon' => 'ic_notification', // Set a valid drawable resource name for small icon
                    'image' => $notificationLogo,         // URL for the image
                    'color'=>'#ffffff',
                    'sound' => $sound,
                ]) 
                ->withData([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => $sound,  // 🔊 Ensure sound is included in the data payload
                ]);
    
            return $this->messaging->send($message);
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            // Log or handle invalid tokens
            \Log::error("Invalid Firebase token: {$deviceToken}");
            throw new \Exception("Invalid token: " . $e->getMessage());
        } catch (\Kreait\Firebase\Exception\Messaging\MessagingException $e) {
            // Log any Firebase messaging-specific errors
            \Log::error("Firebase messaging error: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            // Log any general exceptions
            \Log::error("Failed to send notification: " . $e->getMessage());
            throw $e;
        }
    }
}
