<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Http;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Activity;
use App\Models\Registration;
use App\Models\UserSubscription;
use App\Models\SubscriptionMaster;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use App\Http\Controllers\Api\CRMController;

use DB;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        try {
            $user = Auth::user();
            
            //dd($user);

            if (!$user) {
                return response()->json([
                    'error' => 'User not authenticated.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $user->load('registration');
            
            $userSubscription = UserSubscription::with('subscriptionMaster')->where('user_id', $user->id)->first();
            
            $principl_user = [];

            if($userSubscription->is_dependent == 1) {
                $referralID = $userSubscription->referral_id;
                $ref_details = UserSubscription::where('user_id', $referralID)->first();
    
                $ref_user_details = User::where('id', $referralID)->first();
                $reg_details = Registration::where('id', $ref_user_details->reg_id)->first();
    
                $principl_user['name'] = $reg_details->first_name .' '. $reg_details->last_name;
                $principl_user['referral_no'] = $ref_details->referral_no;
                $principl_user['id'] = $referralID;
            } else {
                $principl_user['name'] = "";
                $principl_user['referral_no'] = "";
                $principl_user['id'] = "";
            }
            
          
                $subscriptiondata = SubscriptionMaster::where('id', $userSubscription->subscription_id)->first();
            
                $userSubscription = [
                    'is_accepted' => (bool) $userSubscription->is_accepted,
                    'is_qualifying_period' => (bool) $userSubscription->is_qualifying_period,
                    'referral_no' => $userSubscription->referral_no,
                    'adult_count' =>  $userSubscription->adult_count,
                    'senior_count' =>  $userSubscription->senior_count,
                    'child_count' => $userSubscription->child_count,
                    'start_date' => $userSubscription->start_date,
                    'end_date' => $userSubscription->end_date,
                    'free_plan' => (bool) $userSubscription->free_plan,
                    'is_dependent' => (bool) $userSubscription->is_dependent,
                    
                ];
            
            $registration = $user->registration;
           
            $registrationData = [
                    'heart_problems' => $registration->heart_problems,
                    'diabetes' => $registration->diabetes,
                    'allergic' => $registration->allergic,
                    'allergic_medication_list' => $registration->allergic_medication_list,
                    'phone_number' => $registration->phone_number,
                    'race' => $registration->race,
                    'gender' => $registration->gender,
                    'nationality' => $registration->nationality,
                    'dob' => $registration->dob,
                    'address' => $registration->address,                   
                    'longitude' => $registration->longitude,
                    'latitude' => $registration->latitude,
                    //'city' => $registration->city,
                    'ic_number' => $registration->ic_number,
                   // 'state' => $registration->state,
                    'first_name' => $registration->first_name,
                    'last_name' => $registration->last_name,
                     'are_u_foreigner' => $registration->are_u_foreigner,
                     'passport_no' => $registration->passport_no,
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
                'principl_user' => $principl_user,
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
    
    public function getPersonalInfo()
    {
        $user = Auth::user(); // Get the authenticated user
        
        $registration = Registration::where('email', $user->email)->first();
        
        if ($registration) {
            return response()->json([
                'first_name' => $registration->first_name,
                'last_name' => $registration->last_name,
                'ic_number' => $registration->ic_number,
                'phone_number' => $registration->phone_number,
                'dob' => $registration->dob,
                'race' => $registration->race,
                'gender' => $registration->gender,
                'nationality' => $registration->nationality,
            ]);
        } else {
            return response()->json(['message' => 'Personal information not found.'], 404);
        }
    }


    public function updatePersonalInfo(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'ic_number' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'dob' => 'required|date',
            'race' => 'required',
            'gender' => 'required',
            'nationality' => 'required',
        ]);

        $registration = Registration::where('email', $user->email)->first();
        if ($registration) {
            $registration->update($request->only([
                'first_name',
                'last_name',
                'ic_number',
                'phone_number',
                'dob',
                'race',
                'gender',
                'nationality'
            ]));
            return response()->json(['message' => 'Profile updated successfully.'], 200);
        } else {
            return response()->json(['message' => 'User Not Found', 401]);
        }
    }

   public function getAddressInfo()
    {
        $user = Auth::user();
        
        $registration = Registration::where('email', $user->email)->first();
        
        if ($registration) {
            return response()->json([
                'address' => $registration->address,
                // 'address2' => $registration->address2,
                // 'city' => $registration->city,
                // 'state' => $registration->state,
                // 'postcode' => $registration->postcode,
                // 'country' => $registration->country,
            ]);
        } else {
            return response()->json(['message' => 'Address information not found.'], 404);
        }
    }

    public function updateAddressInfo(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'address' => 'nullable|string',
            // 'address2' => 'nullable|string|max:255',
            // 'city' => 'nullable|string|max:100',
            // 'state' => 'nullable|string|max:100',
            // 'postcode' => 'nullable|string|max:20',
            // 'country' => 'nullable|string|max:100',
        ]);

        $registration = Registration::where('email', $user->email)->first();
        if ($registration) {
            $registration->update($request->only([
                'address',
                // 'address2',
                // 'city',
                // 'state',
                // 'postcode',
                // 'country',
            ]));
            return response()->json(['message' => 'Address information updated successfully.', 200]);
        } else {
            return response(401)->json(['message' => 'User Not Found', 401]);
        }
    }

    public function getMedicalInfo()
    {
        $user = Auth::user(); 
        
        $registration = Registration::where('email', $user->email)->first();
        
        if ($registration) {
            return response()->json([
                'heart_problems' => $registration->heart_problems,
                'diabetes' => $registration->diabetes,
                'allergic' => $registration->allergic,
                'allergic_medication_list' => $registration->allergic_medication_list,
            ]);
        } else {
            return response()->json(['message' => 'Medical information not found.'], 404);
        }
    }

    public function updateMedicalInfo(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'heart_problems' => 'nullable|boolean',
            'diabetes' => 'nullable|boolean',
            'allergic' => 'nullable|boolean',
            'allergic_medication_list' => 'nullable|string',
        ]);

        $registration = Registration::where('email', $user->email)->first();
        if ($registration) {
            $registration->update($request->only([
                'heart_problems',
                'diabetes',
                'allergic',
                'allergic_medication_list',
            ]));
            return response()->json(['message' => 'Medical information updated successfully.', 200]);
        } else {
            return response()->json(['message' => 'User Not Found', 401]);
        }
    }
    
    // public function profileEditing(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'id' => 'nullable|exists:registrations,id', // Registration ID, if updating
    //             'first_name' => 'required|string|max:255',
    //             'last_name' => 'required|string|max:255',
    //             'ic_number' => $request->input('are_u_foreigner') ? 'nullable' : 'required|string|max:255',
    //             'phone_number' => 'required|string|max:20',
    //             'email' => 'required|email|unique:registrations,email,' . $request->id,
    //             'dob' => 'required|date',
    //             'race' => 'required',
    //             'gender' => 'required',
    //             'nationality' => 'required',
    //             'address' => 'nullable|string',
    //             'is_covered' => 'nullable|boolean',
    //             'heart_problems' => 'nullable|boolean',
    //             'diabetes' => 'nullable|boolean',
    //             'allergic' => 'nullable|boolean',
    //             'allergic_medication_list' => 'nullable',
    //             'are_u_foreigner' => 'required|boolean', 
    //             'passport_no' => $request->input('are_u_foreigner') ? 'required_if:are_u_foreigner,1|string|max:255' : 'nullable',
 
    //         ]);
    
    //         $userId = auth()->id();
    
    //         $registrationData = array_merge(
    //             $request->only('first_name', 'last_name', 'ic_number', 'phone_number', 'email', 'dob', 'race', 'gender', 'nationality')
    //         );
            
    //         //dd($request->allergic_medication_List);
    
    //         if ($request->filled('id')) {
    //             $registration = Registration::findOrFail($request->id);
    //             $registration->update(array_merge(
    //                 $request->only('address', 'is_covered', 'heart_problems', 'diabetes', 'allergic', 'allergic_medication_list','are_u_foreigner','passport_no')
    //             ));
    //             $registration->update($registrationData);
    //         } else {
    //             $registration = Registration::create(array_merge(
    //                 $registrationData,
    //                 $request->only('address', 'is_covered', 'heart_problems', 'diabetes', 'allergic', 'allergic_medication_list','are_u_foreigner','passport_no')
    //             ));
    //         }
            
    //         $user = User::findOrFail($userId);
    //         $user->update([
    //             'name' => $request->first_name.' '.$request->last_name
    //         ]);
    
    //         return response()->json(['id' => $registration->id, 'message' => 'Profile Edited successfully!'], 200);
    
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json($e->errors(), 422);
    //     } catch (\Exception $e) {
    //         \Log::error('Failed to process registration: ' . $e->getMessage());
    //         return response()->json(['error' => 'Failed to process your request'], 500);
    //     }
    // }
    
    public function profileEditing(Request $request)
    {
        try {
            $request->validate([
                'id' => 'nullable|exists:registrations,id', // Registration ID, if updating
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'ic_number' => $request->input('are_u_foreigner') ? 'nullable' : 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                //'email' => 'required|email|unique:registrations,email,' . $request->id,
                //'dob' => 'required|date',
                'race' => 'required',
                'gender' => 'required',
                'nationality' => 'required',
                'address' => 'nullable|string',
                'is_covered' => 'nullable|boolean',
                'heart_problems' => 'nullable|boolean',
                'diabetes' => 'nullable|boolean',
                'allergic' => 'nullable|boolean',
                'allergic_medication_list' => 'nullable',
                'are_u_foreigner' => 'required|boolean',
                'passport_no' => $request->input('are_u_foreigner') ? 'required_if:are_u_foreigner,1|string|max:255' : 'nullable',
            ]);
    
            $userId = auth()->id();
    
            $registrationData = $request->only(
                'first_name', 'last_name', 'ic_number', 'phone_number', 
                'race', 'gender', 'nationality', 
                'address', 'is_covered', 'heart_problems', 'diabetes', 
                'allergic', 'allergic_medication_list', 'are_u_foreigner', 'passport_no'
            );
    
            $crmController = new CRMController();
            $accessToken = $crmController->getZohoAccessToken();
            $module = 'User_Registrations';  
    
            if ($request->filled('id')) {
                // Update record
                $registration = Registration::findOrFail($request->id,);
                $registration->update($registrationData);
    
                // Update record in Zoho CRM
                if ($registration->zoho_record_id) {
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
                               // 'dob' => $registration->dob,
                              //  'email_id' => $registration->email,
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
                                'remindme' => $registration->remindme,
                                'id' => $registration->zoho_record_id,
                            ],
                        ],
                    ];
    
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put("https://www.zohoapis.com/crm/v2/$module", $zohoData);
                    
                    //dd($response->json());
                    
    
                    if ($response->failed()) {
                        throw new \Exception('Failed to update Zoho CRM record');
                    }
                }
            } else {
                // Create new record
                $registration = Registration::create($registrationData);
    
                // Create record in Zoho CRM
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
                           // 'dob' => $registration->dob,
                           // 'email_id' => $registration->email,
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
                            'remindme' => $registration->remindme,
                        ],
                    ],
                ];
    
                $response = Http::withHeaders([
                    'Authorization' => "Zoho-oauthtoken $accessToken",
                    'Content-Type' => 'application/json',
                ])->post("https://www.zohoapis.com/crm/v2/$module", $zohoData);
    
                if ($response->successful()) {
                    $responseData = $response->json();
                    $recordId = $responseData['data'][0]['details']['id'] ?? null;
    
                    if ($recordId) {
                        $registration->update(['zoho_record_id' => $recordId]);
                    }
                } else {
                    throw new \Exception('Failed to create Zoho CRM record');
                }
            }
    
            $user = User::findOrFail($userId);
            $user->update([
                'name' => $request->first_name . ' ' . $request->last_name,
            ]);
    
            return response()->json(['id' => $registration->id, 'message' => 'Profile Edited successfully!'], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            \Log::error('Failed to process registration: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process your request'], 500);
        }
    }


    
    
}
