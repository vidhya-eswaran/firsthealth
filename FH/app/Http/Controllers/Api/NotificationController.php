<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Services\FirebasePushNotificationService;

class NotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebasePushNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function sendPushNotification(Request $request)
    {
        $userId = $request->user()->id;
        $deviceToken = $request->input('device_token');
        $title = $request->input('title');
        $body = $request->input('body');

        $this->firebaseService->sendNotification($deviceToken, $title, $body);

        return response()->json(['message' => 'Notification sent successfully!']);
    }
}
