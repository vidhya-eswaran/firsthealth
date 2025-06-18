<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityMaster;
use App\Models\User;
use Carbon\Carbon;
use App\Models\UserSubscription;
use App\Models\NotificationUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\FirebasePushNotificationService;


class ActivityController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebasePushNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    
   public function list(Request $request)
    {
        try {
            $request->validate([
                'filterBy' => 'sometimes|string',
                'orderBy' => 'sometimes|in:ASC,DESC',
                'reg_id' => 'sometimes|string', // Allow string to handle comma-separated values
            ]);
    
            $user_id = auth()->user()->id;
            $orderBy = $request->input('orderBy') ?? 'DESC';
            $filterBy = $request->input('filterBy', '');
            $reg_id = $request->input('reg_id', '');
    
            $filterByArray = $filterBy ? explode(',', $filterBy) : [];
            $regIdArray = $reg_id ? explode(',', $reg_id) : [];
    
            $activities = collect();
    
            $usersubscription = UserSubscription::where('user_id', $user_id)->first();
            $isDependent = $usersubscription ? $usersubscription->is_dependent == 1 : false;
    
            // Handle activities by reg_id if provided
            if (!empty($regIdArray)) {
                $activitiesByRegId = Activity::where('user_id', $user_id)
                    ->whereIn('reg_id', $regIdArray)
                    ->orderBy('created_at', $orderBy)
                    ->get()
                    ->map(function ($activity) {
                        $user = User::find($activity->user_id);
                        $activity->activity_date = $activity->created_at 
                                                    ? $activity->created_at->format('h:i A') 
                                                    : 'Unknown';

                        $activity->is_scheduled = $activity->is_scheduled ? true : false;
    
                        $activityMaster = ActivityMaster::where('name', '=', $activity->activity)->first();
    
                        return [
                            'activity' => [
                                'id' => $activity->id,
                                'trip_id' => $activity->trip_id,
                                'activity' => $activity->activity,
                                'activity_id' => $activityMaster ? $activityMaster->id : null, // Include activity_id
                                'activity_date' => $activity->activity_date,
                                'is_scheduled' => $activity->is_scheduled,
                                'created_at' => $activity->created_at,
                            ],
                            'user' => [
                                'id' => $user ? $user->id : null,
                                'name' => $user ? $user->name : 'Unknown',
                                'created_at' => $user ? $user->created_at : null,
                                'updated_at' => $user ? $user->updated_at : null,
                            ],
                        ];
                    });
    
                $activities = $activities->merge($activitiesByRegId);
            }
    
            // Handle activities for the main user
            if (empty($filterByArray) || in_array($user_id, $filterByArray)) {
                $mainUserActivities = Activity::where('user_id', $user_id)
                    ->orderBy('created_at', $orderBy)
                    ->get()
                    ->map(function ($activity) use ($user_id) {
                        $user = User::find($user_id);
                         $activity->activity_date = $activity->created_at 
                                                    ? $activity->created_at->format('h:i A') 
                                                    : 'Unknown';
                        $activity->is_scheduled = $activity->is_scheduled ? true : false;
    
                        $activityMaster = ActivityMaster::where('name', '=', $activity->activity)->first();
    
                        return [
                            'activity' => [
                                'id' => $activity->id,
                                'trip_id' => $activity->trip_id,
                                'activity' => $activity->activity,
                                'activity_id' => $activityMaster ? $activityMaster->id : null, // Include activity_id
                                'activity_date' => $activity->activity_date,
                                'is_scheduled' => $activity->is_scheduled,
                                'created_at' => $activity->created_at,
                            ],
                            'user' => [
                                'id' => $user->id,
                                'name' => $user->name,
                                'created_at' => $user->created_at,
                                'updated_at' => $user->updated_at,
                            ],
                        ];
                    });
    
                $activities = $activities->merge($mainUserActivities);
            }
    
            // Handle dependent users' activities
            if ($usersubscription && !$isDependent) {
                $dep_user_subscriptions = UserSubscription::where('referral_id', $user_id)
                    ->when(!empty($filterByArray), function ($query) use ($filterByArray) {
                        $query->whereIn('user_id', $filterByArray);
                    })
                    ->get();
    
                foreach ($dep_user_subscriptions as $dep_user_subscription) {
                    $user = User::find($dep_user_subscription->user_id);
    
                    $dependentActivities = Activity::where('user_id', $dep_user_subscription->user_id)
                        ->orderBy('created_at', $orderBy)
                        ->get()
                        ->map(function ($activity) use ($user) {
                            $activity->activity_date = $activity->created_at->format('h:i A');
                            $activity->is_scheduled = $activity->is_scheduled ? true : false;
    
                            $activityMaster = ActivityMaster::where('name', '=', $activity->activity)->first();
    
                            return [
                                'activity' => [
                                    'id' => $activity->id,
                                    'activity' => $activity->activity,
                                    'trip_id' => $activity->trip_id,
                                    'activity_id' => $activityMaster ? $activityMaster->id : null, // Include activity_id
                                    'activity_date' => $activity->activity_date,
                                    'is_scheduled' => $activity->is_scheduled,
                                    'created_at' => $activity->created_at,
                                ],
                                'user' => [
                                    'id' => $user->id,
                                    'name' => $user->name,
                                    'created_at' => $user->created_at,
                                    'updated_at' => $user->updated_at,
                                ],
                            ];
                        });
    
                    $activities = $activities->merge($dependentActivities);
                }
            }
    
            return response()->json([
                'is_dependent' => $isDependent,
                'activities' => $activities,
            ], 200);
    
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve activities: ' . $e->getMessage());
    
            return response()->json([
                'error' => 'Failed to retrieve activities',
                'message' => $e->getMessage(),
            ], 500);
        }
    }






    // private function formatActivityTime($createdAt)
    // {
    //     if ($createdAt->isToday()) {
    //         return 'Today at ' . $createdAt->format('h:i A');
    //     } elseif ($createdAt->isYesterday()) {
    //         return 'Yesterday at ' . $createdAt->format('h:i A');
    //     } else {
    //         return $createdAt->format('d F Y');
    //     }
    // }
    
    public function formatActivityTime($dateTime)
    {
        return $dateTime->format('Y-m-d H:i A');
    }



    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'activity' => 'required|exists:activity_masters,id', // Validate the activity ID
                'activity_by' => 'required',
                'reg_id' => 'nullable|exists:registrations,id',
            ]);
            
            $name = ActivityMaster::where('id',$validatedData['activity'])->first();
            
            if (!empty($validatedData['reg_id'])) {
                $userSubscription = UserSubscription::where('reg_id', $validatedData['reg_id'])->first();
    
                if (!$userSubscription || $userSubscription->referral_id !== auth()->user()->id) {
                    return response()->json([
                        'error' => 'Invalid reg_id',
                        'message' => 'The reg_id does not belong to a dependent of the primary user.',
                    ], 400);
                }
                
                $validatedData['activity'] = $name->name;
                $validatedData['reg_id'] = $validatedData['reg_id'];
                $validatedData['user_id'] = $userSubscription->referral_id;
                $validatedData['activity_date'] = Carbon::now(); 
                
            }else
            {
                $validatedData['activity'] = $name->name;
                $validatedData['reg_id'] = NULL;
                $validatedData['user_id'] = auth()->user()->id;
                $validatedData['activity_date'] = Carbon::now(); 
            }
            
    
            $activity = Activity::create($validatedData);

            $notifyUser = User::where('id', auth()->user()->id)->first();

            if($notifyUser && $notifyUser->device_token){
                NotificationUser::create([
                    'form_user_id' => $notifyUser->id,
                    'to_user_id' => $notifyUser->id,
                    'title' => 'First Health',
                    'type' => 'notification',
                    'body' => $name->name,
                    'is_sent' => 1,
                    'created_by' => $notifyUser->id,
                ]);
                $deviceToken = $notifyUser->device_token;
                $title = 'First Health';
                $body = $name->name;

                $this->firebaseService->sendNotification($deviceToken, $title, $body);
            }
    
            return response()->json($activity, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
    
            return response()->json([
                'error' => 'Validation Error',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
    
            Log::error('Failed to store activity: ' . $e->getMessage());
    
            return response()->json([
                'error' => 'Failed to store activity',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function activity_masters(Request $request)
    {
        $faqs = ActivityMaster::all()->makeHidden(['created_at', 'updated_at']);

        return response()->json($faqs);
    }
    
    public function getLatestActivity()
    {
        try {
            $latestActivity = Activity::where('user_id', auth()->user()->id)
                                      ->latest('activity_date') // Get the latest by activity_date
                                      ->first();
            
            if ($latestActivity) {
                if ($latestActivity->activity == 'Ambulance Cancelled') {
                    return response()->json(['message' => 'No scheduled activity found'], 404);
                }
    
                if ($latestActivity->activity == 'Ambulance Scheduled') {
                    return response()->json($latestActivity, 200);
                }
            }
    
            // If no scheduled activity was found, return a 404
            return response()->json(['message' => 'No activity found'], 404);
    
        } catch (\Exception $e) {
            Log::error('Failed to retrieve latest activity: ' . $e->getMessage());
    
            return response()->json([
                'error' => 'Failed to retrieve latest activity',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    

}
