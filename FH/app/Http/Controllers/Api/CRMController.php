<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\UserSubscription;
use App\Models\User; 
use App\Models\Registration;
use App\Models\Member;
use App\Models\Driver;
use Illuminate\Support\Facades\Log;
use App\Models\SubscriptionMaster;
use Carbon\Carbon;

class CRMController extends Controller
{
    public function getDependents(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'user_id' => 'required|exists:users,id', // Ensure user_id exists in users table
        ]);

        $userId = $request->input('user_id');

        $dependents = UserSubscription::where('referral_id', $userId)
            ->where('is_dependent', 1)
            ->where('reg_id', '=', NULL)
            ->get();
            
         $dependentsCount = $dependents->count();

        if ($dependentsCount === 0) {
            return response()->json([
                'message' => 'No dependents found for the provided user ID.',
                'dependents_count' => $dependentsCount,
                'dependents' => [],
            ], 404);
        }

        $dependentDetails = $dependents->map(function ($dependent) {
            return Registration::where('id', $dependent->user_id)->first([
                'id', 'first_name','first_name', 'email', 'phone_number', 'address',
            ]);
        });

        return response()->json([
            'message' => 'Dependents retrieved successfully.',
            'dependents_count' => $dependentsCount,
            'dependents' => $dependentDetails,
        ], 200);
    }
    
    public function getManualDependent(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'user_id' => 'required|exists:users,id', 
        ]);

        $userId = $request->input('user_id');

        $dependents = UserSubscription::where('referral_id', $userId)
            ->where('is_dependent', 1)
            ->where('reg_id', '!=', NULL)
            ->get();
            
         $dependentsCount = $dependents->count();

        if ($dependentsCount === 0) {
            return response()->json([
                'message' => 'No dependents found for the provided user ID.',
                'dependents_count' => $dependentsCount,
                'dependents' => [],
            ], 404);
        }

        $dependentDetails = $dependents->map(function ($dependent) {
            return Registration::where('id', $dependent->reg_id)->first([
                'id', 'first_name','first_name', 'email', 'phone_number', 'address',
            ]);
        });

        return response()->json([
            'message' => 'Dependents retrieved successfully.',
            'dependents_count' => $dependentsCount,
            'dependents' => $dependentDetails,
        ], 200);
    }
    
    public function Additionalcharge(Request $request)
    {
        $request->validate([
            'id' => 'sometimes|exists:additional_charges,id', // Validate ID if present
            'title' => 'required',
            'range_limit' => 'required',
            'price' => 'required|numeric',
        ]);
    
        if ($request->has('id')) {
            $additionalCharge = AdditionalCharge::find($request->id); // Replace with your model name
            $additionalCharge->title = $request->title;
            $additionalCharge->range_limit = $request->range_limit;
            $additionalCharge->price = $request->price;
            $additionalCharge->save();
    
            return response()->json([
                'success' => true,
                'message' => 'Additional charge updated successfully',
                'data' => $additionalCharge,
            ], 200);
        } else {
            $additionalCharge = new AdditionalCharge(); // Replace with your model name
            $additionalCharge->title = $request->title;
            $additionalCharge->range_limit = $request->range_limit;
            $additionalCharge->price = $request->price;
            $additionalCharge->save();
    
            return response()->json([
                'success' => true,
                'message' => 'Additional charge added successfully',
                'data' => $additionalCharge,
            ], 201);
        }
    }

    
   public function hotCalltoCRM(Request $request)
    {
        $request->validate([
            'current_lat' => 'required|numeric',
            'current_long' => 'required|numeric',
        ]);
    
        $user = Auth::user();
        
        if($user->driver_id == NULL)
        {
            $regDetails = Registration::where('id', $user->reg_id)->first();
            
            $userlogin = User::where('id', $user->id)->first();
    
            if (!$regDetails) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration details not found.',
                ], 404);
            }
            
            $dob = Carbon::parse($regDetails->dob);
            $age = $dob->age;
            
            $membership = "";
            
            $prim_userSubscription = UserSubscription::where('user_id', $user->id)
                ->where('is_dependent', 0)
                ->where('referral_no', '!=', NULL)
                ->first();
                
            $dep_userSubscription = UserSubscription::where('referral_id', $user->id)
                ->where('is_dependent', 1)
                ->where('reg_id', '!=', NULL)
                ->first();
                
            $subscriptionId = $prim_userSubscription->subscription_id ?? $dep_userSubscription->subscription_id ?? null;
    
            if ($subscriptionId) {
                $subscriptionMaster = SubscriptionMaster::where('id', $subscriptionId)->first();
                if ($subscriptionMaster) {
                    $membership = $subscriptionMaster->plan; 
                }
            }
            
            $currentLat = $request->current_lat;
            $currentLong = $request->current_long;
            $currentLocation = $this->getLocationName($currentLat, $currentLong);
            
           // dd($currentLocation);
        
           $zohoData = [
                'data' => [
                    [
                        'User_Id' => $user->id,
                        'Name' => $regDetails->first_name . ' ' . $regDetails->last_name. ' | '. $userlogin->email,
                        'Email' => $regDetails->email,
                        'Phone_no' => $regDetails->phone_number,
                        'Registered_lat' => $regDetails->latitude,
                        'Registered_long' => $regDetails->longitude,
                        'Registered_location' => $regDetails->address,
                        'Current_location' => $currentLocation,
                        'Tag' => "Premium",
                        'Age' => $age,
                        'Current_lat' => $request->current_lat,
                        'Current_long' => $request->current_long,
                        'Membership' => $membership,
                        'Action' => "Not Scheduled",
                    ]
                ]
            ];
    
            $accessToken = $this->getZohoAccessToken();
        
            $module = 'Calling_Users';
            
            $response = Http::withHeaders([
                'Authorization' => "Zoho-oauthtoken $accessToken",
                'Content-Type' => 'application/json',
            ])->post("https://www.zohoapis.com/crm/v2/$module", $zohoData);
        }
        else
        {
            
            /*driver call to CRM*/
            
            $regDetails = Driver::where('user_id', $user->id)->first();
    
            if (!$regDetails) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration details not found.',
                ], 404);
            }
            
           
            $currentLat = $request->current_lat;
            $currentLong = $request->current_long;
            $currentLocation = $this->getLocationName($currentLat, $currentLong);
            
           // dd($currentLocation);
        
           $zohoData = [
                'data' => [
                    [
                        'User_ID' => $user->id,
                        'Name' => $regDetails->name,
                        'Email' => $regDetails->email,
                        'Phone_Number' => $regDetails->phone_number,
                        'Current_Location' => $currentLocation,
                    ]
                ]
            ];
            
            $crmController = new CRMController();
    
            $accessToken = $crmController->getZohoAccessToken();
        
            $module = 'Calling_Drivers';
            
            $response = Http::withHeaders([
                'Authorization' => "Zoho-oauthtoken $accessToken",
                'Content-Type' => 'application/json',
            ])->post("https://www.zohoapis.com/crm/v2/$module", $zohoData);
        }

       
        // Parse and check the response
        if ($response->successful()) {
            $responseData = $response->json();
    
            if (!empty($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
                return response()->json([
                    'success' => true,
                    'message' => 'Data inserted into Zoho CRM successfully!',
                    'record_id' => $responseData['data'][0]['details']['id'] ?? null, // Record ID if available
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Data not inserted into Zoho CRM.',
                    'details' => $responseData,
                ], 500);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to Zoho CRM.',
                'error' => $response->body(),
            ], 500);
        }
    }
    
    private function getLocationName($latitude, $longitude)
    {
        $apiKey = "AIzaSyCcnRShoTxqZ8ZufrDkjjZ29awLWOQ5vUM"; // Ensure you have added this key to your .env file
        $url = "https://maps.googleapis.com/maps/api/geocode/json";
    
        $response = Http::get($url, [
            'latlng' => "$latitude,$longitude",
            'key' => $apiKey,
        ]);
        
        //dd($response->json());
    
        Log::info('Geocoding API Response:', $response->json());
        
        if ($response->successful()) {
            $data = $response->json();
    
            if (isset($data['results'][0]['formatted_address'])) {
                return $data['results'][0]['formatted_address']; // The location name
            }
        }
    
        return 'Unknown Location';
    }

    
   
    private function calculateAge($dob)
    {
        if (!$dob) {
            return null; 
        }
        return \Carbon\Carbon::parse($dob)->age;
    }
    
    public function getZohoAccessToken()
    {
        // $clientId = env('ZOHO_CLIENT_ID');
        // $clientSecret = env('ZOHO_CLIENT_SECRET');
        // $redirectUri = env('ZOHO_REDIRECT_URI');
        // $refreshToken = env('ZOHO_REFRESH_TOKEN');
        
        $cachedToken = cache()->get('zoho_access_token');

        if ($cachedToken) {
            return $cachedToken;
        }
        
        $clientId = "1000.QNLCYYNYN2D209PRU9CAY42GVUKX4C";
        $clientSecret = "f20465b33c536ec11cce176fe927a633c7edc07206";
        $redirectUri = "http://stg-api.firsthealthassist.com/api/zoho/callback";
        $refreshToken = "1000.c8898dbccc5733a785f6d3418eed260d.16b8b0f5e56f09292344b05d74ef4519";
        
        //dd($clientId);
        
        if (!$clientId || !$clientSecret || !$redirectUri || !$refreshToken) {
            throw new \Exception("Zoho API credentials are not properly configured in the .env file.");
        }
    
        $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
        ]);
    
        if ($response->failed()) {
            throw new \Exception('Failed to retrieve access token from Zoho. Response: ' . $response->body());
        }
    
        $data = $response->json();
    
        if (!isset($data['access_token'])) {
            throw new \Exception('Access token not found in Zoho response. Response: ' . json_encode($data));
        }
    
        $accessToken = $data['access_token'];
        $expiresIn = $data['expires_in'] ?? 3600; // Default to 1 hour if not provided
    
        cache()->put('zoho_access_token', $accessToken, now()->addSeconds($expiresIn - 300));
    
        return $accessToken;
    }
    
    public function getUserGraphData()
    {
        $data = User::where('is_active', 1)->get();
        
        return response()->json($data);
    }

    
    

}

