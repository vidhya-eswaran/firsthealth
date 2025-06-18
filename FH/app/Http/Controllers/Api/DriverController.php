<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

use App\Mail\DriverCredentialsMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Mime\Email;
use Illuminate\Http\Request;

use App\Models\InviteUser;
use App\Models\TripStatusLog;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Paramedic;
use App\Models\Hospital;
use App\Models\RoasterMapping;
use App\Models\NotificationUser;
use App\Models\Ambulance;
use App\Models\DriverDeclinedReason;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\CRMController;
use App\Services\FirebasePushNotificationService;

use App\Jobs\DeleteZohoDistanceRecords;


class DriverController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebasePushNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    
    public function store(Request $request)
    {
        $driverData = $request->only([
           'name',
           'email',
            'phone_number',
            //'id_proof',
            'license_number',
            'hospital_name',
            'hospital_id',
            'current_lat',
            'current_long',
            'license_issue_date',
            'license_valid_from',
            'license_valid_upto',
            'driver_country_code',
            //'guarantor_country_code',
           // 'rfid_tracking_id',
            'status',
            'vehicle_number',
            'passport_number',
            'zoho_record_id',
            'user_id'
        ]);
    
        $driverData['license_issue_date'] = isset($driverData['license_issue_date']) && !empty($driverData['license_issue_date']) 
            ? Carbon::createFromFormat('d-m-Y', $driverData['license_issue_date'])->format('Y-m-d') 
            : null;
        
        $driverData['license_valid_from'] = isset($driverData['license_valid_from']) && !empty($driverData['license_valid_from']) 
            ? Carbon::createFromFormat('d-m-Y', $driverData['license_valid_from'])->format('Y-m-d') 
            : null;
        
        $driverData['license_valid_upto'] = isset($driverData['license_valid_upto']) && !empty($driverData['license_valid_upto']) 
            ? Carbon::createFromFormat('d-m-Y', $driverData['license_valid_upto'])->format('Y-m-d') 
            : null;


        $driver = Driver::create($driverData);
    
        $defaultPassword = 'Driver@' . substr($driver->phone_number, -4); // Example: Driver@1234

        //dd($driver->id);
        // Insert into User table
        $user = User::create([
            'name' => $driver->name,
            'email' => $driver->email,
            'password' => Hash::make($defaultPassword), // Hash the password
            'driver_id' => $driver->id,
        ]);
        
        $driver->update(['user_id' => $user->id]);
        
        Mail::to($user->email)->send(new DriverCredentialsMail($user->email, $defaultPassword));

        
            $crmController = new CRMController();
            $accessToken = $crmController->getZohoAccessToken();
            
            $zohoData = [
                'data' => [
                    [
                        //'u_id' => $user->id,
                        'driver_id' => $driver->id,
                        'Name' => $driver->name,
                        'Email' => $driver->email,
                    ]
                ]
            ];
    
            $module = 'FHUser';  
            
          // dd($accessToken);
    
            $response = Http::withHeaders([
                'Authorization' => "Zoho-oauthtoken $accessToken",
                'Content-Type' => 'application/json',
            ])->post("https://www.zohoapis.com/crm/v2/$module", $zohoData);
    
        return response()->json([
            'success' => true,
            'data' => $driver,
            'user' => $user,
            'default_password' => $defaultPassword, // Optionally return password for admin reference
        ], 201);
    }
    
    public function get($id)
    {
        $driver = Driver::findOrFail($id);
    
        return response()->json(['success' => true, 'data' => $driver]);
    }

    public function saveVehicle(Request $request)
    {
        $vehicle = Vehicle::updateOrCreate(
            ['id' => $request->id], 
            ['type' => $request->type,
            'status' => $request->status ?? 'available',
          //  'hospital_id' => $request->hospital_id , 
           // 'hospital_name' => $request->hospital_name, 
            'vehicle_number' => $request->vehicle_number,
            'vehicle_name' => $request->vehicle_name,
            'ambulance_life_support' => $request->ambulance_life_support]
        );

        return response()->json(['message' => 'Vehicle saved successfully', 'data' => $vehicle], 200);
    }

    // Insert or Update Paramedic
    public function saveParamedic(Request $request)
    {
        $paramedic = Paramedic::updateOrCreate(
            ['hospital_id' => $request->hospital_id], 
            ['name' => $request->name, 'phone_number' => $request->phone_number, 'status' => $request->status ?? 'active', 'hospital_name' => $request->hospital_name]
        );

        return response()->json(['message' => 'Paramedic saved successfully', 'data' => $paramedic], 200);
    }

    // Insert or Update Hospital
    public function saveHospital(Request $request)
    {
        $hospital = Hospital::updateOrCreate(
            ['id' => $request->id], // Condition to check if hospital exists
            [
                'name' => $request->name, 
                'address' => $request->address ?? '', 
                'phone_number' => $request->phone_number, 
                'latitude'  => $request->latitude, 
                'longitude' => $request->longitude
            ]
        );
       

        return response()->json(['message' => 'Hospital saved successfully', 'data' => $hospital], 200);
    }
    

    public function updateProfile(Request $request)
    {
        //dd("SSS");
        $user = Auth::user();
    
        $driver = Driver::where('user_id', $user->id)->first();
    
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
        
        //dd("SSS");
    
        $request->validate([
            'name' => 'nullable|string|max:255',
            //'email' => 'nullable|email',
            'phone_number' => 'nullable|string|max:20',
            //'id_proof' => 'nullable|string',
            'license_number' => 'nullable|string|max:50',
            //'vehicle_number' => 'nullable|string|max:50',
            //'shift' => 'nullable|string|max:50'
        ]);
        
       // dd("SSS");
    
        $driver->update($request->only([
            'name',
           // 'email',
            'phone_number',
            //'id_proof',
            'license_number',
            //'vehicle_number',
            //'shift'
        ]));
    
        return response()->json(['message' => 'Profile updated successfully', 'driver' => $driver], 200);
    }
    
    public function getDriverProfile()
    {
        //dd("ssssssssssss");
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        $roaster = RoasterMapping::where('driver_id', $driver->id) ->orderBy('id', 'DESC')->first();
        
        if ($roaster) {
           
            $driver['shift'] = $roaster->shift;
            
            $vehicles = Vehicle::where('id' , $roaster->vehicle_id)->first();
            if ($vehicles) {
                $driver['vehicle_name'] = $vehicles->vehicle_name;
                $driver['vehicle_number'] = $vehicles->vehicle_number;
            } else {
                $driver['vehicle_name'] = null;
                $driver['vehicle_number'] = null;
            }
        
        } else {
            $driver['shift'] = null; // Or any default value
        }

        if (!$driver) {
            return response()->json(['message' => 'Driver profile not found'], 404);
        }

        return response()->json(['driver' => $driver], 200);
    }
    
    public function updateDriverStatus(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'status' => 'required|string',
            'current_lat'=> 'required',
            'current_long'=> 'required',
            // Adjust statuses as needed
        ]);
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
    
        $driver->status = $request->status;
        $driver->current_lat = $request->current_lat;
        $driver->current_long = $request->current_long;
        $driver->save();
        
          //dd($driver);
        
            $crmController = new CRMController();
            $accessToken = $crmController->getZohoAccessToken();
                
               // dd($startDate);
            
                $zohoData = [
                    'data' => [
                        [
                             'Status' => $request->status,
                             'Current_Latitude' => json_encode($request->current_lat),
                             'Current_Longitude' => json_encode($request->current_long),
                        ],
                    ],
                ];
            
                $module = 'Driver_Master';
                $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                
               // dd($zohoData);
            
                if ($driver->zoho_record_id) {
                    // Update existing record
                    $recordId = $driver->zoho_record_id;
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put("$crmUrl/$recordId", $zohoData);
                }
            //dd($response->json());
                
            $todayDate = Carbon::now()->toDateString();
    
            // Fetch all roaster records for today
            $roasters = RoasterMapping::where('driver_id', $driver->id)
                ->whereDate('created_at', $todayDate)
                ->get();
    
            // Update driver status in roaster records
            RoasterMapping::where('driver_id', $driver->id)
                ->whereDate('created_at', $todayDate)
                ->update(['driver_status' => $request->status]);
        
            // Update each Roaster record in Zoho CRM
            foreach ($roasters as $roaster) {
                if ($roaster->zoho_record_id) {
                    $zohoData1 = [
                        'data' => [
                            [
                                'Driver_Status' => $request->status,
                            ],
                        ],
                    ];
                    
                    $module1 = 'Roaster';
                    $crmUrl1 = "https://www.zohoapis.com/crm/v2/$module1";
        
                    Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put("$crmUrl1/{$roaster->zoho_record_id}", $zohoData1);
                }
            }
     
    
        return response()->json([
            'status' => true,
            'message' => 'Driver status updated successfully',
            'driver' => $driver
        ]);
    }
    
    public function updateRideStatus(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'ride_status' => 'required|string', // Adjust statuses as needed
            'trip_id' => 'required',
        ]);
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
                
            $todayDate = Carbon::now()->toDateString();
    
            $roasters = RoasterMapping::where('driver_id', $driver->id)
                ->whereDate('created_at', $todayDate)
                ->get();
    
            RoasterMapping::where('driver_id', $driver->id)
                ->whereDate('created_at', $todayDate)
                ->update(['ride_status' => $request->ride_status]);
                
            $assignambulance = Ambulance::where('id', $request->trip_id)->first();
            
            $crmController = new CRMController();
            $accessToken = $crmController->getZohoAccessToken();
            
             /*make the roaster record as busy for online*/
                
            $roasters = RoasterMapping::where('driver_id', $driver->id)->get();
            
            $title = "";
            $body = "";
            
            if($request->ride_status == "Dropped Off" || $request->ride_status == "Complete")
            {
                $driverStatus = "Online";
            }else{
                $driverStatus = "Busy";
            }
            foreach ($roasters as $roaster) {
                if ($roaster->zoho_record_id) {
                        $relatedRecordId = $this->getZohoRideStatusId($request->ride_status, $accessToken);
                        $zohoData1 = [
                            'data' => [
                                [
                                    'Driver_Status' => $driverStatus,
                                    'Ride_Status' => [
                                        'id' => $relatedRecordId, // Pass the ID of the related record
                                    ],
                                ],
                            ],
                        ];
                        
                        $module1 = 'Roaster';
                        $crmUrl1 = "https://www.zohoapis.com/crm/v2/$module1";
            
                        $response  = Http::withHeaders([
                            'Authorization' => "Zoho-oauthtoken $accessToken",
                            'Content-Type' => 'application/json',
                        ])->put("$crmUrl1/{$roaster->zoho_record_id}", $zohoData1);
                }
            }
            
            
            $hospital = "";
            if($request->ride_status == "Picked Up")
            {
                $hospital = Hospital::where('id', $assignambulance->hospital_id)->select('latitude', 'longitude', 'address')->first();
            }
            
            if($request->ride_status == "Dropped Off")
            {
                 /*make the driver as online*/
                if ($driver->zoho_record_id) {
                    $zohoData = [
                        'data' => [
                            [
                                 'Status' => "Online",
                            ],
                        ],
                    ];
                
                    $module = 'Driver_Master';
                    $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                
                    $recordId = $driver->zoho_record_id;
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put("$crmUrl/$recordId", $zohoData);
                }
                
               // dd($response->json());
            }
           
            
            $assignambulance->trip_status = $request->ride_status;
            $assignambulance->save();
            
            if($request->ride_status == "Complete")
            {
                // /*Reduce call count in subscription table*/
                
                // $userSubscription = UserSubscription::where('user_id', $assignambulance->user_id)->first();
                // if($userSubscription)
                // {
                //     $createdDate = Carbon::parse($userSubscription->start_date)->startOfDay();
                //     $currentDate = now();
                //     $daysPassed = $createdDate->diffInDays($currentDate);
                //     $qualifyingPeriod = 14;
                    
                //     $isWithinQualifyingPeriod = $daysPassed <= $qualifyingPeriod;
                    
                //     //dd($isWithinQualifyingPeriod);
                    
                //     if (!$isWithinQualifyingPeriod || $userSubscription->plan_times > 1) 
                //     {
                      
                //         if ($assignambulance->trip == "Emergency Trip") {
                //             if ($userSubscription->r_emergency_calls > 0) {
                //                 $userSubscription->r_emergency_calls -= 1;
                //             }
                //         } else {
                //             if ($userSubscription->r_clinic_calls > 0) {
                //                 $userSubscription->r_clinic_calls -= 1;
                //             }
                //         }
                
                //         $userSubscription->save(); // Save after decrementing
                //     }
                    
                // }
                
                $title = "Destination Reached";
                $body = "You’ve arrived safely at your destination with First Health. Thank you for choosing us.";
                /*Driver and Hospital distance record delete in CRM*/
               
                DeleteZohoDistanceRecords::dispatch(
                    $assignambulance->user_id,
                    $assignambulance->driver_id,
                    $assignambulance->hospital_id,
                    $accessToken
                );
                
              
            }
            
          
            if ($assignambulance->zoho_record_id) {
                $relatedRecordId = $this->getZohoRideStatusId($request->ride_status, $accessToken);
            
                $zohoData = [
                        'data' => [
                            [
                                'Trip_Status' => [
                                    'id' => $relatedRecordId, // Pass the ID of the related record
                                ],
                            ],
                        ],
                    ];
            
                    $module1 = 'AssignAmbulances';
                    $crmUrl1 = "https://www.zohoapis.com/crm/v2/$module1";
            
                Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                ])->put("$crmUrl1/{$assignambulance->zoho_record_id}", $zohoData);
            }
            
            $lastStatusLog = TripStatusLog::where('trip_id', $assignambulance->id)
                ->orderBy('status_updated_at', 'desc')
                ->first();
            
            $now = Carbon::now();
            
            // Calculate time taken for previous status
            $timeTaken = null;
            if ($lastStatusLog) {
                $timeTaken = $now->diffInSeconds(Carbon::parse($lastStatusLog->status_updated_at));
                $lastStatusLog->time_taken = $timeTaken;
                $lastStatusLog->save();
            }
            
            // Insert new log
            TripStatusLog::create([
                'trip_id' => $assignambulance->id,
                'status' => $request->ride_status,
                'status_updated_at' => $now,
                'time_taken' => null, // will be filled when next status comes in
            ]);
            
            $user = \DB::table('users')->where('id', $assignambulance->user_id)->first();
            
            if($request->ride_status == "Arrived")
            {
                $title = "Ambulance Has Arrived";
                $body = "The ambulance has arrived at your location. Please board now.";
            }
            if($request->ride_status == "On the way")
            {
                $title = "Ambulance is on the Way";
                $body = "The ambulance is on its way to your location. You can track its location now.";
            }
            
            if($user && $user->device_token){
                       
                $userName = $user->name;
                $activity = $request->ride_status;
                
               // $body =  "Ambulance is $activity..";
        
                            // Create the main notification
                 NotificationUser::create([
                                'form_user_id' => $user->id,
                                'to_user_id' => $user->id,
                                'type' => 'notification',
                                'title' => $title,
                                'body' => $body,
                                'is_sent' => 1,
                                'created_by' => $user->id,
                            ]);
        
                            // Send notification via Firebase with a unique collapse key
                $this->firebaseService->sendNotification($user->device_token, $title, $body, ['collapse_key' => 'referral_response']);
            }
                
        return response()->json([
            'status' => true,
            'current_status' => $request->ride_status,
            'hospital' => $hospital
        ]);
    }
    
    private function getZohoRideStatusId($rideStatusName, $accessToken)
    {
        
        // $crmUrl = "https://www.zohoapis.com/crm/v2/settings/modules";

        // $response = Http::withHeaders([
        //     'Authorization' => "Zoho-oauthtoken $accessToken",
        //     'Content-Type' => 'application/json',
        // ])->get($crmUrl);
        
        // dd($response->json());
        $module = 'Driver_Status'; 
        $crmUrl = "https://www.zohoapis.com/crm/v2/$module/search?criteria=(Name:equals:$rideStatusName)";
        
        //dd($crmUrl);
        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken $accessToken",
            'Content-Type' => 'application/json',
        ])->get($crmUrl);
        
       // dd($response->json());

    
        $data = $response->json();
    
        if (!empty($data['data'][0]['id'])) {
            return $data['data'][0]['id']; // Return the ID of the matched Ride_Status
        }
    
        return null;
    }
    
    
    public function DriverCalltoCRM(Request $request)
    {
        $request->validate([
            'current_lat' => 'required|numeric',
            'current_long' => 'required|numeric',
        ]);
    
        $user = Auth::user();
    
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
    
    
    
    public function driverDecline(Request $request)
    {
        $request->validate([
            'declined_reason' => 'nullable|string',
            'trip_id' => 'required|integer'
        ]);

        $user = Auth::user();
        
        $driver_details = Driver::where('user_id', $user->id)->first();
        

        if (!$user || !$user->driver_id) {
            return response()->json(['error' => 'Driver ID not found for logged-in user'], 404);
        }
        
        $crmController = new CRMController();
            
        $accessToken = $crmController->getZohoAccessToken();
        
        if($request->declined_reason){
            
            $reasons = DriverDeclinedReason::create([
            'driver_id' => $user->driver_id, 
            'driver_name' => $user->name,    
            'phone_number' => $driver_details->phone_number, 
            'declined_reason' => $request->declined_reason,
            'trip_id' => $request->trip_id,
            ]);
            
            $relatedRecordId = $this->getZohoAmbulanceId($request->trip_id, $accessToken);
                        
            //dd($relatedRecordId);
                       
            
            $zohoData = [
                'data' => [
                    [
                       // 'Driver_Declined_Reason' => $request->declined_reason,
                        'Name' =>  $request->declined_reason,
                        'Driver_Name' => $user->name,
                        'Driver_Contact_Number' => $driver_details->phone_number,
                        'Driver_Id' => $user->driver_id,
                        'Trip_Id' => $request->trip_id,
                        'Assign_Ambulance' => [
                            'id' => $relatedRecordId  // ✅ Changed to 'name' for lookup
                        ],
                    ]
                ]
            ];
            
            //dd($driver_details);
        
            $module = 'Driver_Declined_Reason';
            
            $response = Http::withHeaders([
                'Authorization' => "Zoho-oauthtoken $accessToken",
                'Content-Type' => 'application/json',
            ])->post("https://www.zohoapis.com/crm/v2/$module", $zohoData);
        }
        
        $ambulance = Ambulance::where('id', $request->trip_id)->where('driver_id', $driver_details->id)->first();
        
        if ($ambulance) {
            $ambulance->increment('decline_count'); // Increase the count by 1
            $ambulance->assigned_trip_status = "Decline";
            $ambulance->save();
        }
        
        //dd($ambulance);
        
        
        if($request->declined_reason)
        {
            $declinecount = DriverDeclinedReason::where('driver_id', $driver_details->id)->where('trip_id', $request->trip_id)->count();
        }else{
            $declinecount = 1;
        }
        
        // dd($declinecount);
        
         $accessToken1 = $crmController->getZohoAccessToken();
        
         $zohoData1 = [
                    'data' => [
                        [
                             'Assigned_Trip_Status' => "Decline",
                             'Decline_Count' => $declinecount
                        ],
                    ],
                ];
            
                $module = 'AssignAmbulances';
                $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                
              // dd($zohoData1);
            
                if ($ambulance->zoho_record_id) {
                    // Update existing record
                    $recordId = $ambulance->zoho_record_id;
                    $response1 = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken1",
                        'Content-Type' => 'application/json',
                    ])->put("$crmUrl/$recordId", $zohoData1);
                }
       // dd($response1);
        if ($response1->successful()) {
            $responseData = $response1->json();
    
            if (!empty($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
                
                //$reasons->zoho_record_id = $responseData['data'][0]['details']['id'];
                //$reasons->save();
                
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
                'error' => $response1->body(),
            ], 500);
        }

        return response()->json(['message' => 'Declined reason added successfully'], 201);
    }
    
    
    private function getZohoAmbulanceId($AmbulanceID, $accessToken)
    {
        
        $module = 'AssignAmbulances'; 
        $crmUrl = "https://www.zohoapis.com/crm/v2/$module/search?criteria=(S_No:equals:$AmbulanceID)";
        
        //dd($crmUrl);
        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken $accessToken",
            'Content-Type' => 'application/json',
        ])->get($crmUrl);
        
       //dd($response->json());

    
        $data = $response->json();
    
        if (!empty($data['data'][0]['id'])) {
            return $data['data'][0]['id']; // Return the ID of the matched Ride_Status
        }
    
        return null;
    }

    public static function getDriverStatus(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
        
        return response()->json([
            'status' => true,
            'driver_status' => $driver->status
        ]);
        
        
    }
    
    public static function getTripStatus(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
        
        $todayDate = Carbon::now()->toDateString();
    
        $roasters = RoasterMapping::where('driver_id', $driver->id)
                ->whereDate('created_at', $todayDate)
                ->first();
                
        if($roasters)
        {
            $ride_status = $roasters->ride_status;
        }else{
            $ride_status = "NULL";
        }
         
        
        return response()->json([
            'status' => true,
            'Trip_status' => $ride_status
        ]);
        
        
    }
    
    public static function driverTripHistory(Request $request){
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
        
      
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'trip_status' => 'nullable|string'
        ]);
    
        $query = Ambulance::where('driver_id', $driver->id);
    
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        } elseif ($request->filled('start_date')) {
            $query->where('created_at', '>=', Carbon::parse($request->start_date));
        } elseif ($request->filled('end_date')) {
            $query->where('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
        }
    
        if ($request->filled('trip_status')) {
            $query->where('trip_status', $request->trip_status);
        }
    
        $ambulances = $query->orderBy('created_at', 'desc')->get();
        
        $userIds = $ambulances->pluck('user_id')->unique()->toArray();
        
        $userSubscriptions = UserSubscription::whereIn('user_id', $userIds)->pluck('referral_no', 'user_id'); 
        
        foreach ($ambulances as $ambulance) {
            $ambulance->referral_no = $userSubscriptions[$ambulance->user_id] ?? null;
        }
    
        return response()->json([
            'status' => true,
            'trip_details' => $ambulances
        ]);
    }

    public function uploadPCRFile(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:jpg,jpeg,png,pdf|max:2048', 
            'trip_id' => 'required'
        ]);
        
        $user = Auth::user();
        
        //dd($user);
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
        
        $filePath = $request->file('file')->store('uploads', 'public');
        
        $publicFilePath = url('api/download-file/' . basename($filePath));
        
        $ambulance = Ambulance::where('id', $request->trip_id)->where('driver_id', $driver->id)->first();
        
       //dd($request->trip_id, $driver->id, $ambulance);
        
        $ambulance->pcr_file = $filePath;
        $ambulance->save();
        
       // dd($ambulance);
        
       if ($ambulance->zoho_record_id) {
            $crmController = new CRMController();
            $accessToken = $crmController->getZohoAccessToken();
    
            $module = "AssignAmbulances";
            $recordId = $ambulance->zoho_record_id;
           
            /*===============================================*/
            
            $updateUrl = "https://www.zohoapis.com/crm/v2/$module/$recordId";

            $updateData = [
                'data' => [
                    [
                        'PCR_File' => $publicFilePath, 
                    ],
                ],
            ];
            
            $response = Http::withHeaders([
                'Authorization' => "Zoho-oauthtoken $accessToken",
                'Content-Type'  => 'application/json',
            ])->put($updateUrl, $updateData);
            
            $zohoResponse = $response->json();

            if ($response->successful() && isset($zohoResponse['data'][0]['status']) && $zohoResponse['data'][0]['status'] === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'PCR file successfully uploaded to Zoho CRM',
                    'zoho_response' => $zohoResponse,
                ], 200);
            }
            
            // If not successful, return error response
            return response()->json([
                'error' => 'File upload to Zoho CRM failed',
                'zoho_response' => $zohoResponse,
            ], 400);


        }
        return response()->json([
            'success' => true,
            'file_url' => asset('storage/' . $filePath),
            'zoho_response' => $response ?? null,
            'data' => $ambulance
        ], 201);
    }
    
    public static function DriverTripCount(Request $request)
    {
        $user = Auth::user();
        
        //dd($user);
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
        
        $ambulance_total = Ambulance::where('driver_id', $driver->id)->count();
        
        $ambulance_not = Ambulance::where('driver_id', $driver->id)->where('trip_status', "Complete")->count();
        
        return response()->json([
            'success' => true,
            'total' => $ambulance_total,
            'not_complete' => $ambulance_not
        ], 201);
        
    }
    
    public static function Driverlive(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'trip_id' => 'required',
            'current_lat'=> 'required',
            'current_long'=> 'required',
            
        ]);
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
    
       // $driver->status = $request->status;
        $driver->current_lat = $request->current_lat;
        $driver->current_long = $request->current_long;
        $driver->save();
        
        
        $ambulance = Ambulance::where('id', $request->trip_id)->where('driver_id', $driver->id)->first();
        
        $hospital = Hospital::where('id', $ambulance->hospital_id)->first();
        
            //   $data = [
            //         'user_id' => $ambulance->user_id,   
            //         'name' => $driver->name,
            //         'date' => $ambulance->pickup_date,
            //         'phone' => $driver->phone_number,
            //         'trip' => $ambulance->trip,
            //         //'location' => $validatedData['location_name'],
            //       // 'member_id' => $userSubscription->referral_no ?? '',
            //         'trip_id' => $ambulance->id,
            //         'hospital_name' => $hospital->name ?? '',
            //         'hospital_address' => $hospital->address,
            //       // 'decline_count' => $ambulance->decline_count
            //       // 'message' => $body,      
            //     ];
                
            //   // dd($data);
            
            //     broadcast(new \App\Events\AmbulanceNotification($data));
                
         return response()->json([
            'status' => true,
            'message' => 'Driver status updated successfully'
        ]);
        
    }
    
    public function driverDistance(Request $request)
    {
        $userId = $request->query('user_id');
        $userLat = $request->query('lat');
        $userLong = $request->query('long');
    
        $driverIds = RoasterMapping::where('driver_status', 'Online')
            ->where(function ($query) {
                $query->where('ride_status', 'Complete')
                      ->orWhereNull('ride_status');
            })
            ->pluck('driver_id')
            ->toArray(); // ✅ flatten
            
           //] dd($driverIds);
        
           $drivers = Driver::whereIn('id', $driverIds)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_long')
            ->get()
            ->map(function ($driver) use ($userLat, $userLong) {
                $theta = $userLong - $driver->current_long;
                $dist = sin(deg2rad($userLat)) * sin(deg2rad($driver->current_lat)) +
                        cos(deg2rad($userLat)) * cos(deg2rad($driver->current_lat)) * cos(deg2rad($theta));
                $dist = acos($dist);
                $dist = rad2deg($dist);
                $miles = $dist * 60 * 1.1515;
                $driver->distance = round($miles * 1.609344, 2); // in km
                return $driver;
            })->sortBy('distance')->values();

          //dd($drivers);

       
        $zohoData1 = [
            'data' => $drivers->map(function ($driver) use ($request) {
                $currentLocation = $this->getLocationName($driver->current_lat, $driver->current_long);
    
                return [
                    'Name'             => $driver->name . ' | ' . ($driver->distance ?? '0') . ' km', 
                    'Driver_Name'      => $driver->name . ' | ' . ($driver->distance ?? '0') . ' km', 
                    'Driver_Id'        => $driver->id,
                    'User_Id'          => $request->query('user_id'), // Corrected user_id from Driver table
                    'Current_Location' => $currentLocation,
                    'Driver_Status'    => 'Online',
                    'Location_From'    => $request->location,
                    'Status'           => "Active"
                ];
            })->toArray()
        ];
        
       // dd($zohoData1);
    
        // Step 4: Send Data to Zoho CRM
        $crmController1 = new CRMController();
        $accessToken1 = $crmController1->getZohoAccessToken();
    
        $module = 'Driver_Distance';
        $response1 = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken $accessToken1",
            'Content-Type' => 'application/json',
        ])->post("https://www.zohoapis.com/crm/v2/$module", $zohoData1);
        
       // dd($response1->json());
    
       return response('Data Fetching Success', 200);

    }
    
}
