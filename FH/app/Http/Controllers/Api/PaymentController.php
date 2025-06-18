<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\NotificationUser;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use Carbon\Carbon;
use App\Http\Controllers\Api\CRMController;
use App\Services\FirebasePushNotificationService;
use DB;

class PaymentController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebasePushNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    
    public function handleCallback(Request $request)
    {
        try {
            $validated = $request->validate([
                'status_id' => 'required|integer', // 1 for success, 0 for failure
                'order_id' => 'required|string',
                'transaction_id' => 'required|string',
                'msg' => 'required|string',
            ]);
            
            if (preg_match('/^(\d+)FH(\d+)$/', $validated['order_id'], $matches)) {
                $autoIncrementId = $matches[1]; // Auto-increment ID before "FH"
                $randomNo = $matches[2];        // Digits after "FH"
            } else {
                return response()->json(['error' => 'Invalid order ID format'], 400);
            }
    
            $userSubscription = UserSubscription::findOrFail($autoIncrementId);
    
            Log::info('UserSubscription Retrieved:', $userSubscription->toArray());
            Log::info('User Retrieved:', $userSubscription->user->toArray());
            
            $payment = Payment::where('random_no', $randomNo)->where('user_id', $userSubscription->user_id)->firstOrFail();
    
            //$payment = new Payment();
            $payment->user_id = $userSubscription->user_id; // use an existing user ID
            $payment->subscription_id = $userSubscription->subscription_id; // use an existing subscription ID
            $payment->transaction_id = $validated['transaction_id'];
            //$payment->amount = $userSubscription->amount;
            $payment->status = $validated['status_id'];
            //$payment->payment_method = 'SenangPay';
            $payment->payment_date = now();
            $payment->save();
            
            if ($validated['status_id'] == 1) {
                $userSubscription->is_paid = 1;
                $userSubscription->transaction_id = $validated['transaction_id'];
                $userSubscription->save();
                
                // Update Zoho CRM
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
    
                $zohoData = [
                    'data' => [
                        [
                            'is_paid' => 1,
                            'transaction_id' => $validated['transaction_id'],
                        ],
                    ],
                ];
    
                $module = 'User_Subscriptions';
                $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                $recordId = $userSubscription->zoho_record_id;
    
                if ($recordId) {
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put("$crmUrl/$recordId", $zohoData);
    
                    if ($response->successful()) {
                        return response()->json(['message' => 'Payment and CRM update successful'], 200);
                    } else {
                        Log::error('Zoho Update Failed', ['response' => $response->json()]);
                        return response()->json(['message' => 'Payment successful but CRM update failed'], 200);
                    }
                } else {
                    return response()->json(['message' => 'Payment successful but no Zoho record found'], 200);
                }
            } else {
                return response()->json(['message' => 'Payment failed', 'reason' => $validated['msg']], 400);
            }
    
        } catch (\Exception $e) {
            Log::error('Callback Processing Error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'An error occurred while processing the callback'], 500);
        }
    }
    
    public function testSavePayment()
    {
        $payment = new Payment();
        $payment->user_id = 1; // use an existing user ID
        $payment->subscription_id = 1; // use an existing subscription ID
        $payment->transaction_id = 'test_transaction_id';
        $payment->amount = 100.00;
        $payment->status = 'success';
        $payment->payment_method = 'Test Method';
        $payment->payment_date = now();
        
        if ($payment->save()) {
            return response()->json(['message' => 'Payment saved successfully']);
        } else {
            return response()->json(['error' => 'Failed to save payment']);
        }
    }



    public function paymentReturn(Request $request)
    {
        return response()->json(['message' => 'Payment return received'], 200);
    }
}
