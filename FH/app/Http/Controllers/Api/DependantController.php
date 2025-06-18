<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\SubscriptionMaster;
use App\Models\BenefitMaster;
use App\Models\Dependant;
use App\Models\UserSubscription;
use App\Models\Registration;
use App\Models\Payment;
use App\Models\PurchaseSlot;
use App\Models\User;
use App\Models\InviteUser;
use App\Models\NotificationUser;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use \DateTime;
use App\Mail\DependentInvitationMail;
use App\Mail\RevokeMail;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Api\CRMController;
use App\Services\FirebasePushNotificationService;


class DependantController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebasePushNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $userSubscription = UserSubscription::where('user_id', $userId)->firstOrFail();
            
            $dependentSubscriptions = UserSubscription::where('referral_id', $userId)
                ->where('is_dependent', 1)
                ->where('is_removed', 0)
                ->get();

            $invitedUsers = InviteUser::where('user_id', $userId)->get();
            
            //dd($invitedUsers);

            $types = ['Adult', 'Senior', 'Child'];
            $remaining = [];
            $id = 1;

            foreach ($types as $type) {
                $typeColumn = strtolower($type) . '_count';
                $typeCount = $userSubscription->$typeColumn;
                $usedCount = $dependentSubscriptions->where('type_dependant', $type)->count();
                $invitedCount = $invitedUsers->where('type_dependant', $type)
                    ->where('is_revoke', 0)
                    ->where('is_release_slot', 0)
                    ->count();
               // dd($typeCount,$usedCount,$invitedCount);

                $remainingCount = max(0, $typeCount);
                
                

                for ($i = 0; $i < $remainingCount; $i++) {
                    $remaining[] = [
                        'Id' => $id++,
                        'type' => $type . ' member',
                        'title' => $type,
                        'range' => $this->getAgeRange($type),
                        'filled' => false,
                        'name' => '',
                        'man_reg' => false,
                    ];
                }
            }
            
            

            $totalSlots = $userSubscription->slot_count;
            $usedSlots = $dependentSubscriptions->count();
            $invitedSlots = $invitedUsers->where('is_revoke', 0)->where('is_release_slot', 0)->where('is_accepted', 0)->count();
            
            //dd($totalSlots, $usedSlots, $invitedSlots);
            
            $remainingSlots = max(0, $totalSlots - $usedSlots - $invitedSlots);

            return response()->json([
                'referral_number' => $userSubscription->referral_no,
                'remaining_slots' => $remainingSlots,
                'remaining' => $remaining
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve dependent details: ' . $e->getMessage(), [
                'exception' => $e,
                'userId' => $userId ?? null
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dependent details.'
            ], 500);
        }
    }

    private function getAgeRange($type)
    {
        switch ($type) {
            case 'Adult':
                return '18-59 years old';
            case 'Senior':
                return 'Older than 59 years old';
            case 'Child':
                return 'Younger than 18 years old';
            default:
                return '';
        }
    }

    public function getById(Request $request, $id)
    {
        try {
            //dd($id);

            // Retrieve User Subscriptions
            $registration = Registration::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Dependent details retrieved successfully.',
                'data' => $registration
            ], 200);



        } catch (\Exception $e) {
            Log::error('Failed to retrieve dependent details: ' . $e->getMessage(), [
                'exception' => $e,
                'requestData' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dependent details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeOrUpdate(Request $request)
    {
        // Check if reg_id is present in the request
        if ($request->has('reg_id')) {
            return $this->edit($request);
        } else {
            return $this->store($request);
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            //'ic_number' => 'required|string|max:100',
            'phone_number' => 'required|string|max:100',
            'email' => 'required|email|max:250',
            'dob' => 'required|date',
            'race' => 'required|string|max:250',
            'gender' => 'required|in:0,1',
            'nationality' => 'required|string|max:250',
            'heart_problems' => 'required|boolean',
            'diabetes' => 'required|boolean',
            'allergic' => 'required|boolean',
            //'allergic_medication_list' => 'required|string|max:200',
            'type_dependant' => 'required',
            'are_u_foreigner' => 'required|boolean',
            // 'passport_no' => $request->input('are_u_foreigner') ? 'required_if:are_u_foreigner,1|string|max:255' : 'nullable',

        ];
        
         if ($request->input('are_u_foreigner') == 1) {
            // dd("dd");
            $rules['passport_no'] = 'required|string|max:255';
             
            $rules['ic_number'] = 'nullable'; // Not required if foreigner
        } else {
            $rules['ic_number'] = 'required|string|max:255';
            $rules['passport_no'] = 'nullable'; // Not required if not foreigner
        }
        
        $rules['allergic_medication_list'] = $request->input('allergic') == 1
        ? 'required|string|max:200'
        : 'nullable|string|max:200';
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            
            if (Registration::where('email', $request->input('email'))->exists()) {
                return response()->json(['error' => 'Email Already Exists'], 400);
            }

            $userId = $request->user()->id;

            $typeDependant = $request->input('type_dependant');
            $lowercaseTypeDependant = strtolower($typeDependant);
            $typeDependantColumn = $lowercaseTypeDependant . '_count'; // e.g., adult_count, child_count, etc.

            $userSubscription = UserSubscription::where('user_id', $userId)->first();
            if (!$userSubscription) {
                return response()->json(['error' => 'User subscription not found'], 404);
            }

            $dbDependantCount = $userSubscription->$typeDependantColumn;

            $currentDependantCount = UserSubscription::where('referral_id', $userId)->where('is_removed', 0)->where('type_dependant', $typeDependant)->count();
            
            //dd($currentDependantCount,$dbDependantCount );

            if ($dbDependantCount == 0) {
                return response()->json([
                    'error' => "$typeDependant Member Maximum number of dependants reached - Max limit $currentDependantCount"
                ], 400);
            }

            // $age = \Carbon\Carbon::parse($request->input('dob'))->age;

            // switch ($typeDependant) {
            //     case 'Adult':
            //         if ($age < 18 || $age > 59) {
            //             return response()->json(['error' => 'Adult Member must be between 18-59 years old'], 400);
            //         }
            //         break;
            //     case 'Child':
            //         if ($age > 17) {
            //             return response()->json(['error' => 'Child Member must be up to 17 years old'], 400);
            //         }
            //         break;
            //     case 'Senior Citizen':
            //         if ($age < 60) {
            //             return response()->json(['error' => 'Senior Citizen Member must be 60 years or older'], 400);
            //         }
            //         break;
            // }
            
            $dob = \Carbon\Carbon::parse($request->input('dob'));
            $today = \Carbon\Carbon::today();
            $sixtyYearsAgo = $today->copy()->subYears(60);
            
            switch ($typeDependant) {
                case 'Adult':
                    if ($dob->greaterThan($today->copy()->subYears(18)) || $dob->lessThanOrEqualTo($today->copy()->subYears(60))) {
                        return response()->json(['error' => 'Adult Member must be between 18-59 years old'], 400);
                    }
                    break;
                case 'Child':
                    if ($dob->lessThanOrEqualTo($today->copy()->subYears(18))) {
                        return response()->json(['error' => 'Child Member must be up to 17 years old'], 400);
                    }
                    break;
                case 'Senior':
                    if ($dob->greaterThan($sixtyYearsAgo)) {
                        return response()->json(['error' => 'Senior Citizen Member must be 60 years or older'], 400);
                    }
                    break;
            }
            
           // dd($typeDependant);


            $registrationData = [
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'ic_number' => $request->input('ic_number'),
                'phone_number' => $request->input('phone_number'),
                'email' => $request->input('email'),
                'dob' => $request->input('dob'),
                'race' => $request->input('race'),
                'gender' => $request->input('gender'),
                'nationality' => $request->input('nationality'),
                'heart_problems' => $request->input('heart_problems'),
                'diabetes' => $request->input('diabetes'),
                'allergic' => $request->input('allergic'),
                'allergic_medication_list' => $request->input('allergic_medication_list'),
                'are_u_foreigner' => $request->input('are_u_foreigner'),
                'passport_no' => $request->input('passport_no'),
            ];

            $registration = Registration::create($registrationData);
            
            if ($userSubscription) {
                if ($typeDependant === 'Senior') {
                    if ($userSubscription->senior_count >= 0) {
                        $userSubscription->decrement('senior_count');
                    } else {
                        return response()->json([
                            'message' => 'No more senior dependent slots available.'
                        ], 400);
                    }
                } elseif ($typeDependant === 'Adult') {
                    if ($userSubscription->adult_count >= 0) {
                        $userSubscription->decrement('adult_count');
                    } else {
                        return response()->json([
                            'message' => 'No more adult dependent slots available.'
                        ], 400);
                    }
                }elseif ($typeDependant === 'Child') {
                    if ($userSubscription->child_count >= 0) {
                        $userSubscription->decrement('child_count');
                    } else {
                        return response()->json([
                            'message' => 'No more child dependent slots available.'
                        ], 400);
                    }
                }
            }
            
            $crmStatus = 'success'; 
            $recordId = null;

            try {
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
                    
                $zohoData = [
                    'data' => [
                        [
                            'reg_id' => $registration->id,
                            'Name' => $registration->first_name.' '.$registration->last_name ,
                            'address' => $registration->address,
                            'allergic' => $registration->allergic,
                            'allergic_medication_list' => $registration->allergic_medication_list,
                            'are_u_foreigner' => $registration->are_u_foreigner,
                            'diabetes' => $registration->diabetes,
                            'dob' => $registration->dob,
                            'email_id' => $registration->email,
                            'first_name' => $registration->first_name,
                            'gender' => $registration->gender == 0 ? 'male' : 'female',
                            'heart_problems' => $registration->heart_problems,
                            'ic_number' => $registration->ic_number,
                            'last_name' => $registration->last_name,
                            'latitude' => $registration->latitude,
                            'longitude' => $registration->longitude,
                            'medical_info' => $registration->medical_info,
                            'nationality' => $registration->nationality,
                            'passport_no' => $registration->passport_no,
                            'phone_number' => $registration->phone_number,
                            'race' => $registration->race,
                        ],
                    ],
                ];
               // dd($zohoData);
            
                $module = 'User_Registrations';  
            
                $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->post("https://www.zohoapis.com/crm/v2/$module", $zohoData);
                    
               // dd($response->json());
                if ($response->successful()) {
                    $responseData = $response->json();
    
                    if (!empty($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
                        $recordId = $responseData['data'][0]['details']['id'] ?? null;
                        
                        if ($recordId) {
                            $registration->zoho_record_id = $recordId; 
                            $registration->save();
                        }
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

            $subscriptionData = [
                'referral_id' => $userId,
                'subscription_id' => $userSubscription->subscription_id,
                'reg_id' => $registration->id,
                'is_dependent' => 1,
                'is_manual' => 1,
                'type_dependant' => $typeDependant,
                'count' => 0,
                'adult_count' => 0,
                'senior_count' => 0,
                'child_count' => 0,
                't_emergency_calls' => 2,
                't_clinic_calls' => 2,
            ];
            $userSubscription = UserSubscription::create($subscriptionData);
            
            $membership = "";
                
            $subscriptionId = $userSubscription->subscription_id ? $userSubscription->subscription_id : null;
            
            if ($subscriptionId) {
                $subscriptionMaster = SubscriptionMaster::where('id', $subscriptionId)->first();
                if ($subscriptionMaster) {
                    $membership = $subscriptionMaster->plan; 
                }
            }
            
             try {
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
            
                $zohoData = [
                    'data' => [
                        [
                            'Name' => $registration->first_name. ' '.$registration->last_name,
                            'referral_id' => $userId,
                            'subscription_id' => $userSubscription->subscription_id,
                            'reg_id' => $registration->id,
                            'is_dependent' => 1,
                            'is_manual' => 1,
                            'type_dependant' => $typeDependant,
                            'count' => 0,
                            'adult_count' => 0,
                            'senior_count' => 0,
                            'child_count' => 0,
                            't_emergency_calls' => 2,
                            't_clinic_calls' => 2,
                            'Membership' => $membership,
                        ],
                    ],
                ];
            
                $module = 'User_Subscriptions';
                $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
            
                
                    // Create new record
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->post($crmUrl, $zohoData);
             
            
                if ($response->successful()) {
                    $responseData = $response->json();
                    if (!empty($responseData['data'][0]['code']) && $responseData['data'][0]['code'] === 'SUCCESS') {
                        $recordId = $responseData['data'][0]['details']['id'] ?? null;
                        if ($recordId) {
                            $userSubscription->zoho_record_id = $recordId;
                            $userSubscription->save();
                        }
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

            return response()->json([
                'message' => 'Dependant registration added successfully',
                'data' => $registration,
                'crmStatus' => $crmStatus,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (Exception $e) {
            Log::error('Failed to process registration: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            return response()->json(['error' => 'Failed to process your request'], 500);
        }
    }

    public function edit(Request $request)
    {
        // Validation rules
        $rules = [
            'reg_id' => 'required',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            //'ic_number' => 'required|string|max:100',
            'phone_number' => 'required|string|max:100',
           // 'email' => 'required|email|max:250',
            //'dob' => 'required|date',
            'race' => 'required|string|max:250',
            'gender' => 'required|in:0,1',
            'nationality' => 'required|string|max:250',
            'heart_problems' => 'required|boolean',
            'diabetes' => 'required|boolean',
            'allergic' => 'required|boolean',
            //'allergic_medication_list' => 'required|string|max:200',
            'type_dependant' => 'required',
            'are_u_foreigner' => 'required|boolean',

        ];
        
        if ($request->input('are_u_foreigner') == 1) {
            // dd("dd");
            $rules['passport_no'] = 'required|string|max:255';
             
            $rules['ic_number'] = 'nullable'; // Not required if foreigner
        } else {
            $rules['ic_number'] = 'required|string|max:255';
            $rules['passport_no'] = 'nullable'; // Not required if not foreigner
        }
        
         $rules['allergic_medication_list'] = $request->input('allergic') == 1
            ? 'required|string|max:200'
            : 'nullable|string|max:200';
        
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {

            
            $userId = $request->user()->id;
            $regId = $request->input('reg_id');
            $newTypeDependant = $request->input('type_dependant');


            // Retrieve the dependant record
            $dependent = Registration::find($regId);
            if (!$dependent) {
                return response()->json(['error' => 'Dependent not found'], 404);
            }

            // Check if the email already exists for another record
            if (Registration::where('email', $request->input('email'))
                            ->where('id', '<>', $regId)
                            ->exists()) {
                return response()->json(['error' => 'Email already exists'], 400);
            }

            // Retrieve user subscription record
            $userSubscription = UserSubscription::where('user_id', $userId)->where('is_removed', 0)->first();
            if (!$userSubscription) {
                return response()->json(['error' => 'User subscription not found'], 404);
            }

            // Check if the dependant type already exists
            $db_Dependant = UserSubscription::where('referral_id', $userId)
                ->where('is_removed', 0)
                ->where('type_dependant', $newTypeDependant)
                ->where('reg_id', $regId) // Exclude the current dependant
                ->first();

            // $age = \Carbon\Carbon::parse($request->input('dob'))->age;

            // switch ($newTypeDependant) {
            //     case 'Adult':
            //         if ($age < 18 || $age > 59) {
            //             return response()->json(['error' => 'Adult Member must be between 18-59 years old'], 400);
            //         }
            //         break;
            //     case 'Child':
            //         if ($age > 17) {
            //             return response()->json(['error' => 'Child Member must be up to 17 years old'], 400);
            //         }
            //         break;
            //     case 'Senior Citizen':
            //         if ($age < 60) {
            //             return response()->json(['error' => 'Senior Citizen Member must be 60 years or older'], 400);
            //         }
            //         break;
            // }
            
            $dob = \Carbon\Carbon::parse($request->input('dob'));
            $today = \Carbon\Carbon::today();
            $sixtyYearsAgo = $today->copy()->subYears(60);
            
            switch ($newTypeDependant) {
                case 'Adult':
                    if ($dob->greaterThan($today->copy()->subYears(18)) || $dob->lessThanOrEqualTo($today->copy()->subYears(60))) {
                        return response()->json(['error' => 'Adult Member must be between 18-59 years old'], 400);
                    }
                    break;
                case 'Child':
                    if ($dob->lessThanOrEqualTo($today->copy()->subYears(18))) {
                        return response()->json(['error' => 'Child Member must be up to 17 years old'], 400);
                    }
                    break;
                case 'Senior Citizen':
                    if ($dob->greaterThan($sixtyYearsAgo)) {
                        return response()->json(['error' => 'Senior Citizen Member must be 60 years or older'], 400);
                    }
                    break;
            }


            if (!$dependent) {
                return response()->json(['error' => 'Dependent not found'], 404);
            }

            if ($db_Dependant && $db_Dependant->type_dependant == $newTypeDependant) {
                // Update the dependant record
                $dependent = Registration::find($regId);
                $dependent->update([
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'ic_number' => $request->input('ic_number'),
                    'phone_number' => $request->input('phone_number'),
                   // 'email' => $request->input('email'),
                   // 'dob' => $request->input('dob'),
                    'race' => $request->input('race'),
                    'gender' => $request->input('gender'),
                    'nationality' => $request->input('nationality'),
                    'heart_problems' => $request->input('heart_problems'),
                    'diabetes' => $request->input('diabetes'),
                    'allergic' => $request->input('allergic'),
                    'allergic_medication_list' => $request->input('allergic_medication_list'),
                    'are_u_foreigner' => $request->input('are_u_foreigner'),
                    'passport_no' => $request->input('passport_no'),
                ]);

                return response()->json([
                    'message' => 'Dependent details updated successfully',
                    'data' => $dependent,
                ], 200);
            } else {
                $exist_Dependant_count = UserSubscription::where('referral_id', $userId)
                    ->where('is_removed', 0)
                    ->where('type_dependant', $newTypeDependant)
                    ->count();

                $currentTypeDependantColumn = strtolower($newTypeDependant) . '_count';
                $dbCurrentTypeDependantCount = $userSubscription->$currentTypeDependantColumn;

                if ($dbCurrentTypeDependantCount == 0) {
                    return response()->json([
                        'error' => "$newTypeDependant Member Maximum number of dependants reached - Max limit $exist_Dependant_count"
                    ], 400);
                }

                // Update the dependant record
                $dependent->update([
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'ic_number' => $request->input('ic_number'),
                    'phone_number' => $request->input('phone_number'),
                   // 'email' => $request->input('email'),
                   // 'dob' => $request->input('dob'),
                    'race' => $request->input('race'),
                    'gender' => $request->input('gender'),
                    'nationality' => $request->input('nationality'),
                    'heart_problems' => $request->input('heart_problems'),
                    'diabetes' => $request->input('diabetes'),
                    'allergic' => $request->input('allergic'),
                    'allergic_medication_list' => $request->input('allergic_medication_list'),
                    'are_u_foreigner' => $request->input('are_u_foreigner'),
                    'passport_no' => $request->input('passport_no'),
                ]);

                // Update the user subscription with the new type
                UserSubscription::where('reg_id', $regId)->update([
                    'referral_id' => $userId,
                    'is_dependent' => 1,
                    'is_manual' => 1,
                    'type_dependant' => $newTypeDependant,
                    'count' => 0,
                    'adult_count' => 0,
                    'senior_count' => 0,
                    'child_count' => 0,
                    't_emergency_calls' => 2,
                    't_clinic_calls' => 2,
                ]);

                return response()->json([
                    'message' => 'Dependent details updated successfully',
                    'data' => $dependent,
                ], 200);
            }
        } catch (ValidationException $e) {
            // Return validation errors
            return response()->json($e->errors(), 422);
        } catch (Exception $e) {
            // Log the exception and return error response
            Log::error('Failed to update dependent details: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'error' => 'Failed to process your request',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function inviteDependent(Request $request)
    {
        try {
            $userId = $request->user()->id;
    
            // Validate request data
            $request->validate([
                'email' => 'required|email',
                'type_dependant' => 'required',
            ]);
    
            $email = $request->email;
            $dependentType = $request->type_dependant;
            $type_mail = 1;
    
            $userSubscription = UserSubscription::where('user_id', $userId)->first();
    
            if (!$userSubscription) {
                return response()->json([
                    'message' => 'User subscription not found.'
                ], 404);
            }
            
            $alreadyadded_user = User::where('email',$email)->first();
            if($alreadyadded_user)
            {
                $check_subscription = UserSubscription::where('user_id',$alreadyadded_user->id)->first();
            
                if ($check_subscription) {
                    return response()->json([
                        'message' => 'User already added with a subscription.',
                        'status' => false
                    ], 400);
                }
            }
            
            
            $exists_mail = InviteUser::where('user_id', $userId)
                ->where('to_mail', $email)
                ->first(); 
            if($dependentType === 'Adult')
            {
                $maxAllowedCount = $userSubscription->adult_count;
            }else if($dependentType === 'Senior')
            {
                $maxAllowedCount = $userSubscription->senior_count;
            }else
            {
                $maxAllowedCount = $userSubscription->child_count;
            }
    
            //$maxAllowedCount = $dependentType === 'Adult' ? $userSubscription->adult_count : $userSubscription->senior_count;
            
           // dd($maxAllowedCount);
    
            $currentDependentCount = UserSubscription::where('referral_id', $userId)
                ->where('is_removed', 0)
                ->where('type_dependant', $dependentType)
                ->count();
                
            //dd($maxAllowedCount);
            // if($currentDependentCount > 0)
            // {
               // if ($currentDependentCount > $maxAllowedCount) {
               if ($maxAllowedCount == 0) {
                    return response()->json([
                        'message' => "$dependentType Member: Maximum number of dependents reached - Max limit $maxAllowedCount"
                    ], 400);
                }
            //}
            
            $crmController = new CRMController();
            $accessToken = $crmController->getZohoAccessToken();
            
            
    
            if ($exists_mail) {
                
                if($exists_mail->is_revoke == 0)
                {
                    return response()->json(['message' => 'Already invite sent to this email ID.'], 400);
                }
                
                if($exists_mail->is_revoke == 1)
                {
                    if ($userSubscription) {
                        if ($dependentType === 'Senior') {
                            if ($userSubscription->senior_count >= 0) {
                                $userSubscription->decrement('senior_count');
                            } else {
                                return response()->json([
                                    'message' => 'No more senior dependent slots available.'
                                ], 400);
                            }
                        } elseif ($dependentType === 'Adult') {
                            if ($userSubscription->adult_count >= 0) {
                                $userSubscription->decrement('adult_count');
                            } else {
                                return response()->json([
                                    'message' => 'No more adult dependent slots available.'
                                ], 400);
                            }
                        }elseif ($dependentType === 'Child') {
                            if ($userSubscription->child_count >= 0) {
                                $userSubscription->decrement('child_count');
                            } else {
                                return response()->json([
                                    'message' => 'No more child dependent slots available.'
                                ], 400);
                            }
                        }
                    }
            
                    $zohoUpdateData = [
                        'data' => [
                            [
                                'adult_count' => $userSubscription->adult_count,
                                'senior_count' => $userSubscription->senior_count,
                                'child_count' => $userSubscription->child_count,
                            ],
                        ],
                    ];
            
                    $zohoUrl = "https://www.zohoapis.com/crm/v2/User_Subscriptions/{$userSubscription->zoho_record_id}";
            
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put($zohoUrl, $zohoUpdateData);
                    
                }
                $exists_mail->update([
                    'type_dependant' => $dependentType,
                    'status' => 0, 
                    'type_mail' => $type_mail,
                    'is_release_slot' => 0,
                    'is_revoke' => 0,
                ]);
                
            } else {
                $inviteUser = InviteUser::create([
                    'user_id' => $userId,
                    'to_mail' => $email,
                    'type_dependant' => $dependentType,
                    'type_mail' => $type_mail,
                    'status' => 0, 
                    'is_release_slot' => 0,
                    'is_revoke' => 0,
                ]);
                
                if ($userSubscription) {
                    if ($dependentType === 'Senior') {
                        if ($userSubscription->senior_count >= 0) {
                            $userSubscription->decrement('senior_count');
                        } else {
                            return response()->json([
                                'message' => 'No more senior dependent slots available.'
                            ], 400);
                        }
                    } elseif ($dependentType === 'Adult') {
                        if ($userSubscription->adult_count >= 0) {
                            $userSubscription->decrement('adult_count');
                        } else {
                            return response()->json([
                                'message' => 'No more adult dependent slots available.'
                            ], 400);
                        }
                    }elseif ($dependentType === 'Child') {
                        if ($userSubscription->child_count >= 0) {
                            $userSubscription->decrement('child_count');
                        } else {
                            return response()->json([
                                'message' => 'No more child dependent slots available.'
                            ], 400);
                        }
                    }
                }
                
                $zohoUpdateData = [
                        'data' => [
                            [
                                'adult_count' => $userSubscription->adult_count,
                                'senior_count' => $userSubscription->senior_count,
                                'child_count' => $userSubscription->child_count,
                            ],
                        ],
                    ];
            
                    $zohoUrl = "https://www.zohoapis.com/crm/v2/User_Subscriptions/{$userSubscription->zoho_record_id}";
            
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put($zohoUrl, $zohoUpdateData);
            }
            
            
    
            try {
                // Send the invitation email
                Mail::to($email)->send(new DependentInvitationMail($userSubscription->referral_no, $dependentType));
    
                if ($exists_mail) {
                    $exists_mail->update([
                        'status' => 1 // Email sent
                    ]);
                } else {
                    $inviteUser->update([
                        'status' => 1 // Email sent
                    ]);
                }
                
               // dd("DDdd");
    
                return response()->json([
                    'message' => 'Invitation sent successfully!',
                    'status' => true
                ], 200);
            } catch (\Exception $e) {
                //dd($e->getMessage());
                \Log::error('Failed to send invitation email: ' . $e->getMessage(), [
                    'exception' => $e,
                    'email' => $email,
                ]);
    
                if ($exists_mail) {
                    $exists_mail->update([
                        'status' => 0 // Email not sent
                    ]);
                } else {
                    $inviteUser->update([
                        'status' => 0 // Email not sent
                    ]);
                }
    
                return response()->json([
                    'message' => 'Failed to send email.',
                ], 500);
            }
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Log detailed error for debugging
            \Log::error('Failed to invite dependent: ' . $e->getMessage(), [
                'exception' => $e,
                'requestData' => $request->all(),
            ]);
    
            // Return a generic error message
            return response()->json([
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }

    public function Resendinvite(Request $request)
    {
        try {
            $userId = $request->user()->id;
    
            // Validate request data
            $request->validate([
                'email' => 'required|email',
                'type_dependant' => 'required',
            ]);
    
            $email = $request->email;
            $dependentType = $request->type_dependant;
            $type_mail = 1;
    
            $userSubscription = UserSubscription::where('user_id', $userId)->first();
    
            if (!$userSubscription) {
                return response()->json([
                    'message' => 'User subscription not found.'
                ], 404);
            }
            
            $alreadyadded_user = User::where('email',$email)->first();
            if($alreadyadded_user)
            {
                $check_subscription = UserSubscription::where('user_id',$alreadyadded_user->id)->first();
            
                if ($check_subscription) {
                    return response()->json([
                        'message' => 'User already added with a subscription.',
                        'status' => false
                    ], 400);
                }
            }
            
            
            $exists_mail = InviteUser::where('user_id', $userId)
                ->where('to_mail', $email)
                ->first();
            if ($exists_mail) {
                
                try {
                    // Send the invitation email
                    Mail::to($email)->send(new DependentInvitationMail($userSubscription->referral_no, $dependentType));
        
                    if ($exists_mail) {
                        $exists_mail->update([
                            'status' => 1 // Email sent
                        ]);
                    } else {
                        $inviteUser->update([
                            'status' => 1 // Email sent
                        ]);
                    }
                    
                   // dd("DDdd");
        
                    return response()->json([
                        'message' => 'Resend Invitation sent successfully!',
                        'status' => true
                    ], 200);
                } catch (\Exception $e) {
                    //dd($e->getMessage());
                    \Log::error('Failed to send invitation email: ' . $e->getMessage(), [
                        'exception' => $e,
                        'email' => $email,
                    ]);
        
                    if ($exists_mail) {
                        $exists_mail->update([
                            'status' => 0 // Email not sent
                        ]);
                    } else {
                        $inviteUser->update([
                            'status' => 0 // Email not sent
                        ]);
                    }
        
                    return response()->json([
                        'message' => 'Failed to send email.',
                    ], 500);
                }
            }else{
                 return response()->json([
                        'message' => 'Invitation is not send before!',
                        'status' => false
                    ], 200);
            }
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Log detailed error for debugging
            \Log::error('Failed to invite dependent: ' . $e->getMessage(), [
                'exception' => $e,
                'requestData' => $request->all(),
            ]);
    
            // Return a generic error message
            return response()->json([
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }

    
    public function dependentUserDetails(Request $request)
    {
        try {
            $user = Auth::user();
            
            $dependentID = $request->dependent_id;

            if (!$user) {
                return response()->json([
                    'error' => 'User not authenticated.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            //$user->load('registration');
            
            $user = User::where('id',$dependentID)->first();
            
            $userSubscription = UserSubscription::with('subscriptionMaster')->where('user_id', $dependentID)->first();
            
            //dd($userSubscription);
            
          
                $subscriptiondata = SubscriptionMaster::where('id', $userSubscription->subscription_id)->first();
            
                $userSubscription = [
                    'is_accepted' => (bool) $userSubscription->is_accepted,
                    'is_qualifying_period' => (bool) $userSubscription->is_qualifying_period,
                    'referral_no' => $userSubscription->referral_no,
                    'start_date' => $userSubscription->start_date,
                    'end_date' => $userSubscription->end_date,
                    'free_plan' => (bool) $userSubscription->free_plan,
                    'is_dependent' => (bool) $userSubscription->is_dependent,
                    
                ];
            
            $registration = $user->registration;
           
            $registrationData = [
                'heart_problems' => (bool) $registration->heart_problems,
                'diabetes' => (bool) $registration->diabetes,
                'allergic' => (bool) $registration->allergic,
                'allergic_medication_list' => $registration->allergic_medication_list,
                'phone_number' => $registration->phone_number,
                    'race' => $registration->race,
                    'gender' => $registration->gender,
                    'nationality' => $registration->nationality,
                    'dob' => $registration->dob,
                    'address' => $registration->address,                   
                    'address2' => $registration->address2,
                    'postcode' => $registration->postcode,
                    'city' => $registration->city,
                    'ic_number' => $registration->ic_number,
                    'state' => $registration->state,
                    'first_name' => $registration->first_name,
                    'last_name' => $registration->last_name,
                    'id' => $registration->id,
            ];
            
            
            //dd($subscriptiondata);
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    
                ],
                'registration' => $registrationData,
                'subscription_details' => $subscriptiondata,               
                'user_subscription' => $userSubscription,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'User or registration not found.'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return response()->json([
                'error' => 'An unexpected error occurred.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function DependentRemove(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $depId = $request->input('dependent_id');

            $dependent = UserSubscription::where('referral_id', $userId)->where('is_dependent',1)
                ->where('user_id', $depId)
                ->first();
            //dd($dependent);
            if (!$dependent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dependent not found.'
                ], 404);
            }
            
            $user = User::where('id',$depId)->first();
            
            $inviteUser = InviteUser::where('to_mail', $user->email)
                                            ->where('is_revoke', 0)
                                            ->first();
            
            // $subscriptiondetails = SubscriptionMaster::where('id', $dependent->subscription_id)->first();
            if($inviteUser)
            {
               if ($inviteUser->type_dependant == "Adult") {
                    UserSubscription::where('user_id', $userId)->increment('adult_count');
                } elseif ($inviteUser->type_dependant == "Child") {
                    UserSubscription::where('user_id', $userId)->increment('child_count');
                } elseif ($inviteUser->type_dependant == "Senior") {
                    UserSubscription::where('user_id', $userId)->increment('senior_count');
                } 
            }
            else
            {
                $registration = Registration::where('id', $user->reg_id)->first();
            
                $age_group = 'unknown';
                
                if($registration)
                {
                    $dob = \Carbon\Carbon::parse($registration->dob);
                    $today = \Carbon\Carbon::today();
                    
                    $childCutoff = $today->copy()->subYears(18);  // > 18 years
                    $adultCutoff = $today->copy()->subYears(60);  // ≥ 60 years is senior
                    
                    if ($dob->greaterThan($childCutoff)) {
                        $age_group = 0; // Child
                    } elseif ($dob->greaterThan($adultCutoff)) {
                        $age_group = 1; // Adult (between 18 and <60)
                    } else {
                        $age_group = 2; // Senior (60+)
                    }
                    
                    if ($age_group == 1) {
                        UserSubscription::where('user_id', $userId)->increment('adult_count');
                    } elseif ($age_group == 0) {
                        UserSubscription::where('user_id', $userId)->increment('child_count');
                    } elseif ($age_group == 2) {
                        UserSubscription::where('user_id', $userId)->increment('senior_count');
                    }
                }
            }
            
                $userSubscription = UserSubscription::where('user_id', $userId)->first();
            
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
            
                    $zohoUpdateData = [
                        'data' => [
                            [
                                'adult_count' => $userSubscription->adult_count,
                                'senior_count' => $userSubscription->senior_count,
                                'child_count' => $userSubscription->child_count,
                            ],
                        ],
                    ];
            
                    $zohoUrl = "https://www.zohoapis.com/crm/v2/User_Subscriptions/{$userSubscription->zoho_record_id}";
            
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put($zohoUrl, $zohoUpdateData);
            
            $dependent->update([
                'is_removed' => 1,
                'is_dependent' => 1,
                'count' => 0,
                'adult_count' => 0,
                'senior_count' => 0,
                'child_count' => 0,
                'slot_count' => 0,
                't_emergency_calls' => 2,
                't_clinic_calls' => 2,
                'is_active' =>0,
            ]);
            
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
            
                    $zohoUpdateData = [
                        'data' => [
                            [
                                'is_removed' => 1,
                                'is_dependent' => 1,
                                'count' => 0,
                                'adult_count' => 0,
                                'senior_count' => 0,
                                'child_count' => 0,
                                'slot_count' => 0,
                                't_emergency_calls' => 2,
                                't_clinic_calls' => 2,
                                'is_active' =>0,
                            ],
                        ],
                    ];
            
                    $zohoUrl = "https://www.zohoapis.com/crm/v2/User_Subscriptions/{$dependent->zoho_record_id}";
            
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put($zohoUrl, $zohoUpdateData);


            $notifyUser = User::where('id', $dependent->user_id)->first();

           if($notifyUser && $notifyUser->device_token){
                NotificationUser::create([
                    'form_user_id' => $userId,
                    'to_user_id' => $notifyUser->id,
                    'title' => '⚠ Membership Update',
                    'type' => 'notification',
                    'body' => 'You’ve been removed from '.$request->user()->name.' First Health plan.',
                    'is_sent' => 1,
                    'created_by' => $userId,
                ]);
                $deviceToken = $notifyUser->device_token;
                $title = '⚠ Membership Update';
                $body = 'You’ve been removed from '.$request->user()->name.' First Health plan.';

                $this->firebaseService->sendNotification($deviceToken, $title, $body);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Dependent user removed successfully.',
                'data' => $dependent
            ], 200);

        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (Exception $e) {
            Log::error('Failed to remove dependent user: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process your request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function invitedUserList(Request $request)
    {
        try {
            // Get the user ID from the authenticated user
            $userId = $request->user()->id;

            // Retrieve the user's valid invitations for dependents
            $inviteUsers = InviteUser::where('user_id', $userId)->where('status', 1)->get();

            // Check if any invite users exist
            if ($inviteUsers->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid InviteUser found for the user.'
                ], 404);
            }

            // Return success response with the list of invite users
            return response()->json([
                'status' => 'success',
                'message' => 'Invited User list retrieved successfully.',
                'data' => $inviteUsers
            ], 200);

        } catch (\Exception $e) {
            // Log the error details
            Log::error('Failed to retrieve invited users: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id,
                'request_data' => $request->all()
            ]);

            // Return a generic error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve invited users.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateInviteStatus(Request $request)
    {
        try {
            // Get the user ID from the authenticated user
            $userId = $request->user()->id;

            // Validate request data
            $validatedData = $request->validate([
                'email' => 'required|email',
                'type' => 'required|string',
                'status' => 'required|boolean'
            ]);

            $email = $validatedData['email'];
            $dependentType = $validatedData['type'];
            $status = $validatedData['status'];

            // Retrieve the user's valid invitation for the dependent
            $inviteUser = InviteUser::where('user_id', $userId)
                ->where('to_mail', $email)
                ->where('type_dependant', $dependentType)
                ->first();

            // Check if the invite user exists
            if (!$inviteUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid InviteUser found for the user.'
                ], 404);
            }

            // Update the invite user's status field
            $inviteUser->is_accepted = $status;
            $inviteUser->save();

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Invite status updated successfully.',
                'data' => $inviteUser
            ], 200);

        } catch (\Exception $e) {
            // Log the error details
            Log::error('Failed to update invite status: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id,
                'request_data' => $request->all()
            ]);

            // Return an error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update invite status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function moreInfo(Request $request,$id)
    {
        try {

            // Get the user ID from the request
            $userId = $request->user()->id;

            // Retrieve user's subscription details for dependents
            $userSubscription = UserSubscription::where('user_id', $userId)
                ->select('referral_no', 't_clinic_calls', 'r_clinic_calls', 't_emergency_calls', 'r_emergency_calls', 'start_date')
                ->first();

            if (!$userSubscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid subscription found for the user.'
                ], 404);
            }

            // Retrieve dependent details
            $dependent = Registration::find($id);

            if (!$dependent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dependent not found.'
                ], 404);
            }

            // Get the type of the dependent (validate is "Child")
            $dependentType = UserSubscription::where('reg_id', $id)->first();

            if (!$dependentType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dependent type not found or does not match the type "Child".'
                ], 404);
            }

            // Prepare response data
            $dependentInfo = [
                'reg_id' => $dependent->id,
                'membership_id' => $userSubscription->referral_no,
                'membership_type' => $dependentType->type_dependant,
                'membership_since' => $userSubscription->start_date,
                'total_emergency_calls' => $userSubscription->t_emergency_calls,
                'remaining_emergency_calls' => $userSubscription->r_emergency_calls,
                'total_non_emergency_calls' => $userSubscription->t_clinic_calls,
                'remaining_non_emergency_calls' => $userSubscription->r_clinic_calls,
                'first_name' => $dependent->first_name,
                'last_name' => $dependent->last_name,
                'full_name' => $dependent->first_name . ' ' . $dependent->last_name,
                'email' => $dependent->email,
                'dob' => $dependent->dob,
                'race' => $dependent->race,
                'phone_number' => $dependent->phone_number,
                'gender' => $dependent->gender,
                'nationality' => $dependent->nationality,
                'address' => $dependent->address,
                'heart_problems' => $dependent->heart_problems,
                'diabetes' => $dependent->diabetes,
                'allergic' => $dependent->allergic,
                'allergic_medication_list' => $dependent->allergic_medication_list

            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Dependent details retrieved successfully.',
                'data' => $dependentInfo
            ], 200);

        } catch (\Exception $e) {
            // Log the error with details
            Log::error('Failed to retrieve dependent details: ' . $e->getMessage(), [
                'exception' => $e,
                'requestData' => $request->all()
            ]);

            // Return an error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dependent details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function purchaseSlots(Request $request)
    {
        try {
            $request->validate([
                // 'Adult' => 'required',
                // 'Senior' => 'required',
                // 'Child' => 'required',
                'amount' => 'required',
            ]);

            $userId = $request->user()->id;

            $userSubscription = UserSubscription::where('user_id', $userId)->first();
            
            $totalCount = $request->Adult + $request->Senior + $request->Child + $userSubscription->slot_count;

            if ($totalCount > 10) {
                return response()->json(['error' => 'The total count of adult, senior, and child cannot exceed 10'], 400);
            }
            
            $user_details = User::where('id',$userId)->first();
            
    
            if (!$userSubscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid subscription found for the user.'
                ], 404);
            }
            $subscriptionData = [
                'user_id' => $userId,
                'adult_count' => $request->input('Adult'),
                'senior_count' => $request->input('Senior'),
                'child_count' => $request->input('Child'),
            ];
            PurchaseSlot::create($subscriptionData);

            //$userSubscription->adult_count += $request->input('Adult');
            //$userSubscription->senior_count += $request->input('Senior');
            //$userSubscription->child_count += $request->input('Child');
            //$userSubscription->slot_count = $totalCount;
            $userSubscription->amount += $request->input('amount');
            $userSubscription->save();
            
            // Zoho CRM Update Logic
                $crmStatus = 'success';
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
        
                $zohoData = [
                    'data' => [
                        [
                            'amount' => $userSubscription->amount,
                        ],
                    ],
                ];
        
                $module = 'User_Subscriptions';
                $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
        
                if ($userSubscription->zoho_record_id) {
                    $recordId = $userSubscription->zoho_record_id;
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put("$crmUrl/$recordId", $zohoData);
                    
                   // dd($response);
        
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
            
            $amount = $request->amount;  
           
            if ($amount > 0) {
                
                putenv('SENANGPAY_MERCHANT_ID=310172620120861');
                putenv('SENANGPAY_SECRET_KEY=7053-332');
                
                $merchant_id = getenv('SENANGPAY_MERCHANT_ID');
                $secret_key = getenv('SENANGPAY_SECRET_KEY');
                
                $reg_details = Registration::findOrFail($user_details->reg_id);
    
                //$order_id = $userSubscription->id; 
                $name = $reg_details->first_name;
                $email = $user_details->email;
                $phone = $reg_details->phone_number;
                //$detail = 'Subscription_payment';
                
                $detail = urldecode('Subscription_payment');
                $amount = urldecode($request->amount);
                //$order_id = urldecode($userSubscription->id);
                
                $random_number = mt_rand(100, 999);
                
                $payment = new Payment();
                $payment->user_id = $userSubscription->user_id; // use an existing user ID
                $payment->subscription_id = $userSubscription->subscription_id; // use an existing subscription ID
                $payment->transaction_id = NULL;
                $payment->amount = $amount;
                $payment->status = 2;
                $payment->random_no = $random_number;
                $payment->payment_method = 'Purchase';
                $payment->payment_date = now();
                $payment->save();
                
                $order_id = $userSubscription->id . 'FH' . $random_number;
                
                $hash = hash_hmac('sha256', $secret_key . $detail . $amount . $order_id, $secret_key);

               $senangPayUrl = "https://app.senangpay.my/payment/{$merchant_id}";
    
                $data = [
                    'detail' => 'Subscription_payment',
                    'amount' => $amount,
                    'order_id' => $order_id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'hash' => $hash,
                ];
    
                return response()->json([
                    'url' => $senangPayUrl,
                    'data' => $data,
                    'crmStatus' => $crmStatus,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Dependent purchase slots updated successfully.',
                'data' => $userSubscription,
                'crmStatus' => $crmStatus
            ], 200);

        } catch (\Exception $e) {
            // Log the error with details
            Log::error('Failed to update dependent slots: ' . $e->getMessage(), [
                'exception' => $e,
                'requestData' => $request->all()
            ]);

            // Return an error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update dependent slots.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function purchaseSlotUpdate(Request $request)
    {
        try {
           
            
            $request->validate([
                //'user_id' => 'required',
                'Adult' => 'required',
                'Senior' => 'required',
                'Child' => 'required',
            ]);
            
            $userId = $request->user()->id;

            $userSubscription = UserSubscription::where('user_id', $userId)->first();
            
            $totalCount = $request->Adult + $request->Senior + $request->Child + $userSubscription->slot_count;

            if ($totalCount > 10) {
                return response()->json(['error' => 'The total count of adult, senior, and child cannot exceed 10'], 400);
            }
            
            $user_details = User::where('id',$userId)->first();
            

            if (!$userSubscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid subscription found for the user.'
                ], 404);
            }
            $subscriptionData = [
                'user_id' => $userId,
                'adult_count' => $request->input('Adult'),
                'senior_count' => $request->input('Senior'),
                'child_count' => $request->input('Child'),
            ];
            PurchaseSlot::create($subscriptionData);

            $userSubscription->adult_count += $request->input('Adult');
            $userSubscription->senior_count += $request->input('Senior');
            $userSubscription->child_count += $request->input('Child');
            $userSubscription->slot_count = $totalCount;
            $userSubscription->save();
            
                    $crmController = new CRMController();
                    $accessToken = $crmController->getZohoAccessToken();
            
                    $zohoUpdateData = [
                        'data' => [
                            [
                                'adult_count' => $userSubscription->adult_count,
                                'senior_count' => $userSubscription->senior_count,
                                'child_count' => $userSubscription->child_count,
                                'slot_count' => $userSubscription->slot_count,
                            ],
                        ],
                    ];
            
                    $zohoUrl = "https://www.zohoapis.com/crm/v2/User_Subscriptions/{$userSubscription->zoho_record_id}";
            
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put($zohoUrl, $zohoUpdateData);
                    
                    //dd($response->json());
            
                    if (!$response->successful()) {
                        Log::error('Failed to update subscription in Zoho CRM', ['response' => $response->json()]);
                    }
                
            return response()->json(['message' => 'Purchase slots updated successfully'], 200);

            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }

    public function releaseSlot(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;

            $inviteUser = InviteUser::where('id', $id) // Assuming $id is userId here
                ->where('status', 1)->where('is_revoke',0)
                ->first();

            if ($inviteUser) {
                $inviteUser->update(['is_release_slot' => 1]);
                
                $dependentType = $inviteUser->type_dependant;
                
                $userSubscription = UserSubscription::where('user_id', $inviteUser->user_id)->first();
    
                if (!$userSubscription) {
                    return response()->json([
                        'message' => 'User subscription not found.'
                    ], 404);
                }
                
                if ($userSubscription) {
                    if ($dependentType === 'Senior') {
                        if ($userSubscription->senior_count >= 0) {
                            $userSubscription->increment('senior_count');
                        } else {
                            return response()->json([
                                'message' => 'No more senior dependent slots available.'
                            ], 400);
                        }
                    } elseif ($dependentType === 'Adult') {
                        if ($userSubscription->adult_count >= 0) {
                            $userSubscription->increment('adult_count');
                        } else {
                            return response()->json([
                                'message' => 'No more adult dependent slots available.'
                            ], 400);
                        }
                    }elseif ($dependentType === 'Child') {
                        if ($userSubscription->child_count >= 0) {
                            $userSubscription->increment('child_count');
                        } else {
                            return response()->json([
                                'message' => 'No more child dependent slots available.'
                            ], 400);
                        }
                    }
                }
                
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
            
                    $zohoUpdateData = [
                        'data' => [
                            [
                                'adult_count' => $userSubscription->adult_count,
                                'senior_count' => $userSubscription->senior_count,
                                'child_count' => $userSubscription->child_count,
                            ],
                        ],
                    ];
            
                    $zohoUrl = "https://www.zohoapis.com/crm/v2/User_Subscriptions/{$userSubscription->zoho_record_id}";
            
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put($zohoUrl, $zohoUpdateData);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Dependent slots have been updated successfully.',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active invitation found for this user.',
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Failed to update dependent slots: ' . $e->getMessage(), [
                'exception' => $e,
                'requestData' => $request->all(),
            ]);

            // Return an error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update dependent slots.',
                'error' => $e->getMessage(), // Optionally return the error message
            ], 500);
        }
    }

    public function revokeDependent(Request $request, $id)
    {
        try {
            $inviteUser = InviteUser::where('id', $id) 
                ->where('status', 1)
                ->first();

            if ($inviteUser) {
                $inviteUser->update(['is_revoke' => 1]);
                
                $dependentType = $inviteUser->type_dependant;
                
                $userSubscription = UserSubscription::where('user_id', $inviteUser->user_id)->first();
    
                if (!$userSubscription) {
                    return response()->json([
                        'message' => 'User subscription not found.'
                    ], 404);
                }
                
                $membership = SubscriptionMaster::where('id', $userSubscription->subscription_id)->first();
                
                $membership_name = $membership->plan;
                
                //dd($userSubscription);
                
                if ($userSubscription) {
                    if ($dependentType === 'Senior') {
                        if ($userSubscription->senior_count >= 0) {
                            $userSubscription->increment('senior_count');
                        } else {
                            return response()->json([
                                'message' => 'No more senior dependent slots available.'
                            ], 400);
                        }
                    } elseif ($dependentType === 'Adult') {
                        if ($userSubscription->adult_count >= 0) {
                            $userSubscription->increment('adult_count');
                        } else {
                            return response()->json([
                                'message' => 'No more adult dependent slots available.'
                            ], 400);
                        }
                    }elseif ($dependentType === 'Child') {
                        if ($userSubscription->child_count >= 0) {
                            $userSubscription->increment('child_count');
                        } else {
                            return response()->json([
                                'message' => 'No more child dependent slots available.'
                            ], 400);
                        }
                    }
                }
                
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
            
                    $zohoUpdateData = [
                        'data' => [
                            [
                                'adult_count' => $userSubscription->adult_count,
                                'senior_count' => $userSubscription->senior_count,
                                'child_count' => $userSubscription->child_count,
                            ],
                        ],
                    ];
            
                    $zohoUrl = "https://www.zohoapis.com/crm/v2/User_Subscriptions/{$userSubscription->zoho_record_id}";
            
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put($zohoUrl, $zohoUpdateData);
                    
                $db_user = User::where('id', $inviteUser->user_id)->first();
                $registerUser = Registration::where('id', $db_user->reg_id)->first();

                $content = $registerUser->first_name;

                // Create the notification entry
                NotificationUser::create([
                    'form_user_id' => $db_user->id,
                    'to_email' => $inviteUser->to_mail,
                    'title' => 'email',
                    'type' => 'email',
                    'body' => $content,
                    'is_sent' => 1,
                    'created_by' => $db_user->id,
                ]);
                
                $content = [
                    'user_name' => $registerUser->first_name,
                    'membership_name' => $membership_name,
                    'message' => 'Your invitation has been revoked.'
                ];
                
                //dd($content);

                // Send the revocation email
                Mail::to($inviteUser->to_mail)->send(new RevokeMail($content, 'Invitation Revoked'));

                return response()->json([
                    'status' => 'success',
                    'message' => 'Dependent invitation has been successfully revoked.',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active invitation found for this user.',
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Failed to revoke dependent invitation: ' . $e->getMessage(), [
                'exception' => $e,
                'requestData' => $request->all(),
            ]);

            // Return an error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to revoke dependent invitation.',
                'error' => $e->getMessage(), // Optionally return the error message
            ], 500);
        }
    }
    
    public function ManualRemove(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $manuId = $request->input('reg_id');

            $dependent = UserSubscription::where('referral_id', $userId)->where('is_dependent',1)
                ->where('reg_id', $manuId)
                ->first();
            
            if (!$dependent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dependent not found.'
                ], 404);
            }
            
            if ($dependent->type_dependant == "Adult") {
                UserSubscription::where('user_id', $userId)->increment('adult_count');
            } elseif ($dependent->type_dependant == "Child") {
                UserSubscription::where('user_id', $userId)->increment('child_count');
            } elseif ($dependent->type_dependant == "Senior") {
                UserSubscription::where('user_id', $userId)->increment('senior_count');
            }
                $userSubscription = UserSubscription::where('user_id', $userId)->where('is_dependent',0)->first();
            
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
            
                    $zohoUpdateData = [
                        'data' => [
                            [
                                'adult_count' => $userSubscription->adult_count,
                                'senior_count' => $userSubscription->senior_count,
                                'child_count' => $userSubscription->child_count,
                            ],
                        ],
                    ];
            
                    $zohoUrl = "https://www.zohoapis.com/crm/v2/User_Subscriptions/{$userSubscription->zoho_record_id}";
            
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put($zohoUrl, $zohoUpdateData);
            
            $dependent->update([
                'is_removed' => 1,
                'is_dependent' => 1,
                'count' => 0,
                'adult_count' => 0,
                'senior_count' => 0,
                'child_count' => 0,
                't_emergency_calls' => 2,
                't_clinic_calls' => 2,
                'is_active' =>0,
            ]);
            
                    $crmController = new CRMController();
                    $accessToken = $crmController->getZohoAccessToken();
            
                    $zohoUpdateData = [
                        'data' => [
                            [
                                'is_removed' => 1,
                                'is_dependent' => 1,
                                'count' => 0,
                                'adult_count' => 0,
                                'senior_count' => 0,
                                'child_count' => 0,
                                't_emergency_calls' => 2,
                                't_clinic_calls' => 2,
                                'is_active' =>0,
                            ],
                        ],
                    ];
            
                    $zohoUrl = "https://www.zohoapis.com/crm/v2/User_Subscriptions/{$dependent->zoho_record_id}";
            
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put($zohoUrl, $zohoUpdateData);
            
            
           // dd($dependent);
            return response()->json([
                'status' => 'success',
                'message' => 'Manual user removed successfully.',
                'data' => $dependent
            ], 200);

        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (Exception $e) {
            Log::error('Failed to remove dependent user: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process your request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}


