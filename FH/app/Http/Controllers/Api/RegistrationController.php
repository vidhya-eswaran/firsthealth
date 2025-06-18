<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Payment;
use App\Models\InviteUser;
use App\Models\SubscriptionMaster;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Mime\Email;
use App\Http\Controllers\Api\CRMController;
use Carbon\Carbon;


class RegistrationController extends Controller
{
  public function step1(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'ic_number' => $request->input('are_u_foreigner') ? 'nullable' : 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'email' => 'required|email',
                'dob' => 'required|date',
                'race' => 'required',
                'gender' => 'required',
                'nationality' => 'required',
                'are_u_foreigner' => 'required|boolean', 
                'passport_no' => $request->input('are_u_foreigner') ? 'required_if:are_u_foreigner,1|string|max:255' : 'nullable',
 
            ]);
    
            $otp = rand(100000, 999999);
    
            $registration = Registration::where('email', $request->input('email'))->first();
    
            if ($registration) {
                $registration->update([
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'ic_number' => $request->input('ic_number'),
                    'phone_number' => $request->input('phone_number'),
                    'dob' => $request->input('dob'),
                    'race' => $request->input('race'),
                    'gender' => $request->input('gender'),
                    'nationality' => $request->input('nationality'),
                    'otp' => $otp,
                    'are_u_foreigner' => $request->input('are_u_foreigner'),
                    'passport_no' => $request->input('passport_no'),
                ]);
            } else {
                $registration = Registration::create(array_merge(
                    $request->only('first_name','last_name', 'ic_number', 'phone_number', 'email', 'dob','race','gender','nationality', 'are_u_foreigner', 'passport_no'),
                    ['otp' => $otp]
                ));
            }
    
            // // Send the OTP via email
            // Mail::raw("Your OTP code is: $otp", function ($message) use ($request) {
            //     $message->to($request->email)
            //             ->subject('Your OTP Code');
            // });
    
            return response()->json(['id' => $registration->id, 'message' => 'Step 1 completed!'], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            \Log::error('Failed to send OTP: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process your request'], 500);
        }
    }
    public function Emailverify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
    
        $emailExists = \DB::table('registrations')->where('email', $request->email)->exists();
    
        /*if ($emailExists) {
            return response()->json(['message' => 'Email already exists'], 400);
        }*/
        
        $users = User::where('email' , $request->email)->where('is_active', 0)->first();
       // dd($users);
        if ($users) {
            return response()->json(['message' => 'This email is already in use. Please use a different email'], 400);
        }
        $user = User::where('email' , $request->email)->first();
        
        if($user)
        {
           $usersubscription = UserSubscription::where('user_id', $user->id)->exists();
            
            if($usersubscription)
            {
                return response()->json(['message' => 'Email already exists'], 400);
            } 
        }
        
        $otp = rand(100000, 999999);
    
        Cache::put('otp_' . $request->email, $otp, now()->addMinutes(1));

        // Send the OTP email using the proper format
        Mail::send([], [], function ($message) use ($request, $otp) {
            $message->to($request->email)
                ->subject('Your OTP Code')
                ->html("<p>Your OTP code is: <strong>$otp</strong></p>");
        });
    
        return response()->json(['duration' => 1, 'status' => true, 'message' => 'OTP sent to your email'], 200);
    }
    
    public function Otpverify(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|numeric|digits:6',
            ]);
        
            // Retrieve the OTP from the cache
            $cachedOtp = Cache::get('otp_' . $request->email);
        
            if (!$cachedOtp) {
                return response()->json([
                        'status' => false,
                        'message' => 'OTP has expired'
                ], 422);
            }
        
            if ($cachedOtp != $request->otp) {
                return response()->json([
                        'status' => false,
                        'message' => 'Invalid OTP'
                ], 422);
            }
        
            // OTP is valid
            return response()->json([
                'data' => [
                    'status' => true,
                    'message' => 'OTP verified successfully'
                    ]
            ], 200);
        
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Invalid OTP', 'status' => false], 422);

        }
    }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:registrations,id',
            'otp' => 'required|numeric',
        ]);
    
        try {
            
            $registration = Registration::where('id', $request->input('user_id'))->first();
            if (!$registration) {
                return response()->json(['error' => 'User not found'], 404);
            }
    
            if ($registration->otp == $request->input('otp')) {
                
                $registration->update(['otp' => null]);
    
                return response()->json(['message' => 'OTP verification successful!'], 200);
            } else {
                return response()->json(['error' => 'Invalid OTP'], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to verify OTP: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to verify OTP'], 500);
        }
    }

    public function step2(Request $request)
    {
        //dd($id);
        try { 
            $request->validate([
                'address' => 'required|string',
                // 'address2' => 'nullable|string|max:255',
                // 'postcode' => 'required|string|max:10',
                // 'city' => 'required|string|max:255',
                // 'state' => 'required|string|max:255',
                // 'country' => 'required|string|max:255',
                'is_covered' => 'required',
                'longitude' => 'required',
                'latitude' => 'required',
            ]);

            //dd($request);
            $registration = Registration::findOrFail($request->id);
            $registration->update($request->only('address', 'is_covered', 'longitude', 'latitude'));

            return response()->json(['id' => $registration->id,'email' => $registration->email,  'message' => 'Step 2 completed!']);
        }catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        }   
    }

    
    public function step3(Request $request)
    {
        try {  
            $request->validate([
                'password' => 'required|string|min:8',
                'email' => 'required',
            ]);

            $registration = Registration::findOrFail($request->id);
            $registration->password = Hash::make($request->password);
            $registration->email = $request->email;
            $registration->save();
            
            // Check if user already exists
            $user = User::where('email', $registration->email)->first();
            
            if (!$user) {
            // Insert into the user table
                $user = new User();
                $user->reg_id = $registration->id;
                $user->name = $registration->first_name.' '.$registration->last_name;
                $user->email = $registration->email;
                $user->password = $registration->password; // Hashed password
                $user->save();
            } else {
                // Optionally, update the existing user
                $user->name = $registration->first_name.' '.$registration->last_name;
                $user->reg_id = $registration->id;
                $user->password = $registration->password; // Update the password if needed
                $user->save();
            }
            
            $crmController = new CRMController();
            $accessToken = $crmController->getZohoAccessToken();
            
            $zohoData = [
                'data' => [
                    [
                        'u_id' => $user->id,
                        'reg_id' => $registration->id,
                        'Name' => $user->name,
                        'Email' => $user->email,
                    ]
                ],
                'duplicate_check_fields' => ['Email']
            ];
    
            $module = 'FHUser';  
            
          // dd($accessToken);
    
            $response = Http::withHeaders([
                'Authorization' => "Zoho-oauthtoken $accessToken",
                'Content-Type' => 'application/json',
            ])->post("https://www.zohoapis.com/crm/v2/$module/upsert", $zohoData);


            return response()->json(['id' => $registration->id, 'message' => 'Step 3 completed!']);
        }catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        }
    }

    public function step4(Request $request)
    {
        try {

            $request->validate([
                'heart_problems' => 'required|boolean',
                'diabetes' => 'required|boolean',
                'allergic' => 'required|boolean',
                'allergic_medication_list' => $request->input('allergic') == 1 
                    ? 'required|string|max:255' 
                    : 'nullable|string|max:255',
            ]);

            // Find the registration entry and update it
            $registration = Registration::findOrFail($request->id);
            $registration->heart_problems = $request->heart_problems;
            $registration->diabetes = $request->diabetes;
            $registration->allergic = $request->allergic;
            $registration->allergic_medication_list = $request->allergic_medication_list;
            $registration->save();
            
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
                            'dob' => Carbon::parse($registration->dob)->format('Y-m-d'),
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
                            'remindme' => $registration->remindme,
                        ],
                    ],
                ];
            
                    $module = 'User_Registrations';  
                    
                  //dd($accessToken);
            
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

            return response()->json(['id' => $registration->id, 'message' => 'Step 4 completed!', 'crm_status' => $crmStatus,]);
        }catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        }
    }



    public function step5(Request $request)
    {
        try {
            $request->validate([
                'referral_number' => 'nullable|string|max:255',
                'id' => 'required|integer|exists:registrations,id',
            ]);
    
            $registration = Registration::findOrFail($request->id);
    
            $existingSubscription = UserSubscription::where('referral_no', $request->referral_number)
                ->first(['adult_count', 'senior_count', 'child_count', 'created_at','subscription_id','user_id']); 
                
            if(!$existingSubscription){
                return response()->json(['status' => 'error', 'is_valid' => false, 'message' => 'Sorry, this referral number is incorrect.'], 201);
            }
                
            $subscription_data = SubscriptionMaster::where('id',$existingSubscription->subscription_id)->first();
            
            $is_invited = false;
            
            if ($registration->email) {
                $inviteUser = InviteUser::where('to_mail', $registration->email)
                    ->where('user_id', $existingSubscription->user_id) 
                    ->first();
                    
                if($inviteUser)
                {
                    $is_invited = true;
                }
                    //dd($existingSubscription->user_id);
    
                if ($inviteUser && $inviteUser->is_revoke == 1) {
                    return response()->json(['message' => 'This referral code is revoked. Please contact the person.'], 201);
                }
            }
    
            $eligiblePlans = collect(); 
    
            $is_valid = false; 
    
            $age_group = 'unknown'; 
    
            if ($existingSubscription) {
                $is_valid = true; 
    
                // $dob = $registration->dob;
                // $age = \Carbon\Carbon::parse($dob)->age;
    
                // if ($age >= 18 && $age < 59) {
                //     $age_group = 1;
                // } elseif ($age >= 59) {
                //     $age_group = 2;
                // } else {
                //     $age_group = 0;
                // }
                
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

    
                $plans = SubscriptionMaster::with('benefits')->get();
                
               // dd($plans);
    
                foreach ($plans as $plan) {
                    if ($plan->id == 3) {
                        continue; 
                    }
    
                    if ($plan->id == 1) { 
                        if ($age_group == 1 && $existingSubscription->adult_count > 0) {
                            $eligiblePlans->push([
                                'id' => $plan->id, 
                                'name' => $plan->plan
                            ]); 
                        }
                    } elseif ($plan->id == 2) { 
                        if ($age_group == 2 && $existingSubscription->senior_count > 0) {
                            $eligiblePlans->push([
                                'id' => $plan->id, 
                                'name' => $plan->plan
                            ]); 
                        }
                    } else { 
                        if ($age_group == 0 && $existingSubscription->child_count > 0) {
                            $eligiblePlans->push([
                                'id' => $plan->id, 
                                'name' => $plan->plan
                            ]); 
                        }
                    }
                }
                
                //dd($eligiblePlans);
    
                if ($existingSubscription->created_at) {
                    $createdDate = $existingSubscription->created_at;
                    $currentDate = now();
                    $isWithinQualifyingPeriod = $createdDate->diffInDays($currentDate) <= 14;
                    $existingSubscription->is_qualifying_period = $isWithinQualifyingPeriod;
                } else {
                    $existingSubscription->is_qualifying_period = false; 
                }
            } else {
                $is_valid = false; 
            }
    
            $registration->referral_number = $request->referral_number;
            $registration->save();
    
            return response()->json([
                'id' => $registration->id,
                'subscription_plan' => $subscription_data->plan,
                'slots_remain' => $existingSubscription,
                'eligible_plans' => $eligiblePlans->unique('id')->values(), 
                'is_valid' => $is_valid, 
                'age_group' => $age_group ,
                'is_invited' => $is_invited
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }


    
    public function step6(Request $request)
    {
        //dd("DDd");
        try {
            $request->validate([
                'user_id' => 'required', 
                'subscription_id' => 'required|exists:subscription_masters,id', 
                'amount' => 'required',
            ]);
            
            $userid = User::where('reg_id',$request->user_id)->first();
            
            $userSubscription = UserSubscription::where('user_id', $userid->id)->first();
            
            $totalCount = $request->adult_count + $request->senior_count + $request->child_count;

            // Validate if the total count exceeds 10
            if ($totalCount > 10) {
                return response()->json(['error' => 'The total count of adult, senior, and child cannot exceed 10'], 400);
            }
        
            if($request->subscription_id != 3)
                {
                    $referralNo = 'FH' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);  
                }else
                {
                    $referralNo = NULL;
                }
                
            $startDate = Carbon::now();  
            $endDate = Carbon::now()->addYear(); 
                
            if (!$userSubscription) {
                $userSubscription = new UserSubscription();
                $userSubscription->user_id = $userid->id;
                
            }
            $userSubscription->referral_no = $referralNo;
            $userSubscription->subscription_id = $request->subscription_id;
            if($request->subscription_id == 3)
            {
                $userSubscription->free_plan = 1;
                $userSubscription->child_count = 0;
                $userSubscription->senior_count = 0;
                $userSubscription->adult_count = 0;
                $userSubscription->is_qualifying_period = 1;
                $userSubscription->is_dependent = 0;
                $userSubscription->referral_id = NULL;
                $userSubscription->amount = 0;
            }else
            {
                $userSubscription->free_plan = 0;
                // $userSubscription->child_count = $request->child_count;
                // $userSubscription->senior_count = $request->senior_count;
                // $userSubscription->adult_count = $request->adult_count;
                // $userSubscription->slot_count = $totalCount;
                
                $userSubscription->is_qualifying_period = 1;
                $userSubscription->is_dependent = 0;
                $userSubscription->referral_id = NULL;
                $userSubscription->amount = $request->amount;
            }
            $userSubscription->start_date = $startDate;  
            $userSubscription->end_date = $endDate; 
    
            $userSubscription->save();
            
            $crmStatus = 'success'; 
            $recordId = null;
            
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
                
               // dd($startDate);
            
                $zohoData = [
                    'data' => [
                        [
                            'Name' => $userid->name,
                            'u_id' => $userid->id,
                            'reg_id' => $request->user_id,
                            'subscription_id' => $userSubscription->subscription_id,
                            'referral_no' => $userSubscription->referral_no,
                            'start_date' => $userSubscription->start_date ? Carbon::parse($userSubscription->start_date)->format('Y-m-d') : null,
                            'end_date' => $userSubscription->end_date ? Carbon::parse($userSubscription->end_date)->format('Y-m-d') : null,
                            'amount' => $userSubscription->amount,
                            'adult_count' => $request->adult_count ?? 0,
                            'senior_count' => $request->senior_count ?? 0,
                            'child_count' => $request->child_count ?? 0,
                            'free_plan' => $request->free_plan ?? 0,
                            'is_qualifying_period' => $request->is_qualifying_period ?? 1,
                            'is_dependent' => $request->is_dependent ?? 0,
                            'referral_id' => $request->referral_id ?? null,
                            'Membership' => $membership,
                        ],
                    ],
                ];
                
                 
            
                $module = 'User_Subscriptions';
                $crmUrl = "https://www.zohoapis.com/crm/v2/$module";
                
               // dd($zohoData);
            
                if ($userSubscription->zoho_record_id) {
                    // Update existing record
                    $recordId = $userSubscription->zoho_record_id;
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->put("$crmUrl/$recordId", $zohoData);
                } else {
                    // Create new record
                    $response = Http::withHeaders([
                        'Authorization' => "Zoho-oauthtoken $accessToken",
                        'Content-Type' => 'application/json',
                    ])->post($crmUrl, $zohoData);
                }
                
                //dd($response->json());
            
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

                
            // dd($response);
    
            //return response()->json(['message' => 'Subscription successfully added'], 201);
            
            $amount = $request->amount;  
            // if ($request->subscription_id == 1) {
            //     $amount = 100.00; 
            // } else if ($request->subscription_id == 2) {
            //     $amount = 150.00; 
            // }
    
            if ($amount > 0) {
                
                putenv('SENANGPAY_MERCHANT_ID=310172620120861');
                putenv('SENANGPAY_SECRET_KEY=7053-332');
                
                $merchant_id = getenv('SENANGPAY_MERCHANT_ID');
                $secret_key = getenv('SENANGPAY_SECRET_KEY');
                
                $reg_details = Registration::findOrFail($request->user_id);
    
                $name = $userid->name;
                $email = $userid->email;
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
                $payment->payment_method = 'Subscription';
                $payment->payment_date = now();
                $payment->save();
                
                $order_id = $userSubscription->id . 'FH' . $random_number;
                
                $hash = hash_hmac('sha256', $secret_key . $detail . $amount . $order_id, $secret_key);
    
                //$hash = md5($secret_key . $detail . $amount . $order_id);
                //$hash = md5($merchant_id.$secret_key.$order_id);
                //dd($merchant_id);

               $senangPayUrl = "https://app.senangpay.my/payment/{$merchant_id}";
    
                $data = [
                    'detail' => 'Subscription_payment_' .$order_id,
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
    
            return response()->json(['message' => 'Free plan activated, no payment required'], 201);


    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }
    
    public function slotUpdate(Request $request)
    {
        try {
           
            
            $request->validate([
                'user_id' => 'required',
                'child_count' => 'required',
                'senior_count' => 'required',
                'adult_count' => 'required',
            ]);
            
            $userid = User::where('reg_id',$request->user_id)->first();
            
            //dd($userid);
            
            if (!$userid) {
                return response()->json(['error' => 'User not found'], 404);
            }
            
            
            $userSubscription = UserSubscription::where('user_id', $userid->id)->first();
            
            if (!$userSubscription) {
                return response()->json(['error' => 'User subscription not found'], 404);
            }
            
            $totalCount = $request->adult_count + $request->senior_count + $request->child_count;

            // Validate if the total count exceeds 10
            if ($totalCount > 10) {
                return response()->json(['error' => 'The total count of adult, senior, and child cannot exceed 10'], 400);
            }
            
            $startDate = Carbon::now();  
            $endDate = Carbon::now()->addYear(); 
                
                $userSubscription->child_count = $request->child_count;
                $userSubscription->senior_count = $request->senior_count;
                $userSubscription->adult_count = $request->adult_count;
                $userSubscription->slot_count = $totalCount;
                $userSubscription->start_date = $startDate;  
                $userSubscription->end_date = $endDate; 
        
                $userSubscription->save();
                
                // Zoho CRM Update Logic
            $crmController = new CRMController();
            $accessToken = $crmController->getZohoAccessToken();
    
            $zohoData = [
                'data' => [
                    [
                        'child_count' => $request->child_count,
                        'senior_count' => $request->senior_count,
                        'adult_count' => $request->adult_count,
                        'slot_count' => $totalCount,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
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
                        return response()->json(['message' => 'Subscription and CRM updated successfully'], 200);
                    } else {
                        return response()->json(['error' => 'Failed to update Zoho CRM record'], 400);
                    }
                } else {
                    return response()->json(['error' => 'Failed to connect to Zoho CRM'], 500);
                }
            } else {
                return response()->json(['message' => 'Database updated successfully, but no CRM record found'], 200);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }
    
    public function getUserSubscriptionByReferralNo(Request $request)
    {
        try {
            $request->validate([
                'referral_no' => 'required',
            ]);
    

            $userSubscription = UserSubscription::with(['subscriptionMaster.benefits', 'user'])
                                                 ->where('referral_no', $request->referral_no)
                                                 ->first();
            
            if (!$userSubscription) {
                return response()->json(['error' => 'No subscription found with the provided referral number'], 200);
            }
            
            $createdDate = $userSubscription->created_at;
            $currentDate = now();
            $daysSinceCreation = $createdDate->diffInDays($currentDate);
            $isWithinQualifyingPeriod = $daysSinceCreation <= 14;
    
            $daysLeftInQualifyingPeriod = max(0, 14 - $daysSinceCreation);
    
            $userSubscription->is_qualifying_period = $isWithinQualifyingPeriod;
            $userSubscription->days_left_in_qualifying_period = $daysLeftInQualifyingPeriod;
            
            return response()->json(['user_subscription' => $userSubscription], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }


    
   
    public function remindMe(Request $request)
    {
        try {

            $request->validate([
                'remindme' => 'required',
            ]);

            // Find the registration entry and update it
            $registration = Registration::findOrFail($request->id);
            $registration->remindMe = $request->remindme;
            $registration->save();
            
            if ($request->remindme == 1) {
            // Send the email
            Mail::raw("Thank you for signing up for notifications. We will notify you at {$registration->email} once your area is supported.", function ($message) use ($registration) {
                    $message->to($registration->email)
                            ->subject('Your First Health Area is not Covered!');
                });
            }

            return response()->json(['id' => $registration->id,'email' => $registration->email, 'message' => 'Thank you!']);
        }catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 422);
        } 
    }
   
}
