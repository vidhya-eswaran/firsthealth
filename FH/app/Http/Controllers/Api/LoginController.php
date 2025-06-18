<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Registration;
use App\Models\NotificationUser;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\RoasterMapping;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Token as PassportToken;
use App\Http\Controllers\Api\CRMController;
use App\Http\Controllers\Controller;
use App\Services\FirebasePushNotificationService;



class LoginController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebasePushNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    public function getAllUsers()
    {
        $users = User::all(); // Fetches all records from the users table
        return response()->json($users); // Returns the data as JSON response
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'device_token' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
    
        $user = User::where('email', $request->email)->where('is_active', 1)->first();
    
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Incorrect password'], 401);
        }
    
        \Laravel\Passport\Token::where('user_id', $user->id)->update(['revoked' => true]);
    
        $user->device_token = $request->device_token;
        $user->save();
        
       // dd($user);
        
        if($user->driver_id == NULL)
        {
        
            if ($user->first_login) 
            {
                $userSubscription = UserSubscription::where('user_id', $user->id)->first();
                
                //dd($userSubscription);
    
                if ($userSubscription->free_plan == 0 && $userSubscription->is_removed == 0) {
                    $createdDate = $userSubscription->created_at->startOfDay();
                    $currentDate = now()->startOfDay();
                    $daysPassed = $createdDate->diffInDays($currentDate);
                    
                    //dd($daysPassed);
                
                   if ($daysPassed >= 0 && $daysPassed <= 14) {
                        if ($daysPassed === 0) {
                            $dayText = 'today';
                        } elseif ($daysPassed === 1) {
                            $dayText = 'yesterday';
                        } else {
                            $dayText = "$daysPassed days ago";
                        }
                    
                        $body = "Your 14-day qualifying period started {$dayText}. Thank you for being part of the First Health plan.";
                    
                        // Send notification (example logic below)
                        NotificationUser::create([
                            'form_user_id' => $user->id,
                            'to_user_id' => $user->id,
                            'title' => 'Qualifying Period Update',
                            'type' => 'notification',
                            'body' => $body,
                            'is_sent' => 1,
                            'created_by' => $user->id,
                        ]);
                    
                        //dd($user->device_token, $body);
                    
                        if ($user->device_token) {
                            $response = $this->firebaseService->sendNotification(
                                $user->device_token,
                                'Qualifying Period Update',
                                $body
                            );
                           // Log::info('Firebase Notification Response: ', ['response' => $response]);
                        }
                    }
    
                }
    
    
                // Update the flag
                $user->first_login = false;
                $user->save();
            }
        
        }
    
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
    
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }
    
        $token->save();
        
        $is_driver = "";
        
        if($user->driver_id != NULL)
        {
            $is_driver = true;
        }else{
            $is_driver = false;
        }

    
        $reg = Registration::where('id', $user->reg_id)->first(['address', 'address2', 'postcode', 'city', 'state', 'country', 'latitude', 'longitude']);
    
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'user' => $user,
            'registration' => $reg,
            'is_driver' => $is_driver,
            'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
        ]);
    }

    
//   public function resetPassword(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'password' => 'required|string|min:8|confirmed', // confirmed checks password_confirmation
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }

//         $user = Auth::user();

//         if (!$user) {
//             return response()->json(['error' => 'Unauthorized.'], 401);
//         }

//         $user->password = Hash::make($request->password);
//         $user->save();

//         return response()->json(['message' => 'Password reset successfully.'], 200);
//     }
    
    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $request->user()->token();
    
        $token->revoke();
        
        $user->device_token = null;
        $user->save();
        
        // if($user->driver_id != NULL)
        // {
        //      $driver = Driver::where('id', $user->driver_id)->first();
        //      if($driver)
        //      {
        //          $crmController = new CRMController();
        //          $accessToken = $crmController->getZohoAccessToken();
                 
        //           $zohoData = [
        //             'data' => [
        //                 [
        //                      'Status' => "Offline",
        //                 ],
        //             ],
        //         ];
            
        //         $module = 'Driver_Master';
        //         $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                
        //       // dd($zohoData);
            
        //         if ($driver->zoho_record_id) {
        //             // Update existing record
        //             $recordId = $driver->zoho_record_id;
        //             $response = Http::withHeaders([
        //                 'Authorization' => "Zoho-oauthtoken $accessToken",
        //                 'Content-Type' => 'application/json',
        //             ])->put("$crmUrl/$recordId", $zohoData);
        //         }
        //         //dd($response->json());
                    
        //         $todayDate = Carbon::now()->toDateString();
        
        //         // Fetch all roaster records for today
        //         $roasters = RoasterMapping::where('driver_id', $driver->id)
        //             ->whereDate('created_at', $todayDate)
        //             ->get();
        
        //         // Update driver status in roaster records
        //         RoasterMapping::where('driver_id', $driver->id)
        //             ->whereDate('created_at', $todayDate)
        //             ->update(['driver_status' => "Offline"]);
            
        //         // Update each Roaster record in Zoho CRM
        //         foreach ($roasters as $roaster) {
        //             if ($roaster->zoho_record_id) {
        //                 $zohoData1 = [
        //                     'data' => [
        //                         [
        //                             'Driver_Status' => "Offline",
        //                         ],
        //                     ],
        //                 ];
                        
        //                 $module1 = 'Roaster';
        //                 $crmUrl1 = "https://www.zohoapis.com/crm/v2/$module1";
            
        //                 Http::withHeaders([
        //                     'Authorization' => "Zoho-oauthtoken $accessToken",
        //                     'Content-Type' => 'application/json',
        //                 ])->put("$crmUrl1/{$roaster->zoho_record_id}", $zohoData1);
        //             }
        //         }
        //      }
        // }
    
        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
    
    public function deactivate(Request $request)
    {
        $user = Auth::user(); // Get the currently authenticated user

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $user->is_active = false;
        $user->save();

        $user->token()->revoke();

        return response()->json(['message' => 'Account closed successfully'], 200);
    }
    
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json(['error' => 'This email ID is not registered'], 404);
        }
    
        if ($user->is_active == 0) {
            return response()->json(['error' => 'This email ID is deactivated'], 403);
        }

        $token = Password::createToken($user);

        $resetUrl = 'reset-password?token=' . $token . '&email=' . $user->email;
        
        //dd($resetUrl);

        // Send email with the deep link
        Mail::send('emails.resetPassword', ['resetUrl' => $resetUrl], function($message) use ($user) {
            $message->to($user->email);
            $message->subject('Reset Password');
        });

        return response()->json(['message' => 'Password reset link sent successfully'], 200);
    }
   public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
    
        // Check if new password is same as old password
        if (Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'New password cannot be the same as the old password.'], 400);
        }
    
        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );
    
        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully'], 200);
        } else {
            // Log the status for debugging
            \Log::error('Password reset failed with status: ' . $status);
            return response()->json(['error' => 'Invalid token or email'], 400);
        }
    }
    
   public function driver_availability(Request $request)
    {
        $user = Auth::user();
    
        $validator = Validator::make($request->all(), [
            'current_lat'  => 'required',
            'current_long' => 'required',
            //'address'      => 'nullable',
            'status'       => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
    
        // Update driver's location and status
        $driver = Driver::where('user_id', $user->id)->first();
    
        if (!$driver) {
            return response()->json(['error' => 'Driver not found'], 404);
        }
        
        //dd($request->active);
    
        $driver->update([
            'current_lat'  => $request->current_lat,
            'current_long' => $request->current_long,
           // 'address'      => $request->address,
            'status'       => $request->status
        ]);
        
                $crmStatus = 'success';
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
        
                $zohoData = [
                    'data' => [
                        [
                            'Status' => "Online",
                        ],
                    ],
                ];
        
                $module = 'Driver_Master';
                $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
        
                if ($driver->zoho_record_id) {
                    $recordId = $driver->zoho_record_id;
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put("$crmUrl/$recordId", $zohoData);
        
                    if ($response->successful()) {
                        $crmResponse = $response->json();
                        if (!empty($crmResponse['data'][0]['code']) && $crmResponse['data'][0]['code'] === 'SUCCESS') {
                            $crmStatus = 'success';
                          
                        } else {
                            $crmStatus = 'failed';
                        }
                    } else {
                         $crmStatus = 'failed';
                    }
                } else {
                     $crmStatus = 'failed';
                }
    
        return response()->json([
            'message' => 'Driver availability updated successfully',
            'driver'  => $driver,
            'crmstatus' => $crmStatus
        ], 200);
    }



    public function changePassword(Request $request)
    {
        // Validate request
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8',
        ]);
    
        // Get the authenticated user
        $user = Auth::user();
    
        // Check if the old password is correct
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Old password is incorrect', 'status' => false], 400);
        }
    
        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();
    
        return response()->json(['message' => 'Password changed successfully', 'status' => true], 200);
    }



}
