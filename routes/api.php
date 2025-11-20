<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Helpers\CustomHelper;
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
use App\Http\Controllers\Api\Cron\CronController;
use App\Http\Controllers\Api\FacebookController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PagesController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\Api\Google\GoogleController;
use App\Http\Controllers\Api\OctoparseController;

// use App\Http\Controllers\Api\ZohoController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/otps/{email}', [ApiController::class, 'regOtps']);

Route::get('/check_api', function () {
    return "api is working!-all-ok-final-1";
});

Route::get('/test-postcode', function () {
    $cordinates = json_decode(CustomHelper::getCoordinates('ST6 2FD'), true);
    dd($cordinates);
});


Route::get('/test-function', [MyRequestController::class, 'sendNewLeadRequestAutoBidOff']);

// Route::get('/mail-test', [ContactUsController::class, 'sendMailInBackground']);
Route::get('/mail-test', [ApiController::class, 'mailTest']);
Route::post('/update-sms-status', [ApiController::class, 'updateSmsStatus']);
Route::post('/resend-otp', [ApiController::class, 'resendOtp']);

Route::post('/contact-us', [ContactUsController::class, 'store']);

Route::get('lead-purchase-status-update-log/{lead_id}/{seller_id}/{buyer_id}/{log}', [UserController::class, 'leadPurchaseStatusUpdateLog']);
Route::post('request-otp', [ApiController::class, 'requestOtp']);
Route::post('verify-otp', [ApiController::class, 'verifyOtp']);

Route::get('zoho-leads-available', [LeadPreferenceController::class, 'zohoLeadsAvailable']);

Route::post('get-city-name', [ApiController::class, 'getCityName']);

Route::post('get-progress-percentage', [ApiController::class, 'getProgressPercentage']);

// Route::get('zoho/callback', [ZohoController::class, 'handleCallback']);
Route::prefix('check')->group(function () {
    Route::post('/email-id', [UserController::class, 'checkEmailId']);
    Route::post('/phone-number', [UserController::class, 'checkPhoneNumber']);
    Route::post('/company-name', [UserController::class, 'checkCompanyName']);
    Route::post('/company-location', [UserController::class, 'checkCompanyLocation']);
    Route::post('/company-name-without-reg', [UserController::class, 'checkCompanyNameWithoutReg']);
});

//cron for lead buyer registration email
Route::prefix('cron')->group(function () {
    Route::get('closed-leads', [RecommendedLeadsController::class, 'closeLeads']);
    Route::get('on-hourly-basis', [CronController::class, 'onHourlyBasis']);
    Route::get('on-day-basis', [CronController::class, 'onDayBasis']);
    Route::get('on-two-basis', [CronController::class, 'onTwoDayBasis']);
    Route::get('on-evening-basis', [CronController::class, 'onEveningBasis']);
});

Route::prefix('notification')->group(function () {

    Route::get('notification-cron-logs', [NotificationController::class, 'notificationCronLogs']);

    Route::middleware('auth:sanctum', 'authMiddleware')->group(function () {
        Route::post('add-update-notification-settings', [NotificationController::class, 'addUpdateNotificationSettings']);
        Route::post('get-notification-settings', [NotificationController::class, 'getNotificationSettings']);
        Route::post('fetch-all-notifications', [NotificationController::class, 'getAllNotifications']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllNotifications']);
    });
});

Route::prefix('review')->group(function () {
    Route::post('submit-review', [ReviewController::class, 'submitReview']);
    Route::get('get-reviews/{id}', [ReviewController::class, 'getReviews']);
    Route::post('get-profile', [ReviewController::class, 'getProfile']);
    Route::middleware('auth:sanctum', 'authMiddleware')->group(function () {
        Route::get('get-customer-link', [ReviewController::class, 'getCustomerLink']);
    });
});
Route::prefix('payment')->group(function () {

    Route::middleware('auth:sanctum', 'authMiddleware')->group(function () {
        Route::post('/buy-credits', [PaymentController::class, 'buyCredits']);
        Route::get('/get-transaction-logs', [PaymentController::class, 'getTransactionLogs']);
        Route::get('/get-invoices', [PaymentController::class, 'getInvoices']);
        Route::post('/download-invoice', [PaymentController::class, 'downloadInvoice']);
    });
});


Route::prefix('google')->group(function () {
    Route::post('get-auth-token', [GoogleController::class, 'getAuthToken']);

    Route::middleware('auth:sanctum', 'authMiddleware')->group(function () {
        Route::post('get-google-reviews', [GoogleController::class, 'getGoogleReviews']);
    });
});

Route::prefix('octoparse')->group(function () {

    Route::middleware('auth:sanctum', 'authMiddleware')->group(function () {
        Route::post('get-google-reviews', [OctoparseController::class, 'getGoogleReviews']);
    });
});




Route::prefix('customer')->group(function () {
    Route::post('register-quote-customer', [MyRequestController::class, 'registerQuoteCustomer']);
    Route::post('my-request/check-paragraph-quality', [MyRequestController::class, 'checkParagraphQuality']);
    Route::post('my-request/create-new-request', [MyRequestController::class, 'createNewRequest']);
    Route::post('verify-phone-number', [MyRequestController::class, 'verifyPhoneNumber']);
    Route::post('update-register-phone-number', [MyRequestController::class, 'updateRegisterPhoneNumber']);
    Route::middleware('auth:sanctum', 'authMiddleware')->group(function () {

        Route::prefix('my-request')->group(function () {
            Route::get('get-submitted-request-list', [MyRequestController::class, 'getSubmittedRequestList']);
            Route::get('get-submitted-request-info', [MyRequestController::class, 'getSubmittedRequestInfo']);
            Route::post('add-image-to-submitted-request', [MyRequestController::class, 'addImageToSubmittedRequest']);
            Route::post('add-details-to-request', [MyRequestController::class, 'addDetailsToRequest']);
        });

        Route::prefix('setting')->group(function () {
            Route::get('get-profile-info', [AccountSettingController::class, 'getProfileInfo']);
            Route::post('update-profile-image', [AccountSettingController::class, 'updateProfileImage']);
            Route::post('update-profile-info', [AccountSettingController::class, 'updateProfileInfo']);
            Route::post('change-password', [AccountSettingController::class, 'changePassword']);
        });
    });
});

Route::prefix('users')->group(function () {
    //Route::get('/', [UserController::class, 'index']);
    Route::get('/fetch_company_details/{regNumber}', [UserController::class, 'fetch_company_details']);
    Route::post('/questions-answer', [LeadPreferenceController::class, 'questionAnswer']);

    Route::post('/pending-leads', [LeadPreferenceController::class, 'pendingLeads']);
    Route::get('/popular-services', [ApiController::class, 'popularServices']);
    Route::get('/home-services', [ApiController::class, 'homeServices']);
    Route::post('/user-available-popular-services', [ApiController::class, 'popularUserServices']);

    Route::post('/search-services', [ApiController::class, 'searchServices']);
    Route::post('/search-available-services', [ApiController::class, 'searchAvailableServices']);
    Route::post('/leads-search-services', [ApiController::class, 'leadsSearchServices']);
    Route::get('/get-categories', [ApiController::class, 'getCategories']);
    Route::post('/registration', [UserController::class, 'registration']);
    Route::get('/all-services', [ApiController::class, 'allServices']);
    Route::get('/bottom-pages', [PagesController::class, 'bottomPages']);
    Route::get('/page-details/{slug}', [PagesController::class, 'pageDetails']);
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/create-login-magic-link', [UserController::class, 'createLoginMagicLink']);
    Route::post('/get-seller-profile', [UserController::class, 'getSellerProfile']);

    Route::get('test-api', [ApiController::class, 'testApi']);
    Route::post('test-api', [ApiController::class, 'testApi']);


    Route::middleware('auth:sanctum', 'authMiddleware')->group(function () {



        Route::post('change-primary-service', [LeadPreferenceController::class, 'changePrimaryService']);
        Route::post('expand-radius', [LeadPreferenceController::class, 'expandRadius']);

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
        Route::post('/get-pending-leads', [LeadPreferenceController::class, 'getPendingLeads']);
        Route::post('/archive-pending-lead', [LeadPreferenceController::class, 'archivePendingLead']);
        Route::post('/get-archive-leads', [LeadPreferenceController::class, 'getArchiveLeads']);
        Route::post('/unarchive-pending-lead', [LeadPreferenceController::class, 'unarchivePendingLead']);
        Route::post('/get-hired-leads', [LeadPreferenceController::class, 'getHiredLeads']);
        Route::post('/add-hired-leads', [LeadPreferenceController::class, 'addHiredLeads']);
        Route::post('/submit-leads', [LeadPreferenceController::class, 'submitLeads']);


        Route::post('/remove-location', [LeadPreferenceController::class, 'removeLocation']);
        Route::post('/edit-location', [LeadPreferenceController::class, 'editUserLocation']);
        Route::post('/remove-service', [LeadPreferenceController::class, 'removeService']);
        Route::post('/add_location', [LeadPreferenceController::class, 'addUserLocation']);

        Route::post('/add_service', [LeadPreferenceController::class, 'addUserService']);
        Route::post('/get-services', [LeadPreferenceController::class, 'getservices']);
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
        Route::get('/get-seller-dashboard-stats', [UserController::class, 'getSellerDashboardStats']);
        Route::post('/update-profile-image', [UserController::class, 'updateProfileImage']);
        Route::post('/change-password', [UserController::class, 'changePassword']);
        Route::post('/update-profile', [UserController::class, 'updateProfile']);
        Route::post('/edit-profile', [UserController::class, 'editProfile']);


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
        Route::post('seller-card-remove', [SettingController::class, 'removeCard']);
        Route::post('/seller-card-make-primary', [SettingController::class, 'makePrimaryCard']);

        //My Profile
        Route::post('/seller-myprofile-qa', [SettingController::class, 'sellerMyprofileqa']);
        Route::get('/seller-profile-ques', [SettingController::class, 'sellerProfileQues']);
        Route::post('/update-seller-profile', [SettingController::class, 'updateSellerProfile']);

        Route::post('/add-suggested-que', [SuggestedQuestionController::class, 'addSuggestedQue']);



        Route::post('/facebook/create-token', [FacebookController::class, 'exchangeToken']);
        Route::post('/facebook/get-token', [FacebookController::class, 'getSellerToken']);
        Route::post('/facebook/fetch-reviews', [FacebookController::class, 'fetchReviews']);
    });
});
