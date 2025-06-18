<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionMaster;
use App\Models\BenefitMaster;
use App\Models\Registration;
use App\Models\Activity;
use App\Models\Member;
use App\Models\InviteUser;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\NotificationUser;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Api\CRMController;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebasePushNotificationService;

class SubscriptionController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebasePushNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    
    public function sublist()
    {
        try {
            // Retrieve all subscription plans with associated benefits
            $subscriptions = SubscriptionMaster::with('benefits')->get()->map(function ($subscription) {
                $subscription->key_benefits = json_decode($subscription->key_benefits, true); // Decode key_benefits JSON
                $subscription = $subscription->makeHidden(['created_at', 'updated_at']); // Hide unnecessary fields
    
                // Hide unnecessary fields in benefits
                $subscription->benefits->each(function ($benefit) {
                    $benefit->makeHidden(['pivot', 'created_at', 'updated_at']);
                });
    
                $subscription->free_plan = (bool) $subscription->free_plan; // Ensure free_plan is boolean
    
                return $subscription;
            });
    
            // Return the data as JSON response
            return response()->json($subscriptions, 200);
    
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve subscriptions: ' . $e->getMessage());
    
            return response()->json([
                'error' => 'Failed to retrieve subscriptions',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function subscriptionplans(Request $request)
    {
        $user_id = $request->input('user_id');
    
        try {
            // Get the user's DOB from the registration table
            $user = Registration::where('id', $user_id)->firstOrFail();
            $dob = $user->dob;
    
            $age = \Carbon\Carbon::parse($dob)->age;
            
            //dd($age);
    
            $subscriptions = SubscriptionMaster::with('benefits')->get()->map(function ($subscription) use ($age) {
                
                $subscription->key_benefits = json_decode($subscription->key_benefits, true);
                $subscription = $subscription->makeHidden(['created_at', 'updated_at']);
        
                $subscription->benefits->each(function ($benefit) {
                    $benefit->makeHidden(['pivot', 'created_at', 'updated_at']);
                });
                
                //dd($age);
    
                 if ($subscription->id == 1) { 
                    $subscription->eligible = ($age >= 18 && $age < 59) ? true : false;
                } elseif ($subscription->id == 2) { 
                    $subscription->eligible = ($age >= 59) ? true : false;
                } else {
                    $subscription->eligible = true;
                }
                
                $subscription->free_plan = (bool) $subscription->free_plan;
        
                return $subscription;
            });
        
            return response()->json($subscriptions, 200);
        
        } catch (\Exception $e) {
            
            \Log::error('Failed to retrieve subscriptions: ' . $e->getMessage());
        
            return response()->json([
                'error' => 'Failed to retrieve subscriptions',
                'message' => $e->getMessage()
            ], 500);
        }
    }




   public function store(Request $request)
    {
    try {
        // Validate the request
        $validatedData = $request->validate([
            'plan' => 'required|string|max:255',
            'price' => 'required|numeric',
            'eligible' => 'required|boolean',
            'free_plan' => 'required|boolean',
            'usual_price' => 'required|numeric',
            'key_benefits' => 'required|array',  // Ensures key_benefits is an array
            'benefits' => 'nullable|array',  // Benefits is optional but should be an array if present
            'benefits.*' => 'exists:benefit_masters,id',  // Each benefit ID should exist in benefit_masters table
        ]);

        
        $validatedData['key_benefits'] = json_encode($request->key_benefits);

        
        $subscription = SubscriptionMaster::create($validatedData);

       
        if ($request->has('benefits')) {
            $subscription->benefits()->attach($request->benefits);
        }

        return response()->json($subscription, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
          
            return response()->json([
                'error' => 'Validation Error',
                'messages' => $e->errors()
            ], 422);
    
        } catch (\Exception $e) {
            
            Log::error('Failed to store subscription: ' . $e->getMessage());
    
              return response()->json([
                'error' => 'Failed to store subscription',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
           
            $subscription = SubscriptionMaster::with('benefits')->findOrFail($id);
    
            
            $subscription->key_benefits = json_decode($subscription->key_benefits, true);
    
            
            $subscription = $subscription->makeHidden(['created_at', 'updated_at']);
    
            
            $subscription->benefits->each(function ($benefit) {
                $benefit->makeHidden(['pivot', 'created_at', 'updated_at']);
            });
    
            return response()->json($subscription, 200);
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Subscription not found: ' . $e->getMessage());
    
            
            return response()->json([
                'error' => 'Subscription not found',
                'message' => $e->getMessage()
            ], 404);
    
        } catch (\Exception $e) {
            // Log any other errors
            Log::error('Failed to retrieve subscription: ' . $e->getMessage());
    
            return response()->json([
                'error' => 'Failed to retrieve subscription',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $subscription = SubscriptionMaster::findOrFail($id);
        $subscription->update($request->all());

        if ($request->has('benefits')) {
            $subscription->benefits()->sync($request->benefits);
        }

        return response()->json($subscription);
    }
    
    public function getUserSubscriptionById(Request $request)
    {
        try {
            
            $user = Auth::user();
            
            //dd($user);
            
            $userSubscription = UserSubscription::with(['subscriptionMaster.benefits'])
                                                 ->where('user_id', $user->id)
                                                 ->first();
            
            if (!$userSubscription) {
                return response()->json(['error' => 'No subscription found with the provided referral number'], 404);
            }
            
            $fieldsToConvert = ['free_plan', 'is_dependent', 'is_plan_expired', 'is_removed','is_qualifying_period','is_manual', 'is_read']; 
            foreach ($fieldsToConvert as $field) {
                $userSubscription->$field = (bool) $userSubscription->$field;
            }
            
            $createdDate = Carbon::parse($userSubscription->start_date)->startOfDay();
            $currentDate = now();
            $daysPassed = $createdDate->diffInDays($currentDate);
            $qualifyingPeriod = 14;
            
            // remaining days count
            $endDate = Carbon::parse($userSubscription->end_date);
            $currentDate = now();
            $remainingDays = $currentDate->diffInDays($endDate, false); 
            
            if ($remainingDays < 0) {
                $remainingDays = 0; 
            }
            
            $userSubscription->remainingDays = $remainingDays;

            
            $isWithinQualifyingPeriod = $daysPassed <= $qualifyingPeriod;
            $daysLeftInQualifyingPeriod = max(0, $qualifyingPeriod - $daysPassed);
            
            if($userSubscription->plan_times > 1)
            {
                $userSubscription->is_qualifying_period = false;
            }else
            {
                $userSubscription->is_qualifying_period = $isWithinQualifyingPeriod;

            }
            
            $userSubscription->days_left_in_qualifying_period = $daysLeftInQualifyingPeriod;
            $userSubscription->slot_count = $userSubscription->slot_count;
            
            $endDate = $userSubscription->end_date;
            if ($endDate && $currentDate->greaterThan($endDate)) {
                $userSubscription->is_plan_expired = true;
            } else {
                $userSubscription->is_plan_expired = false;
            }

            
            $userSubscription->name = $user->name;
            $userSubscription->email = $user->email;
            
            $reg_details = Registration::where('id',$user->reg_id)->first();
            $userSubscription->dob = $reg_details->dob;
            $userSubscription->remain_slot = $userSubscription->adult_count + $userSubscription->senior_count + $userSubscription->child_count;
            //dd($userSubscription->is_dependent);
            
            $activity = Activity::where('user_id',$user->id)->count();
            $userSubscription->principal_user_activity = $activity;
            
            $principl_user = [];
           
           if($userSubscription->is_dependent == true)
           {
               $principl_user = UserSubscription::with(['subscriptionMaster.benefits'])
                ->where('user_id', $userSubscription->referral_id)->where('is_removed', 0)->first();
                
                foreach ($fieldsToConvert as $field) {
                    $principl_user->$field = (bool) $principl_user->$field;
                }
                
                //dd($principl_user);
                $princUserModel = User::where('id', $userSubscription->referral_id)->first();
                
                $reg_details = Registration::where('id',$princUserModel->reg_id)->first();
                     
                $principl_user->name = $princUserModel->name;
                $principl_user->email = $princUserModel->email;
                $principl_user->dob = $reg_details->dob;
           }

            
            $dependentUsers = UserSubscription::with(['subscriptionMaster.benefits'])
                ->where('referral_id', $user->id)->where('is_removed', 0)->get()
                 ->filter(function ($dependentUser) {
                    if ($dependentUser->user_id !== null) {
                        $dependentUserModel = User::find($dependentUser->user_id);
                        return $dependentUserModel && $dependentUserModel->is_active;
                    }
                    return true; 
                })
                ->map(function ($dependentUser) use ($fieldsToConvert) {
                    foreach ($fieldsToConvert as $field) {
                        $dependentUser->$field = (bool) $dependentUser->$field;
                    }
                    
                    $dependent_activity = 0;
                    
                    if($dependentUser->user_id != NULL)
                    {
                        $dependentUserModel = User::where('id', $dependentUser->user_id)->first();
                        $registration_details = Registration::where('id',$dependentUserModel->reg_id)->first();
                        $dependent_activity = Activity::where('user_id',$dependentUser->user_id)->count();
                     
                        $dependentUser->name = $dependentUserModel->name;
                        $dependentUser->email = $dependentUserModel->email;
                    }else
                    {
                        $registration_details = Registration::where('id',$dependentUser->reg_id)->first();
                        $dependentUser->name = $registration_details->first_name.' '.$registration_details->last_name;
                        $dependentUser->email = $registration_details->email;
                    }
                     
                     $dependentUser->dependent_user_activity = $dependent_activity;
                     $dependentUser->dob = $registration_details->dob;
                     $dependentUser->ic_number = $registration_details->ic_number;
                     $dependentUser->phone_number = $registration_details->phone_number;
                     $dependentUser->race = $registration_details->race;
                     $dependentUser->gender = $registration_details->gender == 0 ? 'male' : 'female';
                     $dependentUser->nationality = $registration_details->nationality;
                     $dependentUser->address = $registration_details->address;
                     $dependentUser->address2 = $registration_details->address2;
                     $dependentUser->postcode = $registration_details->postcode;
                     $dependentUser->city = $registration_details->city;
                     $dependentUser->state = $registration_details->state;
                     $dependentUser->country = $registration_details->country;
                     $dependentUser->medical_info = (bool) $registration_details->medical_info;
                     $dependentUser->heart_problems = (bool) $registration_details->heart_problems;
                     $dependentUser->diabetes = (bool) $registration_details->diabetes;
                     $dependentUser->allergic = (bool) $registration_details->allergic;
                     $dependentUser->allergic_medication_list = $registration_details->allergic_medication_list;
                     $dependentUser->are_u_foreigner = (bool) $registration_details->are_u_foreigner;
                     $dependentUser->passport_no = $registration_details->passport_no;
                     
                    
                    // dd($dependentUser);
                    
                    $createdDate = $dependentUser->created_at;
                    $currentDate = now();
                    $isWithinQualifyingPeriod = $createdDate->diffInDays($currentDate) <= 14;
                    $dependentUser->is_qualifying_period = $isWithinQualifyingPeriod;
    
                    return $dependentUser;
                });
                
            $type_dependent_child_count = UserSubscription::where('referral_id', $user->id)->where('is_removed', 0)->where('type_dependant' , "Child")->count();
                
            $inviteUsers = InviteUser::where('user_id', $user->id)->where('status', 1)->where('is_revoke', 0)->where('is_release_slot',0)->get(['id','to_mail','is_removed','type_dependant','is_accepted','is_revoke','is_release_slot'])
                    ->map(function ($inviteUser) {
                    return [
                        'id' => $inviteUser->id,
                        'to_mail' => $inviteUser->to_mail,
                        'type_dependant' => $inviteUser->type_dependant,
                        'is_accepted' => $inviteUser->is_accepted,
                        'is_removed' => $inviteUser->is_removed ? true : false,
                        'is_revoke' => $inviteUser->is_revoke ? true : false,
                        'is_release_slot' => $inviteUser->is_release_slot ? true : false,
                    ];
                });
            //$allUsers = $dependentUsers->merge($inviteUsers);
            
            $remain_child = $userSubscription->child_count;
            
            $manual_child = $type_dependent_child_count;
            
            $invite_child = InviteUser::where('user_id', $user->id)->where('status', 1)->where('is_revoke', 0)->where('is_release_slot',0)->where('is_accepted',0)->count();
            
            $total_purchased_child = $remain_child + $manual_child + $invite_child;
            
            
    
            return response()->json([
                'user_subscription' => $userSubscription,
                'dependent_users' => $dependentUsers,
                'principl_user' => $principl_user,
                'invite_user' => $inviteUsers,
                'dependent_child_count' => $type_dependent_child_count,
                'total_purchased_child' => $total_purchased_child ?? ''
                
            ], 200);

    
        } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            // Log the error for debugging
           // return $e->getMessage();
            Log::error('Error in getUserSubscriptionById: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }

    public function destroy($id)
    {
        try {
           
            $subscription = SubscriptionMaster::findOrFail($id);
            
            $benefitIds = $subscription->benefits->pluck('id')->toArray();
            
            $subscription->benefits()->detach();
    
            $subscription->delete();
    
           
            return response()->json([
                'message' => 'Subscription deleted successfully'
            ], 200);
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            
            Log::error('Subscription not found: ' . $e->getMessage());
    
            return response()->json([
                'error' => 'Subscription not found',
                'message' => $e->getMessage()
            ], 404);
    
        } catch (\Exception $e) {
           
            Log::error('Failed to delete subscription: ' . $e->getMessage());
    
            return response()->json([
                'error' => 'Failed to delete subscription',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
   public function refUserSubscription(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:registrations,id',
                'referral_no' => 'required|string',
                'age_group' => 'required|integer|in:0,1,2', // 0 for child, 1 for adult, 2 for senior
                'accepted' => 'required|boolean',
            ]);
            
             //dd("dd");
    
            $user = Registration::findOrFail($request->user_id);
            
            $userid = User::where('reg_id',$request->user_id)->first();
            
            $userSub = UserSubscription::where('referral_no', $request->referral_no)->first();
            
            $userSubscription = UserSubscription::where('user_id', $userid->id)->first();
            
            $inviteUser = InviteUser::where('to_mail', $user->email)
                                            ->where('is_revoke', 0)
                                            ->first();
            
                // Map age group to type
                $ageTypeMap = [
                    0 => 'Child',
                    1 => 'Adult',
                    2 => 'Senior',
                ];
                
            $age = $request->age_group;
            
            if ($user) {
                if (!$userSubscription) {
                    $userSubscription = new UserSubscription();
                    $userSubscription->user_id = $userid->id;
                }
                
                $userSubscription->referral_id = $userSub->user_id;
                $userSubscription->referral_no = null; // Remove referral number
                $userSubscription->subscription_id = $userSub->subscription_id;
                $userSubscription->start_date = Carbon::now();  
                $userSubscription->end_date = $userSub->end_date; 
                
                $userSubscription->is_accepted = $request->accepted;
                
               //dd($userSub);
                if ($userSubscription->is_accepted == 1) {
                    
                    if ($inviteUser) {
                
                        $type = $inviteUser->type_dependant;
                    
                        if (!isset($ageTypeMap[$age]) || $ageTypeMap[$age] !== $type) {
                            return response()->json([
                                'status' => false,
                                'message' => 'Access restricted: age group does not match the dependent type.',
                            ], 403);
                        }
                    }
                   //dd($age);
    
                    if ($inviteUser) {
                        $inviteUser->is_accepted = 1;
                        $inviteUser->save();
                    }
                    else {
                        //dd($age);
                        switch ($age) {
                            case 0:
                                $userSub->child_count = max(0, $userSub->child_count - 1);
                                break;
                            case 1:
                                $userSub->adult_count = max(0, $userSub->adult_count - 1);
                                break;
                            case 2:
                                $userSub->senior_count = max(0, $userSub->senior_count - 1);
                                break;
                        }
                        $userSub->save(); // Save the updated counts
                    }
                    $userSubscription->is_dependent = 1;
                    $userSubscription->type_dependant = $ageTypeMap[$age] ?? NULL;
                    $userSubscription->adult_count = 0;
                    $userSubscription->senior_count = 0;
                    $userSubscription->child_count = 0;
                    $userSubscription->free_plan = 0;
                    $userSubscription->is_removed = 0;
                    $userSubscription->save();
                    
                    $crmStatus = 'success'; 
                    $recordId = null;
                    
                    $membership = "";
                
                    $subscriptionId = $userSub->subscription_id ? $userSub->subscription_id : null;
            
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
                                    'Name' => $userid->name,
                                    'u_id' => $userid->id,
                                    'subscription_id' => $userSub->subscription_id,
                                    'referral_no' => null,
                                    'start_date' => Carbon::parse($userSubscription->start_date)->format('Y-m-d'),
                                    'end_date' => Carbon::parse($userSub->end_date)->format('Y-m-d'),
                                    'is_accepted' => $request->accepted,
                                    'adult_count' => 0,
                                    'senior_count' => 0,
                                    'child_count' => 0,
                                    'free_plan' => 0,
                                    'is_dependent' => 1,
                                    'referral_id' => $userSub->user_id,
                                    'Membership' => $membership,
                                ],
                            ],
                        ];
                    
                        $module = 'User_Subscriptions';
                        $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                    
                        if (empty($userSubscription->zoho_record_id)) {
                            // New Record: Insert
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
                                }
                                // return response()->json(['message' => 'Record inserted successfully',
                                // 'is_accepted' => $userSubscription->is_accepted, 
                                // 'crmStatus' => $crmStatus], 201);
                            } else {
                                return response()->json(['error' => 'Failed to insert record'], 500);
                            }
                        } else {
                            // Existing Record: Update
                            $recordId = $userSubscription->zoho_record_id;
                            $response = Http::withHeaders([
                                'Authorization' => "Zoho-oauthtoken $accessToken",
                                'Content-Type' => 'application/json',
                            ])->put("$crmUrl/$recordId", $zohoData);
                    
                            if ($response->successful()) {
                                // return response()->json(['message' => 'Record updated successfully',
                                // 'is_accepted' => $userSubscription->is_accepted, 
                                // 'crmStatus' => $crmStatus
                                // ], 200);
                            } else {
                                return response()->json(['error' => 'Failed to update record'], 500);
                            }
                        }
                    } catch (\Exception $e) {
                        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
                    }

                
                }
                else if ($userSubscription->is_accepted == 0) {
                    $crmStatus = 'no need'; 
                    $inviteUser = InviteUser::where('to_mail', $user->email)
                        ->where('user_id', $userSub->user_id)
                        ->where('is_revoke', 0)
                        ->first();
                    // return $inviteUser;
                    if ($inviteUser) {
                        // If a matching InviteUser is found, update it
                        $inviteUser->is_removed = 1;
                        $inviteUser->save();
                    }
                }

                if ($user) {
                    
                    $user_token = User::where('id', $userSub->user_id)->first();
                    
                    if($user_token && $user_token->device_token){
                        $isAccepted = $userSubscription->is_accepted == 1;
                        $userName = "{$user->first_name} {$user->last_name}";
        
                        $body = $isAccepted
                            ? "Your dependant, $userName has just accepted your invite to the First Health subscription plan."
                            : "Your dependant, $userName has just rejected your invite to the First Health subscription plan.";
    
                        // Create the main notification
                        NotificationUser::create([
                            'form_user_id' => $user->id,
                            'to_user_id' => $user_token->id,
                            'type' => 'notification',
                            'title' => 'First Health',
                            'body' => $body,
                            'is_sent' => 1,
                            'created_by' => $user->id,
                        ]);
    
                        // Send notification via Firebase with a unique collapse key
                        $this->firebaseService->sendNotification($user_token->device_token, 'Invite Update', $body, ['collapse_key' => 'referral_response']);
    
                        // Handle rejection case
                        if (!$isAccepted) {
                            $rejectionBody = 'Exciting news! '.$userName.' is now your main account holder for your plan.';
    
                            // Create additional notification for rejection
                            NotificationUser::create([
                                'form_user_id' => $user->id,
                                'to_user_id' => $user_token->id,
                                'type' => 'notification',
                                'title' => 'New Primary User ',
                                'body' => $rejectionBody,
                                'is_sent' => 1,
                                'created_by' => $user->id,
                            ]);
    
                            // Introduce a slight delay before sending the second notification
                            usleep(500000); // 500ms delay
    
                            // Send rejection notification via Firebase with a unique collapse key
                            $this->firebaseService->sendNotification($user_token->device_token, 'New Primary User ', $rejectionBody, ['collapse_key' => 'slot_release']);
                        }
                        
                    }

                    
                }
                
        
                return response()->json(['message' => 'Record inserted successfully',
                                'is_accepted' => $userSubscription->is_accepted, 
                                'crmStatus' => $crmStatus], 201);
            }
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing your request'->$e], 500);
        }
    }
    
    public function downgradePlan(Request $request)
    {
        try {
            $user = Auth::user();
    
            $userSubscription = UserSubscription::where('user_id', $user->id)->first();
    
            if ($userSubscription && $userSubscription->referral_no && $userSubscription->is_dependent == 0) {
                $userSubscription->subscription_id = 3;
                $userSubscription->adult_count = 0;
                $userSubscription->senior_count = 0;
                $userSubscription->child_count = 0;
                $userSubscription->free_plan = 1;

                $userSubscription->save();
                
                $dependentUsers = UserSubscription::where('referral_id', $user->id)
                                                ->where('is_dependent', 1)
                                                ->get();

                foreach ($dependentUsers as $dependentUser) {
                    $dependentUser->subscription_id = 3; 
                    $dependentUser->is_removed = 1; 
                    $dependentUser->free_plan = 1;
                    $dependentUser->save();

                    $notifyUser = User::where('id', $userSubscription->user_id)->first();
                    $dependentUserdetails = User::where('id', $dependentUser->user_id)->first();

                    if ($notifyUser && $dependentUserdetails && $dependentUserdetails->device_token) {
                        $deviceToken = $dependentUserdetails->device_token;

                        if (!empty($deviceToken)) {
                            NotificationUser::create([
                                'form_user_id' => $notifyUser->id,
                                'to_user_id' => $dependentUserdetails->id,
                                'title' => 'First Health',
                                'type' => 'notification',
                                'body' => "{$notifyUser->name} has downgraded their subscription plan.",
                                'is_sent' => 1,
                                'created_by' => $notifyUser->id,
                            ]);

                            $title = 'First Health';
                            $body = "{$notifyUser->name} has downgraded their subscription plan.";

                            $this->firebaseService->sendNotification($deviceToken, $title, $body);
                        }
                    } else {
                        \Log::error('Dependent user or notify user not found for subscription downgrade. Principal User ID: ' . ($notifyUser ? $notifyUser->id : 'N/A') . ', Dependent User: ' . ($dependentUserdetails ? $dependentUserdetails->name : 'N/A') . ' (ID: ' . ($dependentUserdetails ? $dependentUserdetails->id : 'N/A') . ')');
                    }
                }
    
                return response()->json([
                    'status' => 'success',
                    'message' => 'Subscription plan downgraded successfully'
                ], 200);
            } else if($userSubscription->referral_id && $userSubscription->is_dependent == 1) {
                
                $userSubscription->free_plan = 1;
                $userSubscription->subscription_id = 3;
                $userSubscription->save();
                
                /*when the primary user remove the dependent then dependent need to downgrade the plan on that time we will delete the invited email because its create error InviteUser
                refUserSubscription function */
                $inviteUser = InviteUser::where('to_mail', $user->email)
                                            ->where('is_revoke', 0)
                                            ->first();
                if ($inviteUser) {
                    $inviteUser->delete();
                }
                
                //notify to principal user
                $notifyUser = User::where('id', $userSubscription->referral_id)->first();
                $dependentUser = User::where('id', $userSubscription->user_id)->first();

                if($notifyUser && $notifyUser->device_token){
                    NotificationUser::create([
                        'form_user_id' => $dependentUser->id,
                        'to_user_id' => $notifyUser->id,
                        'title' => 'First Health',
                        'type' => 'notification',
                        'body' => "{$dependentUser->name} has downgraded their subscription plan.",
                        'is_sent' => 1,
                        'created_by' => $dependentUser->id,
                    ]);

                    $deviceToken = $notifyUser->device_token;
                    $title = 'First Health';
                    $body = "{$dependentUser->name} has downgraded their subscription plan.";

                    $this->firebaseService->sendNotification($deviceToken, $title, $body);
                }

                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Subscription plan downgraded successfully'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while downgrading the subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function renewSlot(Request $request)
    {
        try {
            $user = Auth::user();
    
            $request->validate([
                // 'adult_count' => 'nullable|integer|min:0',
                // 'senior_count' => 'nullable|integer|min:0',
                // 'child_count' => 'nullable|integer|min:0',
                'amount' => 'nullable|integer|min:0',
            ]);
    
            $userSubscription = UserSubscription::where('user_id', $user->id)
                ->where('is_dependent', 0)
                ->first();
                //dd($user);
            $depent_count = UserSubscription::where('referral_id', $user->id)->where('is_dependent',1)->where('is_removed',0)->count();
                
            $totalCount = $request->adult_count + $request->senior_count + $request->child_count + $depent_count;

            if ($totalCount > 10) {
                return response()->json(['error' => 'The total count of adult, senior, and child cannot exceed 10'], 400);
            }
    
            if ($userSubscription) {
                
                // $userSubscription->adult_count = $request->adult_count;
                // $userSubscription->senior_count = $request->senior_count;
                // $userSubscription->child_count = $request->child_count;
                // $userSubscription->slot_count = $totalCount;
                $userSubscription->amount = $request->amount;
    
                // $userSubscription->start_date = now();
                // $userSubscription->end_date = now()->addYear();
    
                $userSubscription->save();
                
                // Zoho CRM Update Logic
                $crmStatus = 'success';
                $crmController = new CRMController();
                $accessToken = $crmController->getZohoAccessToken();
        
                $zohoData = [
                    'data' => [
                        [
                            'amount' => $request->amount,
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
        
                    if ($response->successful()) {
                        $crmResponse = $response->json();
                        if (!empty($crmResponse['data'][0]['code']) && $crmResponse['data'][0]['code'] === 'SUCCESS') {
                            $crmStatus = 'success';
                           // return response()->json(['message' => 'Subscription and CRM updated successfully'], 200);
                        } else {
                            $crmStatus = 'failed';
                           // return response()->json(['error' => 'Failed to update Zoho CRM record'], 400);
                        }
                    } else {
                         $crmStatus = 'failed';
                        //return response()->json(['error' => 'Failed to connect to Zoho CRM'], 500);
                    }
                } else {
                     $crmStatus = 'failed';
                    //return response()->json(['message' => 'Database updated successfully, but no CRM record found'], 200);
                }
                
                // $depent_user = UserSubscription::where('referral_id', $user->id)->where('is_dependent',1)->where('is_removed',0)->get();
                
                // foreach ($depent_user as $dependent_user) {
                //     $dependent_user->start_date = now();
                //     $dependent_user->end_date = now()->addYear();
                //     $dependent_user->save(); 
                // }
    
                $amount = $request->amount;  
           
                if ($amount > 0) {
                    
                    putenv('SENANGPAY_MERCHANT_ID=310172620120861');
                    putenv('SENANGPAY_SECRET_KEY=7053-332');
                    
                    $merchant_id = getenv('SENANGPAY_MERCHANT_ID');
                    $secret_key = getenv('SENANGPAY_SECRET_KEY');
                    
                    $reg_details = Registration::findOrFail($user->reg_id);
        
                    //$order_id = $userSubscription->id; 
                    $name = $user->name;
                    $email = $user->email;
                    $phone = $reg_details->phone_number;
                    
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
                    $payment->payment_method = 'Renew';
                    $payment->payment_date = now();
                    $payment->save();
                    
                    $order_id = $userSubscription->id . 'FH' . $random_number;
                    
                    $hash = hash_hmac('sha256', $secret_key . $detail . $amount . $order_id, $secret_key);
        
                    //$hash = sha1($secret_key . $detail . $amount . $order_id);
                    //dd($merchant_id);
    
                   $senangPayUrl = "https://app.senangpay.my/payment/{$merchant_id}";
        
                    $data = [
                        'detail' => 'Subscription payment for Order ' . $order_id,
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
                    ]);
                }
    
                return response()->json([
                    'status' => 'success',
                    'message' => 'Subscription slot renewed successfully',
                    'data' => $userSubscription,
                    'crmStatus' => $crmStatus
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid subscription found for this user'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while renewing the subscription slot',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function renewSlotUpdate(Request $request)
    {
        try {
            
            $request->validate([
                //'user_id' => 'required',
                'adult_count' => 'required',
                'senior_count' => 'required',
                'child_count' => 'required',
            ]);
            
            $userId = $request->user()->id;

            $userSubscription = UserSubscription::where('user_id', $userId)->where('is_dependent', 0)->first();
            
            $totalCount = $request->adult_count + $request->senior_count + $request->child_count + $userSubscription->slot_count;

            if ($totalCount > 10) {
                return response()->json(['error' => 'The total count of adult, senior, and child cannot exceed 10'], 400);
            }

            if (!$userSubscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid subscription found for the user.'
                ], 404);
            }
            
                $planend = UserSubscription::where('user_id', $userId)->where('is_dependent', 0)->where('end_date', '<', Carbon::now())->first();
                
                $depent_user = UserSubscription::where('referral_id', $userId)->where('is_dependent',1)->where('is_removed',0)->get();
                
                //dd($planend);
                if($planend)
                {
                    $userSubscription->adult_count = $request->adult_count;
                    $userSubscription->senior_count = $request->senior_count;
                    $userSubscription->child_count = $request->child_count;
                    $userSubscription->slot_count = $totalCount;
                    $userSubscription->plan_times += 1;
                    $userSubscription->start_date = now();
                    $userSubscription->is_renewed = true;
                    $userSubscription->end_date = now()->addYear();
        
                    $userSubscription->save();
                    
                    foreach ($depent_user as $dependent_user) {
                        $dependent_user->start_date = now();
                        $dependent_user->end_date = now()->addYear();
                        $dependent_user->is_renewed = true;
                        $dependent_user->save(); 
                    }
                    
                    $crmController = new CRMController();
                    $accessToken = $crmController->getZohoAccessToken();
            
                    $zohoUpdateData = [
                        'data' => [
                            [
                                'adult_count' => $userSubscription->adult_count,
                                'senior_count' => $userSubscription->senior_count,
                                'child_count' => $userSubscription->child_count,
                                'slot_count' => $userSubscription->slot_count,
                                'plan_times' => $userSubscription->plan_times,
                                'start_date' => $userSubscription->start_date->format('Y-m-d'),
                                'end_date' => $userSubscription->end_date->format('Y-m-d'),
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
            
                    // Update dependents in Zoho CRM
                    foreach ($dependents as $dependent) {
                        $zohoDependentUpdateData = [
                            'data' => [
                                [
                                    'start_date' => $dependent->start_date->format('Y-m-d'),
                                    'end_date' => $dependent->end_date->format('Y-m-d'),
                                ],
                            ],
                        ];
            
                        $zohoDependentUrl = "https://www.zohoapis.com/crm/v2/User_Subscriptions/{$dependent->zoho_record_id}";
            
                        $dependentResponse = Http::withHeaders([
                            'Authorization' => "Zoho-oauthtoken $accessToken",
                            'Content-Type' => 'application/json',
                        ])->put($zohoDependentUrl, $zohoDependentUpdateData);
            
                        if (!$dependentResponse->successful()) {
                            Log::error('Failed to update dependent subscription in Zoho CRM', [
                                'dependent_id' => $dependent->id,
                                'response' => $dependentResponse->json(),
                            ]);
                        }
                    }
                }else
                {
                    $userSubscription->plan_times += 1;
                    $userSubscription->is_renewed = true;
                    $userSubscription->save();
                    
                    foreach ($depent_user as $dependent_user) {
                        $dependent_user->is_renewed = true;
                        $dependent_user->plan_times += 1;
                        $dependent_user->save(); 
                    }
                    
                    $userId = $user = Auth::user()->id;
                    if (!$userId) {
                        return response()->json(['error' => 'User ID is null'], 500);
                    }

                    \DB::table('renewal_records')->insert([
                        'user_id' => $userId,
                        'renewal_date' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
        
                    $dependents = UserSubscription::where('referral_id', $userId)
                        ->where('is_dependent', 1)
                        ->where('is_removed', 0)
                        ->get();
                        
                    foreach ($dependents as $dependent) {
                        if($dependent->user_id)
                        {
                            \DB::table('renewal_records')->insert([
                            'user_id' => $dependent->user_id,
                            'renewal_date' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        }
                         
                    }
                }
           
                
            return response()->json(['message' => 'Purchase slots updated successfully'], 200);

            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }


    public function readUser(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required',
            ]);
            $userId = $request->user()->id;
            
            //dd($userId);
            
            $userSubscription = UserSubscription::where('user_id', $request->user_id)->where('referral_id',$userId)->where('is_dependent', 1)->first();
            
            if (!$userSubscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid subscription found for the user.'
                ], 404);
            }
            
            if($userSubscription)
            {
                $userSubscription->is_read = 1;
        
                $userSubscription->save();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'User Read successfully'
                ], 200);
            }
            
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while downgrading the subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }
            
    
    public function memberList(Request $request)
    {
        $members = Member::all()->makeHidden(['created_at', 'updated_at']);

        return response()->json($members);
    }
    
    public function insertMember(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'type' => 'required|max:255',
            'title' => 'required|string|max:255',
            'range_limit' => 'nullable',
            'price' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'count' => 'nullable|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
    
        // Insert the new member record
        try {
            $member = new Member();
            $member->type = $request->type;
            $member->title = $request->title;
            $member->range_limit = $request->range_limit ?? 0;
            $member->price = $request->price;
            $member->discount = $request->discount ?? 0;
            $member->count = $request->count ?? 0;
            $member->save();
    
            return response()->json(['message' => 'Member inserted successfully', 'data' => $member], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to insert member', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function editMember(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'type' => 'required|max:255',
            'title' => 'required|string|max:255',
            'range_limit' => 'nullable',
            'price' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'count' => 'nullable|integer',
            'id' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
    
        try {
            // Find the member by ID
            $member = Member::findOrFail($request->id);
    
            // Update the member data
            $member->type = $request->type;
            $member->title = $request->title;
            $member->range_limit = $request->range_limit ?? 0;
            $member->price = $request->price;
            $member->discount = $request->discount ?? 0;
            $member->count = $request->count ?? 0;
            $member->save();
    
            return response()->json(['message' => 'Member updated successfully', 'data' => $member], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update member', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function deleteMember(Request $request)
    {
        try {
            $id = $request->query('id'); 
            
            $member = Member::findOrFail($id);
    
            $member->delete();
    
            return response()->json(['message' => 'Member deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Member not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete member', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function Benefitstore(Request $request)
    {
        // Validate request
        $validatedData = $request->validate([
            'benefit_description' => 'required|string|max:255',
        ]);
    
        // Create a new benefit
        $benefit = BenefitMaster::create([
            'benefit_description' => $validatedData['benefit_description'],
        ]);
    
        return response()->json(['message' => 'Benefit added successfully', 'data' => $benefit], 201);
    }
    
    public function Benefitedit(Request $request)
    {
        // Validate request
        $validatedData = $request->validate([
            'benefit_description' => 'required|string|max:255',
            'id' => 'required',
        ]);
        
        $id = $validatedData['id'];
    
        // Find and update the benefit
        $benefit = BenefitMaster::find($id);
    
        if (!$benefit) {
            return response()->json(['message' => 'Benefit not found'], 404);
        }
    
        $benefit->update([
            'benefit_description' => $validatedData['benefit_description'],
        ]);
    
        return response()->json(['message' => 'Benefit updated successfully', 'data' => $benefit]);
    }
    
    public function Benefit_delete(Request $request)
    {
        $id = $request->query('id'); 
        
        $benefit = BenefitMaster::find($id);
    
        // Check if the benefit exists
        if (!$benefit) {
            return response()->json(['message' => 'Benefit not found'], 404);
        }
    
        // Delete the record
        $benefit->delete();
    
        // Return success response
        return response()->json(['message' => 'Benefit deleted successfully']);
    }



    
    
    public function getUserSubscriptionCRM(Request $request)
    {
        try {
            
            $request->validate([
                'user_id' => 'required',
            ]);
            
            $user = User::where('id', $request->user_id)->first();
            
            $userSubscription = UserSubscription::with(['subscriptionMaster.benefits'])
                                                 ->where('user_id', $user->id)
                                                 ->first();
            
            if (!$userSubscription) {
                return response()->json(['error' => 'No subscription found with the provided referral number'], 404);
            }
            
            $fieldsToConvert = ['free_plan', 'is_dependent', 'is_plan_expired', 'is_removed','is_qualifying_period','is_manual', 'is_read']; 
            foreach ($fieldsToConvert as $field) {
                $userSubscription->$field = (bool) $userSubscription->$field;
            }
            
            $createdDate = Carbon::parse($userSubscription->start_date)->startOfDay();
            $currentDate = now();
            $daysPassed = $createdDate->diffInDays($currentDate);
            $qualifyingPeriod = 14;
            
            // remaining days count
            $endDate = Carbon::parse($userSubscription->end_date);
            $currentDate = now();
            $remainingDays = $currentDate->diffInDays($endDate, false); 
            
            if ($remainingDays < 0) {
                $remainingDays = 0; 
            }
            
            $userSubscription->remainingDays = $remainingDays;

            
            $isWithinQualifyingPeriod = $daysPassed <= $qualifyingPeriod;
            $daysLeftInQualifyingPeriod = max(0, $qualifyingPeriod - $daysPassed);
            
            if($userSubscription->plan_times > 1)
            {
                $userSubscription->is_qualifying_period = false;
            }else
            {
                $userSubscription->is_qualifying_period = $isWithinQualifyingPeriod;

            }
            
            $userSubscription->days_left_in_qualifying_period = $daysLeftInQualifyingPeriod;
            $userSubscription->slot_count = $userSubscription->slot_count;
            
            $endDate = $userSubscription->end_date;
            if ($endDate && $currentDate->greaterThan($endDate)) {
                $userSubscription->is_plan_expired = true;
            } else {
                $userSubscription->is_plan_expired = false;
            }

            
            $userSubscription->name = $user->name;
            $userSubscription->email = $user->email;
            
            $reg_details = Registration::where('id',$user->reg_id)->first();
            $userSubscription->dob = $reg_details->dob;
            $userSubscription->remain_slot = $userSubscription->adult_count + $userSubscription->senior_count + $userSubscription->child_count;
            //dd($userSubscription->is_dependent);
            
            $activity = Activity::where('user_id',$user->id)->count();
            $userSubscription->principal_user_activity = $activity;
            
            $principl_user = [];
           
           if($userSubscription->is_dependent == true)
           {
               $principl_user = UserSubscription::with(['subscriptionMaster.benefits'])
                ->where('user_id', $userSubscription->referral_id)->where('is_removed', 0)->first();
                
                foreach ($fieldsToConvert as $field) {
                    $principl_user->$field = (bool) $principl_user->$field;
                }
                
                //dd($principl_user);
                $princUserModel = User::where('id', $userSubscription->referral_id)->first();
                
                $reg_details = Registration::where('id',$princUserModel->reg_id)->first();
                     
                $principl_user->name = $princUserModel->name;
                $principl_user->email = $princUserModel->email;
                $principl_user->dob = $reg_details->dob;
           }
           
           
            
            $dependentUsers = UserSubscription::with(['subscriptionMaster.benefits'])
                ->where('referral_id', $user->id)->where('is_removed', 0)->get()
                ->map(function ($dependentUser) use ($fieldsToConvert) {
                    foreach ($fieldsToConvert as $field) {
                        $dependentUser->$field = (bool) $dependentUser->$field;
                    }
                    
                    $dependent_activity = 0;
                    
                    if($dependentUser->user_id != NULL)
                    {
                        $dependentUserModel = User::where('id', $dependentUser->user_id)->first();
                        $registration_details = Registration::where('id',$dependentUserModel->reg_id)->first();
                        $dependent_activity = Activity::where('user_id',$dependentUser->user_id)->count();
                     
                        $dependentUser->name = $dependentUserModel->name;
                        $dependentUser->email = $dependentUserModel->email;
                    }else
                    {
                        $registration_details = Registration::where('id',$dependentUser->reg_id)->first();
                        $dependentUser->name = $registration_details->first_name.' '.$registration_details->last_name;
                        $dependentUser->email = $registration_details->email;
                    }
                     
                     $dependentUser->dependent_user_activity = $dependent_activity;
                     $dependentUser->dob = $registration_details->dob;
                     $dependentUser->ic_number = $registration_details->ic_number;
                     $dependentUser->phone_number = $registration_details->phone_number;
                     $dependentUser->race = $registration_details->race;
                     $dependentUser->gender = $registration_details->gender == 0 ? 'male' : 'female';
                     $dependentUser->nationality = $registration_details->nationality;
                     $dependentUser->address = $registration_details->address;
                     $dependentUser->address2 = $registration_details->address2;
                     $dependentUser->postcode = $registration_details->postcode;
                     $dependentUser->city = $registration_details->city;
                     $dependentUser->state = $registration_details->state;
                     $dependentUser->country = $registration_details->country;
                     $dependentUser->medical_info = (bool) $registration_details->medical_info;
                     $dependentUser->heart_problems = (bool) $registration_details->heart_problems;
                     $dependentUser->diabetes = (bool) $registration_details->diabetes;
                     $dependentUser->allergic = (bool) $registration_details->allergic;
                     $dependentUser->allergic_medication_list = $registration_details->allergic_medication_list;
                     $dependentUser->are_u_foreigner = (bool) $registration_details->are_u_foreigner;
                     $dependentUser->passport_no = $registration_details->passport_no;
                     
                    
                    // dd($dependentUser);
                    
                    $createdDate = $dependentUser->created_at;
                    $currentDate = now();
                    $isWithinQualifyingPeriod = $createdDate->diffInDays($currentDate) <= 14;
                    $dependentUser->is_qualifying_period = $isWithinQualifyingPeriod;
    
                    return $dependentUser;
                });
                
            
            return response()->json([
                'user_subscription' => $userSubscription,
                'dependent_users' => $dependentUsers,
                'principl_user' => $principl_user,
                
            ], 200);

    
        } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            // Log the error for debugging
           // return $e->getMessage();
            Log::error('Error in getUserSubscriptionById: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }
    
     
}
