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

use Laravel\Passport\Passport;

// Register Passport routes
//Passport::routes();

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

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

Route::get('/download-terms-pdf', [TCController::class, 'downloadPdf']);


Route::get('/faqs', [FAQController::class, 'index']);
Route::post('/faqs', [FAQController::class, 'store']);
Route::get('/faqs/{id}', [FAQController::class, 'show']);
Route::put('/faqs/{id}', [FAQController::class, 'update']);
Route::delete('/faqs/{id}', [FAQController::class, 'destroy']);

Route::post('/verifyOtp', [RegistrationController::class, 'verifyOtp']);

Route::middleware(['auth:api', 'check.token.expiry'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    
    Route::post('logout', [LoginController::class, 'logout']);
    
    Route::post('deactivate-account', [LoginController::class, 'deactivate']);
    
    Route::get('/getUserSubscriptionById', [SubscriptionController::class, 'getUserSubscriptionById']);
    
    Route::get('/profile/address', [ProfileController::class, 'getAddressInfo']);
    
    Route::get('/profile/medical', [ProfileController::class, 'getMedicalInfo']);
    
    Route::get('/profile/personal', [ProfileController::class, 'getPersonalInfo']);
    
     Route::post('/profile-edit', [ProfileController::class, 'profileEditing']);
     
     Route::post('/dependentUserDetails', [DependantController::class, 'dependentUserDetails']);
     
    Route::post('/DependentRemove', [DependantController::class, 'DependentRemove']);

     Route::post('/downgradePlan', [SubscriptionController::class, 'downgradePlan']);

    // In routes/api.php or routes/web.php
    
    /*jyothi*/
    Route::post('/activities_list', [ActivityController::class, 'list']);
    Route::post('/activities', [ActivityController::class, 'store']);
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/personal-info', [ProfileController::class, 'updatePersonalInfo']);
    Route::put('/profile/address-info', [ProfileController::class, 'updateAddressInfo']);
    Route::put('/profile/medical-info', [ProfileController::class, 'updateMedicalInfo']);
    Route::get('/tc', [TCController::class, 'list']);
    Route::put('/tc', [TCController::class, 'update']);
    /*end*/
    
    /*karthi*/
    Route::get('/dependent', [DependantController::class, 'index']);
    Route::get('/dependent/details', [DependantController::class, 'details']);
    Route::post('/dependant', [DependantController::class, 'store']);
    Route::post('/dependant/edit', [DependantController::class, 'edit']);
    Route::post('/dependant/delete', [DependantController::class, 'delete']);
    Route::post('/dependant/invite', [DependantController::class, 'inviteDependent']);
    Route::post('/dependant/invite-status', [DependantController::class, 'updateInviteStatus']);
    Route::get('/dependant/invited-users', [DependantController::class, 'invitedUserList']);
    Route::get('/dependant/moreinfo', [DependantController::class, 'moreInfo']);
    /*end*/
});





