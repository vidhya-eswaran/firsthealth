<?php

namespace App\Http\Controllers\Api;

use App\Models\Ambulance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use App\Models\User;
use App\Models\ActivityMaster;
use App\Models\NotificationUser;
use App\Models\UserSubscription;
use App\Models\RoasterMapping;
use App\Models\Hospital;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller; 
use App\Http\Controllers\Api\CRMController;
use App\Services\FirebasePushNotificationService;
use App\Models\TripStatusLog;
use App\Jobs\DeleteZohoDistanceRecords;

use App\Events\AmbulanceNotification;


class AmbulanceController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebasePushNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    
    public function getAmbulance(Request $request)
    {
        $ambulance = Ambulance::orderBy('id', 'desc')->get(); // fixed `orderBy`
        
        return response()->json([
            'status' => true,
            'data' => $ambulance
        ]);
    }
    
    public function ambulanceStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            
            'trip'=> 'required',
            'location' => 'required',
            'location_name' => 'nullable',
            'phone_number' => 'required|max:15',
            'hospital_id' => 'nullable',
            'hospital' => 'nullable',
            'registered_address' => 'nullable',
            'reg_lat' => 'nullable',
            'reg_long' => 'nullable',
            'driver_id' => 'nullable',
            'driver' => 'nullable',
            'careoff' => 'nullable|string|max:255',
            'pickup_date' => 'nullable',
            'diagnosis' => 'nullable',
            'gender' => 'nullable',
            'notes' => 'nullable|string',
            'clinical_info' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
            //'patient_name' => 'nullable|string|max:255',
            'status' => 'nullable',
            'trip_status' => 'nullable',
            'reg_id' => 'nullable',
            'manual_username' => 'nullable',
            'record_id' => 'nullable',
            'calling_user_id' => 'nullable'
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // Validation error response
        }
    
        $validatedData = $validator->validated();
        
        $user = \DB::table('users')->where('id', $validatedData['user_id'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        $validatedData['patient_name'] = $user->name;
        
        $validatedData['zoho_record_id'] = $validatedData['record_id'];
        
        //$validatedData['pickup_date'] = Carbon::parse($validatedData['pickup_date'])->format('Y-m-d H:i:s');
        
        $ambulance = Ambulance::create($validatedData);
        
        $activity_masters = ActivityMaster::where('id',$validatedData['status'])->first();
        
        if($activity_masters->id == 2)
        {
            $is_scheduled = 1;
        }else
        {
            $is_scheduled = 0;
        }
    
        $activityData = [
            'user_id' => $validatedData['user_id'],
            'trip_id' => $ambulance->id ?? '',
            'is_scheduled' => $is_scheduled,
            'activity' => $activity_masters->name, 
            'reg_id' => $ambulance->reg_id ?? null,
            'activity_date' => now(), 
            'activity_by' => $validatedData['user_id'], 
        ];
    
        \DB::table('activities')->insert($activityData);
                    
        if($user && $user->device_token){
                       
            $userName = $user->name;
            $activity = $activity_masters->name;
            
            $body =  "Hello, $userName An  $activity  to you. Click here to view..";
    
                        // Create the main notification
             NotificationUser::create([
                            'form_user_id' => $user->id,
                            'to_user_id' => $user->id,
                            'type' => 'notification',
                            'title' => 'First Health',
                            'body' => $body,
                            'is_sent' => 1,
                            'created_by' => $user->id,
                        ]);
    
            $this->firebaseService->sendNotification($user->device_token, 'Ambulance Update', $body, ['collapse_key' => 'referral_response']);
                        
        }
        
         $driver = Driver::where('id', $validatedData['driver_id'])->first();
         
         if($driver){
             
            $driver_user = User::where('id', $driver->user_id)->first();
        
            if($driver_user && $driver_user->device_token)
            {
                /*push notification*/
                    $userName = $driver_user->name;
                    $activity = $activity_masters->name;
                    
                    $body =  "Hello, $userName An  $activity  to you. Click here to view..";
            
                                // Create the main notification
                     NotificationUser::create([
                                    'form_user_id' => $driver_user->id,
                                    'to_user_id' => $driver_user->id,
                                    'type' => 'notification',
                                    'title' => 'First Health',
                                    'body' => $body,
                                    'is_sent' => 1,
                                    'created_by' => $driver_user->id,
                                    'sound' => 'alert_driver',
                                ]);
            
                    $this->firebaseService->sendNotification($driver_user->device_token, 'Ambulance Update', $body,['sound' => 'alert_driver'], ['collapse_key' => 'referral_response']);
        
                /**/
                $userSubscription = UserSubscription::where('user_id', $request->user_id)->first();
                
                $hospital = Hospital::where('id', $validatedData['hospital_id'])->first();
                
                $data = [
                    'user_id' => $driver->user_id,   
                    'name' => $userName,
                    'date' => $validatedData['pickup_date'],
                    'phone' => $validatedData['phone_number'],
                    'trip' => $validatedData['trip'],
                    'location' => $validatedData['location_name'],
                    'member_id' => $userSubscription->referral_no ?? '',
                    'trip_id' => $ambulance->id ?? '',
                    'hospital_name' => $hospital->name ?? '',
                    'hospital_address' => $hospital->address,
                    'decline_count' => $ambulance->decline_count
                   // 'message' => $body,      
                ];
                
               // dd($data);
            
                broadcast(new \App\Events\AmbulanceNotification($data));
            }
         }
        
        /*Need to update the status of calling user ambulance "Scheduled" */
        try {
                
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
                
               // dd($startDate);
            
                $zohoData = [
                    'data' => [
                        [
                             'Action' => "Scheduled",
                        ],
                    ],
                ];
            
                $module = 'Calling_Users';
                $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                
               // dd($zohoData);
            
                if ($validatedData['calling_user_id']) {
                    // Update existing record
                    $recordId = $validatedData['calling_user_id'];
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put("$crmUrl/$recordId", $zohoData);
                }
                
              
                //dd($response->json());
            
                if ($response->successful()) {
                    $responseData = $response->json();
                    if (!empty($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
                       
                        $crmStatus = 'success';
                    } else {
                        $crmStatus = 'failed';
                    }
                } else {
                    $crmStatus = 'failed';
                }
            } catch (\Exception $e) {
                $crmStatus = 'error';
            }
            
         /*Reduce call count in subscription table*/
                
        $isDependent = isset($validatedData['reg_id']);
        
        $primaryuser = UserSubscription::where('user_id', $validatedData['user_id'])->first();

        if ($isDependent) {
            $userSubscription = UserSubscription::where('referral_id', $validatedData['user_id'])
                                                ->where('reg_id', $validatedData['reg_id'])
                                                ->first();
        } else {
            $userSubscription = UserSubscription::where('user_id', $validatedData['user_id'])->first();
        }
        
        if ($userSubscription) {
        
            // Only apply qualifying period check for main user
            $isWithinQualifyingPeriod = true;
            //if (!$isDependent) {
                $createdDate = Carbon::parse($primaryuser->start_date)->startOfDay();
                $currentDate = now();
                $daysPassed = $createdDate->diffInDays($currentDate);
                $qualifyingPeriod = 14;
        
                $isWithinQualifyingPeriod = $daysPassed <= $qualifyingPeriod;
            //}
        
            // Check conditions to reduce trip counts
            if (!$isWithinQualifyingPeriod || $userSubscription->plan_times > 1) {
                if ($validatedData['trip'] === "Emergency Trip") {
                    if ($userSubscription->r_emergency_calls > 0) {
                        $userSubscription->r_emergency_calls -= 1;
                    }
                } else {
                    if ($userSubscription->r_clinic_calls > 0) {
                        $userSubscription->r_clinic_calls -= 1;
                    }
                }
        
                $userSubscription->save();
            }
        }
    
        return response()->json([
            'message' => 'Ambulance details and activity inserted successfully',
            'ambulance_data' => $ambulance,
            'activity_data' => $activityData,
            'crmstatus' => $crmStatus,
        ], 201);
    }
    
    public function ambulanceEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:ambulances,zoho_record_id',
            'trip'=> 'nullable',
            'location' => 'nullable',
            'location_name' => 'nullable',
            'phone_number' => 'nullable|max:15',
            'hospital_id' => 'nullable',
            'hospital' => 'nullable',
            'registered_address' => 'nullable',
            'reg_lat' => 'nullable',
            'reg_long' => 'nullable',
            'driver_id' => 'nullable',
            'driver' => 'nullable',
            'careoff' => 'nullable|string|max:255',
            'pickup_date' => 'nullable',
            'diagnosis' => 'nullable',
            'gender' => 'nullable',
            'notes' => 'nullable|string',
            'clinical_info' => 'nullable|string',
           // 'user_id' => 'required|exists:users,id',
            //'patient_name' => 'nullable|string|max:255',
            'status' => 'nullable',
            'trip_status' => 'nullable',
            'assigned_trip_status' => 'nullable'
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // Validation error response
        }
        
        $validatedData = $validator->validated();
    
        // Fetch ambulance record by ID
        $ambulance = Ambulance::where('zoho_record_id', $request->id)->first();
    
        if (!$ambulance) {
            return response()->json(['message' => 'Ambulance record not found'], 404);
        }
    
        $user = \DB::table('users')->where('id', $ambulance->user_id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        $validatedData['patient_name'] = $user->name;
        //$validatedData['assigned_trip_status'] = NULL;
        
      //  dd($validatedData);
        
        $ambulance->update($validatedData);
    
        $activity_masters = ActivityMaster::where('id', $validatedData['status'])->first();
    
        if ($activity_masters->id == 2) {
            $is_scheduled = 1;
        } else {
            $is_scheduled = 0;
        }
    
        $activityData = [
            'user_id' => $ambulance->user_id,
            'trip_id' => $ambulance->id ?? '',
            'is_scheduled' => $is_scheduled,
            'activity' => $activity_masters->name,
            'activity_date' => now(),
            'activity_by' => $ambulance->user_id,
        ];
    
        // Insert or update activity
        \DB::table('activities')->updateOrInsert(
            ['user_id' => $ambulance->user_id, 'activity' => $activity_masters->name],
            $activityData
        );
        
        //dd($user->device_token);
        
        // if($user && $user->device_token){
                       
        //     $userName = $user->name;
        //     $activity = $activity_masters->name;
            
        //     $body =  "Hello, $userName An  $activity  to you. Click here to view..";
    
        //                 // Create the main notification
        //      NotificationUser::create([
        //                     'form_user_id' => $user->id,
        //                     'to_user_id' => $user->id,
        //                     'type' => 'notification',
        //                     'title' => 'First Health',
        //                     'body' => $body,
        //                     'is_sent' => 1,
        //                     'created_by' => $user->id,
        //                 ]);
    
        //                 // Send notification via Firebase with a unique collapse key
        //     $this->firebaseService->sendNotification($user->device_token, 'Ambulance Update', $body, ['collapse_key' => 'referral_response']);
        // }
            
            $userSubscription = UserSubscription::where('user_id', $user->id)->first();
            
            $hospital = Hospital::where('id', $validatedData['hospital_id'])->first();
            
            /*driver socket*/
            $driver = Driver::where('id', $validatedData['driver_id'])->first();

        if($driver){
             
            $driver_user = User::where('id', $driver->user_id)->first();
                
                //dd($driver_user);
            
            if($driver_user && $driver_user->device_token)
                {
                    /*push notification*/
                        $userName = $driver_user->name;
                        $activity = $activity_masters->name;
                        
                        if($validatedData['assigned_trip_status'] == NULL)
                        {
                            $body =  "Hello, $userName An  $activity  to you. Click here to view..";
                
                                    // Create the main notification
                             NotificationUser::create([
                                            'form_user_id' => $driver_user->id,
                                            'to_user_id' => $driver_user->id,
                                            'type' => 'notification',
                                            'title' => 'First Health',
                                            'body' => $body,
                                            'is_sent' => 1,
                                            'created_by' => $driver_user->id,
                                            'sound' => 'alert_driver',
                                        ]);
                    
                            $this->firebaseService->sendNotification($driver_user->device_token, 'Ambulance Update', $body,['sound' => 'alert_driver'], ['collapse_key' => 'referral_response']);
                        }
                        
                        $driverChanged = isset($validatedData['driver_id']) && $ambulance->driver_id != $validatedData['driver_id'];
            
                    //dd($ambulance);
        
                    // if ($driverChanged) {
                        
                        $data = [
                            'user_id' => $driver->user_id,   
                            'name' => $userName,
                            'date' => $validatedData['pickup_date'],
                            'phone' => $validatedData['phone_number'],
                            'trip' => $validatedData['trip'],
                            'location' => $validatedData['location_name'],
                            'member_id' => $userSubscription->referral_no ?? '',
                            'trip_id' => $ambulance->id,
                            'hospital_name' => $hospital->name,
                            'hospital_address' => $hospital->address,
                            'decline_count' => $ambulance->decline_count
                           // 'message' => $body,      
                        ];
                        
                        //dd($data);
                    
                        broadcast(new \App\Events\AmbulanceNotification($data));
                    //}
                }
            }
                        
    
        return response()->json([
            'message' => 'Ambulance details updated successfully',
            'ambulance_data' => $ambulance,
            'activity_data' => $activityData
        ], 200);
    }
    
    public function AmbulanceAccept(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }

      
        $request->validate([
            'trip_id' => 'required',
            'assigned_trip_status' => 'required'
        ]);
    
        $ambulance = Ambulance::where('id', $request->trip_id)->where('driver_id', $driver->id)->first();
          //      dd($request->trip_id);
        if($ambulance)
        {
            $ambulance->assigned_trip_status = $request->assigned_trip_status;
            $ambulance->status = 2;
            $ambulance->save();
        }
        
        $user = \DB::table('users')->where('id', $ambulance->user_id)->first();
        
        if($user && $user->device_token){
            
            if($request->assigned_trip_status == "Accept")
            {
                $cancel = "Ambulance Scheduled";
                $activityData = [
                    'user_id' => $ambulance->user_id,
                    'trip_id' => $ambulance->id ?? '',
                    'is_scheduled' => 1,
                    'activity' => $cancel, 
                    'reg_id' => $ambulance->reg_id ?? null,
                    'activity_date' => now(), 
                    'activity_by' => $ambulance->user_id, 
                ];
            
                \DB::table('activities')->insert($activityData);
        
                $userName = $user->name;
                
                $body =  "Hello, $userName An Ambulance Scheduled to you. Click here to view..";
        
                            // Create the main notification
                 NotificationUser::create([
                                'form_user_id' => $user->id,
                                'to_user_id' => $user->id,
                                'type' => 'notification',
                                'title' => 'First Health',
                                'body' => $body,
                                'is_sent' => 1,
                                'created_by' => $user->id,
                            ]);
        
                            // Send notification via Firebase with a unique collapse key
                $this->firebaseService->sendNotification($user->device_token, 'Ambulance Update', $body, ['collapse_key' => 'referral_response']);
            }
        }
        
        try {
                $crmController = new CRMController();
                
                $accessToken = $crmController->getZohoAccessToken();
                    
                $cancel = "Ambulance Scheduled";
                    
                $todayDate = Carbon::now()->toDateString();
                    
                $relatedRecordId = AmbulanceController::getZohoactivityId($cancel, $accessToken);
            
                if($request->assigned_trip_status == "Accept")
                {
                   // dd($startDate);
                
                    $zohoData = [
                        'data' => [
                            [
                                 'Assigned_Trip_Status' => $request->assigned_trip_status,
                                 'Ambulance_Activity' => [
                                        'id' => $relatedRecordId, // Pass the ID of the related record
                                    ],
                            ],
                        ],
                    ];
                
                    $module = 'AssignAmbulances';
                    $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                    
                   // dd($zohoData);
                
                    if ($ambulance->zoho_record_id) {
                        // Update existing record
                        $recordId = $ambulance->zoho_record_id;
                        $response = Http::withHeaders([
                            'Authorization' => "Zoho-oauthtoken $accessToken",
                            'Content-Type' => 'application/json',
                        ])->put("$crmUrl/$recordId", $zohoData);
                    }
                    
            
                    /*make the driver as busy*/
                    if ($driver->zoho_record_id) {
                        $zohoData = [
                            'data' => [
                                [
                                     'Status' => "Busy",
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
                    
                    /*make the roaster record as busy for driver*/
                    
                     $roasters = RoasterMapping::where('driver_id', $driver->id)->whereDate('created_at', $todayDate)->get();
                    
                     foreach ($roasters as $roaster) {
                        if ($roaster->zoho_record_id) {
                            $zohoData1 = [
                                'data' => [
                                    [
                                        'Driver_Status' => "Busy",
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
                    
                    //dd($response->json());
                
                    if ($response->successful()) {
                        $responseData = $response->json();
                        if (!empty($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
                           
                            $crmStatus = 'success';
                        } else {
                            $crmStatus = 'failed';
                        }
                    } else {
                        $crmStatus = 'failed';
                    }
                }else{
                    $zohoData = [
                        'data' => [
                            [
                                 'Assigned_Trip_Status' => $request->assigned_trip_status,
                            ],
                        ],
                    ];
                
                    $module = 'AssignAmbulances';
                    $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                    
                   // dd($zohoData);
                
                    if ($ambulance->zoho_record_id) {
                        // Update existing record
                        $recordId = $ambulance->zoho_record_id;
                        $response = Http::withHeaders([
                            'Authorization' => "Zoho-oauthtoken $accessToken",
                            'Content-Type' => 'application/json',
                        ])->put("$crmUrl/$recordId", $zohoData);
                    }
                     $crmStatus = 'success';
                }
            
            } catch (\Exception $e) {
                $crmStatus = 'error';
            }
        
         return response()->json([
            'message' => 'Ambulance details updated successfully',
            'ambulance_data' => $ambulance,
            'crmStatus' => $crmStatus
        ], 200);
    }
    
    public static function TripDetails(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }

      
        $request->validate([
            'trip_id' => 'required',
        ]);
    
        $ambulance = Ambulance::where('id', $request->trip_id)->where('driver_id', $driver->id)->select('reg_lat', 'reg_long')->first();
        
         return response()->json([
            'ambulance_data' => $ambulance
        ], 200);
        
    }
    
    public static function driversCurrentTripDetails(Request $request)
    {
         $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
        
            $todayStart = Carbon::now()->startOfDay();
            $todayEnd = Carbon::now()->endOfDay();
            
            $ambulance = Ambulance::where('driver_id', $driver->id)
                 ->where(function ($query) {
                    $query->whereRaw("LOWER(trip_status) != 'complete'")
                          ->orWhereNull('trip_status');
                })
                ->whereBetween('updated_at', [$todayStart, $todayEnd])
                ->orderBy('updated_at', 'desc')
                ->first();
                
            if ($ambulance) {
                $track_log = TripStatusLog::where('trip_id', $ambulance->id)
                    ->where('status', '=', 'Complete')
                    ->first();
            
                if ($track_log) {
                    return response()->json(['message' => 'No trips found for today'], 200);
                }
            
            } else {
                return response()->json(['message' => 'No trips found for today'], 200);
            }
            
            $user = \DB::table('users')->where('id', $ambulance->user_id)->first();
            
            $userName = $user->name;
        
            $userSubscription = UserSubscription::where('user_id', $ambulance->user_id)->first();
            
            $hospital = Hospital::where('id', $ambulance->hospital_id)->first();
            
            $activity_masters = ActivityMaster::where('id',$ambulance->status)->first();
            
            $data = [
                'user_id' => $driver->user_id,   
                'name' => $userName,
                'date' => $ambulance->pickup_date,
                'phone' => $ambulance->phone_number,
                'trip' => $ambulance->trip,
                'location' => $ambulance->location_name,
                'member_id' => $userSubscription->referral_no ?? '',
                'trip_id' => $ambulance->id,
                'hospital_name' => $hospital->name,
                'hospital_address' => $hospital->address,
                'decline_count' => $ambulance->decline_count,
                'assigned_trip_status' => $ambulance->assigned_trip_status,
                'manual_username' => $ambulance->manual_username ?? '',
                'reg_id' => $ambulance->reg_id ?? '',
                'trip_status' => $ambulance->trip_status ?? '',
                'status' => $activity_masters->name ?? '',
                'notes' => $ambulance->notes ?? ''
               // 'message' => $body,      
            ];
            
             return response()->json(['success' => true, 'data' => $data]);
    }
    
    public function UserTripCancel(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'trip_id' => 'required',
            
        ]);
        
        $ambulance = Ambulance::where('id', $request->trip_id)->where('user_id', $user->id)->first();
        
        //dd($ambulance);
        
        if (!$ambulance) {
            return response()->json(['message' => 'Ambulance trip not found'], 404);
        }
        
        $ambulance->status = 4;
        $ambulance->save();
        
        $activity_masters = ActivityMaster::where('id',4)->first();
        
        $is_scheduled = 1;
    
        $activityData = [
            'user_id' => $user->id,
            'trip_id' => $ambulance->id ?? '',
            'is_scheduled' => $is_scheduled,
            'activity' => $activity_masters->name, 
            'reg_id' => $ambulance->reg_id ?? NULL,
            'activity_date' => now(), 
            'activity_by' => $user->id, 
        ];
    
        \DB::table('activities')->insert($activityData);
        
        $cancel = "Ambulance Cancelled";
        
        $roasters = RoasterMapping::where('driver_id', $ambulance->driver_id)->get();
        
        $driver = Driver::where('id', $ambulance->driver_id)->first();
        
        if($driver)
        {
            RoasterMapping::where('driver_id', $driver->id)->update(['driver_status' => 'Online']);
        }
         
        $driverStatus = "Online";
        
        try {
                
            $crmController = new CRMController();
            $accessToken = $crmController->getZohoAccessToken();
                
            $relatedRecordId = AmbulanceController::getZohoactivityId($cancel, $accessToken);

                $zohoData = [
                        'data' => [
                            [
                                'Ambulance_Activity' => [
                                    'id' => $relatedRecordId, // Pass the ID of the related record
                                ],
                            ],
                        ],
                    ];
                
                $module = 'AssignAmbulances';
                $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
            
                if ($ambulance->zoho_record_id) {
                    // Update existing record
                    $recordId = $ambulance->zoho_record_id;
                    
                    
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put("$crmUrl/$recordId", $zohoData);
                    
                    $zohoData1 = [
                        'data' => [
                            [
                                 'Patient_Name' => $recordId,
                                 'Name' => "Assign Ambulances"
                            ],
                        ],
                    ];
                
                    $module1 = 'Canceled_Ambulances';
                    $crmUrl = "https://www.zohoapis.com/crm/v2/$module1";
                    
                   // dd($zohoData);
                
                    $response1 = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->post("https://www.zohoapis.com/crm/v2/$module1", $zohoData1);
                   //dd($response1->json());
                }
                
                //dd($roasters);
                
                foreach ($roasters as $roaster) {
                    if ($roaster->zoho_record_id) {
                           
                            $zohoData1 = [
                                'data' => [
                                    [
                                        'Driver_Status' => $driverStatus,
                                       
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
               // dd($response->json());
                
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
            
                if ($response->successful()) {
                    $responseData = $response->json();
                    if (!empty($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
                       
                        $crmStatus = 'success';
                    } else {
                        $crmStatus = 'failed';
                    }
                } else {
                    $crmStatus = 'failed';
                }
            } catch (\Exception $e) {
                //dd($e);
                $crmStatus = 'error';
            }
            
         $driver = Driver::where('id', $ambulance->driver_id)->first();
         
         if($driver){
             
            $driver_user = User::where('driver_id', $ambulance->driver_id)->first();
        
            if($driver_user && $driver_user->device_token)
            {
                /*push notification*/
                    $userName = $driver_user->name;
                    $activity = $activity_masters->name;
                    
                    $body =  "Hello, $userName An  $activity  to you. Click here to view..";
            
                                // Create the main notification
                     NotificationUser::create([
                                    'form_user_id' => $driver_user->id,
                                    'to_user_id' => $driver_user->id,
                                    'type' => 'notification',
                                    'title' => 'First Health',
                                    'body' => $body,
                                    'is_sent' => 1,
                                    'created_by' => $driver_user->id,
                                    'sound' => 'alert_driver',
                                ]);
            
                    app(\App\Services\FirebasePushNotificationService::class)->sendNotification($driver_user->device_token, 'Ambulance Cancelled', $body, ['collapse_key' => 'referral_response']);
            
            }
         }
         
         DeleteZohoDistanceRecords::dispatch(
                    $ambulance->user_id,
                    $ambulance->driver_id,
                    $ambulance->hospital_id,
                    $accessToken
                );
        
         return response()->json([
            'message' => 'Ambulance details updated successfully',
            'success' => true,
            'ambulance_data' => $ambulance,
            'crmStatus' => $crmStatus
        ], 200);
        
    }
    
    public static function getZohoactivityId($cancel, $accessToken)
    {
        
        $module = 'Activity_Masters'; 
        $crmUrl = "https://www.zohoapis.com/crm/v2/$module/search?criteria=(Name:equals:$cancel)";
        
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
    
    public static function userCurrentTripDetails(Request $request)
    {
         $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::where('id', $user->id)->first();
        
        if (!$user) {
            return response()->json(['message' => 'Driver not found'], 404);
        }
        
        $todayStart = Carbon::now()->startOfDay();
        $todayEnd = Carbon::now()->endOfDay();
    
        $ambulanceTrips = Ambulance::where('user_id', $user->id)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->orderBy('created_at', 'desc')
            ->get();
            
        //dd($ambulanceTrips);
    
        if ($ambulanceTrips->isEmpty()) {
            return response()->json(['message' => 'No trips found for today'], 200);
        }
        
        $tripIds = $ambulanceTrips->pluck('id');
        
       $completedTripIds = TripStatusLog::whereIn('trip_id', $tripIds)
            ->where('status', 'Complete')
            ->pluck('trip_id')
            ->toArray();
        
        // Filter out completed trips
        $filteredTrips = $ambulanceTrips->filter(function ($trip) use ($completedTripIds) {
            return !in_array($trip->id, $completedTripIds);
        });
        
        if ($filteredTrips->isEmpty()) {
            return response()->json(['message' => 'No trips found for today'], 200);
        }
        
    
        $tripData = [];
    
        foreach ($filteredTrips as $ambulance) {
            
            $roaster = RoasterMapping::where('driver_id', $ambulance->driver_id)
           // ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->orderBy('created_at', 'desc')
            ->first();
            
            $hospital = Hospital::find($ambulance->hospital_id);
            $driver = Driver::find($ambulance->driver_id);
            
            $activity_masters = ActivityMaster::where('id',$ambulance->status)->first();
    
            $tripData[] = [
                'user_id' => $user->id,
                'name' => $driver->name ?? '',
                'date' => $ambulance->pickup_date,
                'phone' => $driver->phone_number ?? '',
                'trip' => $ambulance->trip,
                'location' => $ambulance->location_name,
                'current_lat' => $driver->current_lat ?? '',
                'current_long' => $driver->current_long ?? '',
                'trip_id' => $ambulance->id,
                'hospital_name' => $hospital->name ?? '',
                'hospital_address' => $hospital->address ?? '',
                'manual_username' => $ambulance->manual_username ?? '',
                'reg_id' => $ambulance->reg_id ?? '',
                'trip_status' => $ambulance->trip_status ?? '',
                'assigned_trip_status' => $ambulance->assigned_trip_status ?? '',
                'vehicle' => $roaster->vehicle ?? '',
                'status' => $activity_masters->name ?? '',
                'created_at' => $ambulance->created_at,
                'updated_at' => $ambulance->updated_at
            ];
        }
    
        return response()->json(['success' => true, 'data' => $tripData]);

            
    }
    
    private function getZohoRideStatusId($rideStatusName, $accessToken)
    {
        
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


}
