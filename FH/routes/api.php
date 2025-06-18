<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\FAQController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TCController;
use App\Http\Controllers\Api\DependantController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AmbulanceController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\CRMController;
use App\Http\Controllers\Api\ZohoController;
use App\Http\Controllers\Api\GoogleController;
use App\Http\Controllers\Api\RoasterMappingController;

use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Mail;
use App\Events\AmbulanceNotification;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Auth;
use Pusher\Pusher;
Route::post('/pusher/auth', function (Request $request) {
    if (!Auth::guard('api')->check()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $pusher = new Pusher(
        env('PUSHER_APP_KEY'),
        env('PUSHER_APP_SECRET'),
        env('PUSHER_APP_ID'),
        [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS' => true,
        ]
    );

    return response()->json($pusher->authorizeChannel($request->channel_name, $request->socket_id));
})->middleware('auth:api'); // ✅ Use auth:api Middleware


Route::post('/test-broadcast', function (Request $request) {
    $data = [
        'user_id' => 46,
        'name' => "Dhinakaran P",
          'date' => 2025-03-20,
          'phone' => 5854544544,
          "trip" => "Emergency Trip",
          "location" => "2/23, Balu Nagar 3rd St, TS Krishna Nagar, J J Nagar, Mogappair East, Chennai, Tamil Nadu 600050, India",
          "member_id" => "",
          "trip_id" => 7,
          "hospital_name" => "Pantai Hospital",
          "hospital_address" => "Kuala Lampur",
    ];

    broadcast(new AmbulanceNotification($data));
    // broadcast(new \App\Events\LocationUpdate([
    //     'user_id' => 46,
    //     'latitude' => 12.9716,
    //     'longitude' => 77.5946
    // ]));

    //Log::info("AmbulanceNotification event broadcasted", $data);

    return response()->json(['message' => 'Event broadcasted!']);
});




Route::post('/send-test-email', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
    ]);

    $email = $request->email;

    // Send the test email
    Mail::raw('Test email contents', function ($message) use ($email) {
        $message->to($email)->subject('Test Email');
    });

    return response()->json([
        'message' => 'Test email sent successfullys!'
    ], 200);
});

Route::get('/download-file/{filename}', function ($filename) {
    $filePath = "public/uploads/" . $filename;

    if (Storage::exists($filePath)) {
        return Storage::download($filePath);
    }

    return response()->json(['message' => 'File not found'], 404);
})->name('download.file');



Route::get('/zoho/auth', [ZohoController::class, 'redirectToZoho']);

Route::get('/zoho/callback', [ZohoController::class, 'handleCallback'])->name('zoho.callback');

Route::post('/payment/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');

Route::post('/payment', [PaymentController::class, 'testSavePayment'])->name('payment.testSavePayment');


Route::get('/payment/return', [PaymentController::class, 'paymentReturn'])->name('payment.return');

Route::post('/get-nearby-locations', [LocationController::class, 'getNearbyLocations']);


Route::get('/activity-masters', [ActivityController::class, 'activity_masters']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
  //  Route::post('reset-password', [LoginController::class, 'resetPassword']);
});

Route::post('/register/step1', [RegistrationController::class, 'step1']);

Route::post('/register/step2', [RegistrationController::class, 'step2']);
Route::post('/register/RemindMe', [RegistrationController::class, 'remindMe']);
Route::post('/register/step3', [RegistrationController::class, 'step3']);
Route::post('/register/step4', [RegistrationController::class, 'step4']);
Route::post('/register/step5', [RegistrationController::class, 'step5']);
Route::post('/register/step6', [RegistrationController::class, 'step6']);
Route::post('/getUserSubscriptionByReferralNo', [RegistrationController::class, 'getUserSubscriptionByReferralNo']);

Route::post('/refUserSubscription', [SubscriptionController::class, 'refUserSubscription']);

Route::get('/memberList', [SubscriptionController::class, 'memberList']);

Route::post('login', [LoginController::class, 'login']);

Route::post('forgotPassword', [LoginController::class, 'forgotPassword']);

Route::post('resetpassword', [LoginController::class, 'resetPassword']);

Route::apiResource('subscriptions', SubscriptionController::class);

Route::post('/subscriptionplans', [SubscriptionController::class, 'subscriptionplans']);

Route::get('/sublist', [SubscriptionController::class, 'sublist']);

Route::get('/download-terms-pdf', [TCController::class, 'downloadPdf']);

Route::get('/activity-masters', [ActivityController::class, 'activity_masters']);

/*Ambulance*/
Route::post('/ambulance', [AmbulanceController::class, 'ambulanceStore']);

Route::post('/ambulanceEdit', [AmbulanceController::class, 'ambulanceEdit']);

Route::post('/slotUpdate', [RegistrationController::class, 'slotUpdate']);

Route::post('/drivers', [DriverController::class, 'store']);

Route::get('/drivers/{id}', [DriverController::class, 'get']);

Route::get('/getAllLocations', [LocationController::class, 'getAllLocations']);

Route::post('/check-coverage', [LocationController::class, 'checkCoverage']);

Route::post('/locations', [LocationController::class, 'store']);

/*trips*/
Route::post('/trips', [TripController::class, 'storeTrip']);

Route::get('/faqs', [FAQController::class, 'index']);
Route::post('/faqs', [FAQController::class, 'store']);
Route::get('/faqs/{id}', [FAQController::class, 'show']);
Route::post('/faqs/update', [FAQController::class, 'update']);
Route::delete('/faqs', [FAQController::class, 'destroy']);
Route::post('/updateweb', [FAQController::class, 'updateweb']);

/*TC*/
Route::post('/tc/store', [TCController::class, 'store']);
Route::get('/tc', [TCController::class, 'list']);
Route::post('/tc', [TCController::class, 'update']);
Route::delete('/tc', [TCController::class, 'destroy']);

/*members*/

Route::post('/members', [SubscriptionController::class, 'insertMember']);

// Update a member
Route::post('/editMember', [SubscriptionController::class, 'editMember']);

// Delete a member
Route::delete('/members', [SubscriptionController::class, 'deleteMember']);

Route::post('/Benefitstore', [SubscriptionController::class, 'Benefitstore']);

Route::post('/Benefitedit', [SubscriptionController::class, 'Benefitedit']);

Route::delete('/Benefit_delete', [SubscriptionController::class, 'Benefit_delete']);



Route::post('/verifyOtp', [RegistrationController::class, 'verifyOtp']);

Route::get('/users', [LoginController::class, 'getAllUsers']);

Route::post('/email-verify', [RegistrationController::class, 'Emailverify']);

Route::post('/otp-verify', [RegistrationController::class, 'Otpverify']);

/* =========================CRM===========================*/

Route::post('/crm/dependents', [CRMController::class, 'getDependents']);

Route::post('/crm/Manualdependents', [CRMController::class, 'getManualDependent']);

Route::post('/crm/getUserSubscriptionCRM', [SubscriptionController::class, 'getUserSubscriptionCRM']);

Route::post('/crm/Additionalcharge', [CRMController::class, 'Additionalcharge']);

Route::get('/crm/getUserGraphData', [CRMController::class, 'getUserGraphData']);

Route::post('/roaster-mapping', [RoasterMappingController::class, 'storeOrUpdate']);

Route::post('/getHospitalsByDistance', [RoasterMappingController::class, 'getHospitalsByDistance']);

Route::post('/getDriversByDistance', [DriverController::class, 'getDriversByDistance']);

Route::post('/checkDriverRecord', [RoasterMappingController::class, 'checkDriverRecord']);

Route::get('/driver-distance', [DriverController::class, 'driverDistance']);





/*Google Oauth*/

Route::get('/oauth/redirect', [GoogleController::class, 'redirectToGoogle']);

Route::get('/oauth/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::post('/send-email', [GoogleController::class, 'sendEmail']);


/*Drivers login API*/


Route::post('/save-vehicle', [DriverController::class, 'saveVehicle']);

Route::post('/save-paramedic', [DriverController::class, 'saveParamedic']);

Route::post('/save-hospital', [DriverController::class, 'saveHospital']);

Route::get('/get-ambulance', [AmbulanceController::class, 'getAmbulance']);

Route::post('/getTripById', [TripController::class, 'getTripById']);




Route::middleware(['auth:api', 'check.token.expiry', 'check.device.token', 'action.logger'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    /*CRM*/
    Route::post('/crm/hotCalltoCRM', [CRMController::class, 'hotCalltoCRM']);
    
    Route::post('/update-driver-status', [DriverController::class, 'updateDriverStatus']);
    
    Route::post('/update-ride-status', [DriverController::class, 'updateRideStatus']);

    
    Route::post('logout', [LoginController::class, 'logout']);
    
    Route::post('/change-password', [LoginController::class, 'changePassword']);

    
    Route::post('deactivate-account', [LoginController::class, 'deactivate']);
    
    Route::get('/getUserSubscriptionById', [SubscriptionController::class, 'getUserSubscriptionById']);
    
    Route::get('/profile/address', [ProfileController::class, 'getAddressInfo']);
    
    Route::get('/profile/medical', [ProfileController::class, 'getMedicalInfo']);
    
    Route::get('/profile/personal', [ProfileController::class, 'getPersonalInfo']);
    
     Route::post('/profile-edit', [ProfileController::class, 'profileEditing']);
     
     Route::post('/dependentUserDetails', [DependantController::class, 'dependentUserDetails']);
     
    Route::post('/DependentRemove', [DependantController::class, 'DependentRemove']);
    
    Route::post('/ManualRemove', [DependantController::class, 'ManualRemove']);

    Route::post('/downgradePlan', [SubscriptionController::class, 'downgradePlan']);
    
    Route::post('/renewSlot', [SubscriptionController::class, 'renewSlot']);
    
    Route::get('/latest-activity', [ActivityController::class, 'getLatestActivity']);
    
    Route::post('/purchaseSlotUpdate', [DependantController::class, 'purchaseSlotUpdate']);
    
    Route::post('/renewSlotUpdate', [SubscriptionController::class, 'renewSlotUpdate']);
    
    Route::post('/readUser', [SubscriptionController::class, 'readUser']);
    
    Route::post('/getLatestTrip', [TripController::class, 'getLatestTrip']);
    
    /*CRM*/
    Route::post('/hotCalltoCRM', [CRMController::class, 'hotCalltoCRM']);
    
    Route::post('/DriverCalltoCRM', [DriverController::class, 'DriverCalltoCRM']);

    // In routes/api.php or routes/web.php
    
    /*jyothi*/
    Route::post('/activities_list', [ActivityController::class, 'list']);
    Route::post('/activities', [ActivityController::class, 'store']);
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/personal-info', [ProfileController::class, 'updatePersonalInfo']);
    Route::put('/profile/address-info', [ProfileController::class, 'updateAddressInfo']);
    Route::put('/profile/medical-info', [ProfileController::class, 'updateMedicalInfo']);
    
    /*end*/
    
    /*karthi*/
    Route::get('/dependent', [DependantController::class, 'index']);
    Route::get('/dependent/{id}', [DependantController::class, 'getById']);
    Route::post('/dependant', [DependantController::class, 'storeOrUpdate']);
    Route::post('/dependant/invite', [DependantController::class, 'inviteDependent']);
    Route::post('/resend/invite', [DependantController::class, 'Resendinvite']);
    Route::post('/dependant/invite-status', [DependantController::class, 'updateInviteStatus']);
    Route::get('/dependant/invited-users', [DependantController::class, 'invitedUserList']);
    Route::get('/dependant/moreinfo/{id}', [DependantController::class, 'moreInfo']);
    Route::post('/dependant/purchase-slots', [DependantController::class, 'purchaseSlots']);
    Route::post('/dependant/release-slot/{id}', [DependantController::class, 'releaseSlot']);
    Route::post('/dependant/revoke/{id}', [DependantController::class, 'revokeDependent']);
    /*end*/

    Route::post('/send-notification', [NotificationController::class, 'sendPushNotification']);
    
    /*Drivers login*/
    
    Route::post('driver_availability', [LoginController::class, 'driver_availability']);
    
    Route::post('/drivers/update-profile', [DriverController::class, 'updateProfile']);
    
    Route::match(['get', 'post'], '/drivers/getDriverProfile', [DriverController::class, 'getDriverProfile']);
    
    Route::post('/driver-declined-reason', [DriverController::class, 'driverDecline']);
    
    Route::get('/getDriverStatus', [DriverController::class, 'getDriverStatus']);
    
     Route::get('/getTripStatus', [DriverController::class, 'getTripStatus']);

    Route::post('/driverTripHistory', [DriverController::class, 'driverTripHistory']);
    
    Route::post('/AmbulanceAccept', [AmbulanceController::class, 'AmbulanceAccept']);
    
    Route::post('/TripDetails', [AmbulanceController::class, 'TripDetails']);
    
    Route::post('/driversCurrentTripDetails', [AmbulanceController::class, 'driversCurrentTripDetails']);
    
    Route::post('/uploadPCRFile', [DriverController::class, 'uploadPCRFile']);
    
    Route::get('/DriverTripCount', [DriverController::class, 'DriverTripCount']);
    
    Route::post('/Driverlive', [DriverController::class, 'Driverlive']);
    
    Route::post('/UserTripCancel', [AmbulanceController::class, 'UserTripCancel']);
    
    Route::post('/userCurrentTripDetails', [AmbulanceController::class, 'userCurrentTripDetails']);
    
    


});





