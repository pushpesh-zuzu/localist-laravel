<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\Customer\MyRequestController;
use App\Http\Controllers\Api\Customer\AccountSettingController;
use App\Http\Controllers\Api\NotificationController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




Route::get('/check_api', function () {
    return "check api";
});

Route::prefix('notification')->group(function () {
    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        Route::post('add-update-notification-settings',[NotificationController::class,'addUpdateNotificationSettings']);
        Route::post('get-notification-settings',[NotificationController::class,'getNotificationSettings']);
    });
    
});

Route::prefix('customer')->group(function () {

    Route::get('test',[CustomerController::class,'test']);
   
    Route::post('my-request/check-paragraph-quality',[MyRequestController::class,'checkParagraphQuality']);

    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        Route::prefix('my-request')->group(function () {
            Route::get('get-submitted-request-list',[MyRequestController::class,'getSubmittedRequestList']);
            Route::get('get-submitted-request-info',[MyRequestController::class,'getSubmittedRequestInfo']);
            Route::post('create-new-request',[MyRequestController::class,'createNewRequest']);
            Route::post('add-image-to-submitted-request',[MyRequestController::class,'addImageToSubmittedRequest']);
            Route::post('add-details-to-request',[MyRequestController::class,'addDetailsToRequest']);           
        });

        Route::prefix('setting')->group(function () {
            Route::get('get-profile-info',[AccountSettingController::class,'getProfileInfo']);
            Route::post('update-profile-image',[AccountSettingController::class,'updateProfileImage']);
            Route::post('update-profile-info',[AccountSettingController::class,'updateProfileInfo']);
            Route::post('change-password',[AccountSettingController::class,'changePassword']);
        });

        

    });

});





Route::prefix('users')->group(function () {
    //Route::get('/', [UserController::class, 'index']);
    Route::post('/registration', [UserController::class, 'registration']);
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/popular-services', [ApiController::class, 'popularServices']);
    Route::post('/search-services', [ApiController::class, 'searchServices']);
    Route::get('/get-categories', [ApiController::class, 'getCategories']);
    Route::post('/questions-answer', [ApiController::class, 'questionAnswer']);

    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        // add services 
        Route::post('/add_service', [UserController::class, 'addUserService']);
        Route::post('/add_location', [UserController::class, 'addUserLocation']);

        Route::post('/get_user_services', [UserController::class, 'getUserServices']);
        Route::post('/get_user_locations', [UserController::class, 'getUserLocations']);

        Route::post('/switch_user', [UserController::class, 'switchUser']);
        Route::post('/edit-profile', [UserController::class, 'editProfile']);
        Route::post('/update-profile', [UserController::class, 'updateProfile']);
        Route::post('/logout', [UserController::class, 'logout']);
        Route::post('/update-profile-image', [UserController::class, 'updateProfileImage']);
        Route::post('/change-password', [UserController::class, 'changePassword']);
        
        Route::post('/autobid', [ApiController::class, 'autobid']);
        Route::post('/autobid-list', [ApiController::class, 'autobidList']);

        Route::post('/lead-preferences', [ApiController::class, 'leadpreferences']);
        Route::post('/get-lead-preferences', [ApiController::class, 'getleadpreferences']);
        Route::post('/get-services', [ApiController::class, 'getservices']);

        Route::post('/switch-autobid', [ApiController::class, 'switchAutobid']);

        Route::post('/seller-myprofile', [ApiController::class, 'sellerMyprofile']);
        Route::post('/seller-myprofile-qa', [ApiController::class, 'sellerMyprofileqa']);
        Route::get('/seller-profile-ques', [ApiController::class, 'sellerProfileQues']);

        Route::post('/seller-billing-details', [ApiController::class, 'sellerBillingDetails']);
        Route::post('/seller-card-details', [ApiController::class, 'sellerCardDetails']);
        
    });
    // Route::get('/{id}', [UserController::class, 'show']);
    // Route::put('/{id}', [UserController::class, 'update']);
    //Route::delete('/{id}', [UserController::class, 'destroy']);

    // add services 

    // Route::post('/add_service', [UserController::class, 'addUserService']);
    // Route::post('/add_location', [UserController::class, 'addUserLocation']);
    // Route::post('/get_user_services', [UserController::class, 'getUserServices']);
    // Route::post('/get_user_locations', [UserController::class, 'getUserLocations']);
    // Route::post('/switch_user', [UserController::class, 'switchUser']);
    // Route::get('/get-categories', [UserController::class, 'getCategories']);
});
