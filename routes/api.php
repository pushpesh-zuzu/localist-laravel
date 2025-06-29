<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Customer\AccountSettingController;
use App\Http\Controllers\Api\LeadPreferenceController;
use App\Http\Controllers\Api\Customer\MyRequestController;
use App\Http\Controllers\Api\SuggestedQuestionController;
use App\Http\Controllers\Api\RecommendedLeadsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\CreditPlanController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PagesController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




Route::get('/check_api', function () {
    return "check api";
});


Route::get('test_lead',[ApiController::class,'getLeadByPrefer']);




Route::prefix('notification')->group(function () {
    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        Route::post('add-update-notification-settings',[NotificationController::class,'addUpdateNotificationSettings']);
        Route::post('get-notification-settings',[NotificationController::class,'getNotificationSettings']);
    });
    
});

Route::prefix('review')->group(function () {
    Route::post('submit-review',[ReviewController::class,'submitReview']);
    Route::get('get-reviews/{id}',[ReviewController::class,'getReviews']);
    
    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        Route::get('get-customer-link',[ReviewController::class,'getCustomerLink']);
        
    });

    
});
Route::prefix('payment')->group(function () {

    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        Route::post('/buy-credits', [PaymentController::class, 'buyCredits']);
        Route::get('/get-transaction-logs', [PaymentController::class, 'getTransactionLogs']);
        Route::get('/get-invoices', [PaymentController::class, 'getInvoices']);
        Route::post('/download-invoice', [PaymentController::class, 'downloadInvoice']);
    });

    
});


Route::prefix('customer')->group(function () {
    Route::get('test',[CustomerController::class,'test']);
    Route::get('test',[InvoiceController::class,'test']);

    Route::post('my-request/check-paragraph-quality',[MyRequestController::class,'checkParagraphQuality']);
    Route::post('my-request/create-new-request',[MyRequestController::class,'createNewRequest']);
    Route::post('verify-phone-number',[MyRequestController::class,'verifyPhoneNumber']);
    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        
        Route::prefix('my-request')->group(function () {
            Route::get('get-submitted-request-list',[MyRequestController::class,'getSubmittedRequestList']);
            Route::get('get-submitted-request-info',[MyRequestController::class,'getSubmittedRequestInfo']);
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
    Route::post('/questions-answer', [LeadPreferenceController::class, 'questionAnswer']);
    Route::get('/closed-leads', [RecommendedLeadsController::class, 'closeLeads']);
    Route::post('/pending-leads', [LeadPreferenceController::class, 'pendingLeads']);
    Route::get('/popular-services', [ApiController::class, 'popularServices']);
    Route::post('/search-services', [ApiController::class, 'searchServices']);
    Route::post('/leads-search-services', [ApiController::class, 'leadsSearchServices']);
    Route::get('/get-categories', [ApiController::class, 'getCategories']);
    Route::post('/registration', [UserController::class, 'registration']);
    Route::get('/all-services', [ApiController::class, 'allServices']);
    Route::get('/bottom-pages', [PagesController::class, 'bottomPages']);
    Route::get('/page-details/{slug}', [PagesController::class, 'pageDetails']);
    Route::post('/login', [UserController::class, 'login']);

    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        Route::post('change-primary-service', [LeadPreferenceController::class, 'changePrimaryService']);
        Route::post('expand-radius',[LeadPreferenceController::class, 'expandRadius']);

        Route::post('/get-seller-recommended-leads', [LeadPreferenceController::class, 'getSellerRecommendedLeads']);
        Route::post('get-seven-days-autobid-pause', [LeadPreferenceController::class, 'getSevenDaysAutobidPause']);
        Route::post('/get-service-wise-location', [LeadPreferenceController::class, 'getServiceWiseLocation']);
        Route::post('/seven-days-autobid-pause', [LeadPreferenceController::class, 'sevenDaysAutobidPause']);
        Route::post('/get-save-for-later-list', [LeadPreferenceController::class, 'getSaveForLaterList']);
        Route::post('/get-lead-preferences', [LeadPreferenceController::class, 'getleadpreferences']);
        Route::post('/sort-by-credit-value', [LeadPreferenceController::class, 'sortByCreditValue']);
        Route::post('/get_user_locations', [LeadPreferenceController::class, 'getUserLocations']);
        Route::post('/get_user_services', [LeadPreferenceController::class, 'getUserServices']);
        Route::post('/lead-preferences', [LeadPreferenceController::class, 'leadpreferences']);
        Route::post('/get-lead-request', [LeadPreferenceController::class, 'getLeadRequest']);
        Route::post('/get-lead-profile', [LeadPreferenceController::class, 'getLeadProfile']);
        Route::post('/save-for-later', [LeadPreferenceController::class, 'saveForLater']);
        // Route::post('/sort-by-leads-date', [LeadPreferenceController::class, 'sortByLeadsEntries']);
        Route::post('/get-pending-leads', [LeadPreferenceController::class, 'getPendingLeads']);
        Route::post('/get-hired-leads', [LeadPreferenceController::class, 'getHiredLeads']);
        Route::post('/add-hired-leads', [LeadPreferenceController::class, 'addHiredLeads']);
        Route::post('/submit-leads', [LeadPreferenceController::class, 'submitLeads']);
        
        Route::post('/remove-location', [LeadPreferenceController::class, 'removeLocation']);
        Route::post('/edit-location', [LeadPreferenceController::class, 'editUserLocation']);
        Route::post('/remove-service', [LeadPreferenceController::class, 'removeService']);
        Route::post('/add_location', [LeadPreferenceController::class, 'addUserLocation']);
        
        Route::post('/add_service', [LeadPreferenceController::class, 'addUserService']);
        Route::post('/get-services', [LeadPreferenceController::class, 'getservices']);
        // Route::post('/sort-by-credit', [LeadPreferenceController::class, 'sortByCredit']);
        // Route::get('/get-credit-list', [LeadPreferenceController::class, 'getCreditList']);
        Route::post('/get-online-remote-switch', [LeadPreferenceController::class, 'getOnlineRemoteSwitch']);
        Route::post('/online-remote-switch', [LeadPreferenceController::class, 'onlineRemoteSwitch']);
        Route::post('/leads-by-filter', [LeadPreferenceController::class, 'leadsByFilter']);
        Route::post('/total-credit', [LeadPreferenceController::class, 'totalCredit']);
        Route::post('/response-status', [LeadPreferenceController::class, 'responseStatus']);
        Route::post('/seller-notes', [LeadPreferenceController::class, 'sellerNotes']);
        Route::post('/get-seller-notes', [LeadPreferenceController::class, 'getSellerNotes']);
        Route::post('/pending-purchase-type-filter', [LeadPreferenceController::class, 'pendingPurchaseTypeFilter']);
        Route::post('/hired-purchase-type-filter', [LeadPreferenceController::class, 'hiredPurchaseTypeFilter']);
        Route::post('/leads-enquiry', [LeadPreferenceController::class, 'leadsEnquiry']);
        

        //account details 
        Route::post('/update-profile-image', [UserController::class, 'updateProfileImage']);
        Route::post('/change-password', [UserController::class, 'changePassword']);
        Route::post('/update-profile', [UserController::class, 'updateProfile']);
        Route::post('/edit-profile', [UserController::class, 'editProfile']);
        Route::post('/get-seller-profile', [UserController::class, 'getSellerProfile']);

        Route::post('/switch_user', [UserController::class, 'switchUser']);
        Route::post('/logout', [UserController::class, 'logout']);

        Route::post('/add-multiple-manual-bid', [RecommendedLeadsController::class, 'addMultipleManualBid']);
        Route::post('/switch-autobid', [RecommendedLeadsController::class, 'switchAutobid']);
        Route::post('/get-switch-autobid', [RecommendedLeadsController::class, 'getAutobid']);
        Route::post('/buyer-view-profile', [RecommendedLeadsController::class, 'buyerViewProfile']);
        Route::post('/buyer-activities', [RecommendedLeadsController::class, 'buyerActivities']);
        Route::post('/autobid-list', [RecommendedLeadsController::class, 'getRepliesList']);
        // Route::post('/location-filter', [RecommendedLeadsController::class, 'locationFilter']);
        Route::post('/manual-leads', [RecommendedLeadsController::class, 'getManualLeads']);
        Route::post('/add-manual-bid', [RecommendedLeadsController::class, 'addManualBid']);
        Route::post('/autobid', [RecommendedLeadsController::class, 'addRecommendedLeads']);
        Route::post('/sort-by-location', [RecommendedLeadsController::class, 'sortByLocation']);
        Route::post('/response-time-filter', [RecommendedLeadsController::class, 'responseTimeFilter']);
        Route::post('/rating-filter', [RecommendedLeadsController::class, 'ratingFilter']);
        Route::post('/get-rating-filter', [RecommendedLeadsController::class, 'getRatingFilter']);
        
        //My Credits
        
        Route::post('/add-coupon', [CreditPlanController::class, 'addCoupon']);
        Route::post('/get-coupon', [CreditPlanController::class, 'getCoupon']);
        Route::get('/get-plans', [CreditPlanController::class, 'getPlans']);

        //Invoice & Billing details
        Route::post('/seller-billing-details', [SettingController::class, 'sellerBillingDetails']);

        //My Payment details
        Route::post('/seller-card-details', [SettingController::class, 'sellerCardDetails']);
        Route::get('/get-seller-card', [SettingController::class, 'getSellerCard']);

        //My Profile
        Route::post('/seller-myprofile-qa', [SettingController::class, 'sellerMyprofileqa']);
        Route::get('/seller-profile-ques', [SettingController::class, 'sellerProfileQues']);
        Route::post('/update-seller-profile', [SettingController::class, 'updateSellerProfile']);

        Route::post('/add-suggested-que', [SuggestedQuestionController::class, 'addSuggestedQue']);

        // routes/api.php
        Route::post('/send-notification', [NotificationController::class, 'sendNotification']);

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
