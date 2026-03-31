<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceQuestionsController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\ProfileQuesController;
use App\Http\Controllers\RequestListController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\BlogsController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\EmailSettingsController;
use App\Helpers\WhatsAppMessage;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Api\Cron\CronController;
// use App\Http\Controllers\GoogleReviewController;
use App\Http\Controllers\MapController;
use Illuminate\Http\Request;
use App\Helpers\Zoho\ZohoHelper;
use App\Http\Controllers\ZohoOAuthTestController;
use Illuminate\Support\Facades\Http;
use App\Helpers\Zoho\ZohoEmails;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\d7LeadSupplierController;
use App\Http\Controllers\PurchaseInvoiceHistoryController;
use App\Http\Controllers\ZohoAccountImportController;
use App\Services\D7LeadFinderService;

Route::get('phpinfo', function () {
    phpinfo();
});

Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('config:cache');
    $exitCode = Artisan::call('view:clear');
    $exitCode = Artisan::call('optimize');
    return 'DONE'; //Return anything
});


// Route::get('/send-whatsapp-test', function () {
//     // recipient's phone number with country code
//     $message = 'Hello! This is a test message from Laravel WhatsApp API 🚀';

//     $response = WhatsAppMessage::sendMessage(
//         userId: null,
//         phoneNumber: "919026141516",
//         message: $message,
//         imageUrl: null, // optional
//         // optional
//         subject: 'Testing WhatsApp'
//     );

//     return response()->json($response);
// });

// Route::get('/send-whatsapp-template-test', function () {
//     $response = WhatsAppMessage::sendTemplate(
//         userId: null,
//         phoneNumber: "919026141516",
//         templateName: "lead_buyer_registration",
//         languageCode: "en_US",
//         components: [
//             [
//                 'type' => 'body',
//                 'parameters' => [
//                     ['type' => 'text', 'text' => 'Ashish'],
//                 ],
//             ],
//         ]
//     );

//     return response()->json($response);
// });

Route::get('/install-api', function () {
    $exitCode = Artisan::call('install:api');

    return 'DONE'; //Return anything
});

Route::get('/check-mail', function () {
    $dataUser['email'] = 'pushpesh@zuzucodes.com';
    $dataUser['name'] = 'Pushpesh';
    $dataUser['service'] = 'Web Development';
    $dataUser['password'] = '12345678';
    $dataUser['otp'] = '1234';
    Mail::send('emails.buyer_registration', $dataUser, function ($message) use ($dataUser) {
        $message->to($dataUser['email']);
        $message->subject("local-Welcome to Localist " . $dataUser['name'] . "!");
    });

    return 'DONE'; //Return anything
});


// Route::get('/', function () {
//     return view('dashboard');
// })->middleware(['auth:admin', 'verified'])->name('dashboard');
// Route::get('/', [DashboardController::class, 'index'])->middleware(['auth:admin', 'verified'])->name('dashboard');
Route::middleware('auth:admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth:admin'])->name('dashboard');
    Route::get('/users/{type?}', [UserController::class, 'index'])->name('user.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/service-map', [MapController::class, 'index'])->name('service-map.index');
    Route::get('/service-map/data', [MapController::class, 'data'])->name('service-map.data');
    Route::get('/service-map/export', [MapController::class, 'exportCsv'])->name('service-map.export');

    Route::resource('sectors', SectorController::class);

    Route::resource('blogs', BlogsController::class);
    Route::resource('pages', PagesController::class);
    Route::resource('menus', MenuController::class);
    Route::resource('plans', PlansController::class);
    Route::resource('settings', SettingController::class);
    Route::resource('buyer', BuyerController::class);
    Route::get('buyer/{type}/{id}', [BuyerController::class, 'show'])->name('buyer.show.custom');
    Route::post('buyer/update-platform-source', [BuyerController::class, 'updatePlatformSource'])->name('buyer.updatePlatformSource');
    Route::get('buyer-lead-details/{leadid}', [BuyerController::class, 'leadDetails'])->name('buyer.leadDetails');
    Route::get('buyer-incomplete-list', [BuyerController::class, 'incompletelist'])->name('buyer.incompletelist');
    Route::get('buyer-test-complete-list', [BuyerController::class, 'testUserCompleteList'])->name('buyer.testusercompletelist');
    Route::get('buyer-test-incomplete-list', [BuyerController::class, 'testUserInCompleteList'])->name('buyer.testuserincompletelist');
    Route::get('buyer-bids/{userid}', [BuyerController::class, 'buyerBids'])->name('buyer.buyerBids');
    Route::get('buyer-login/{userid}', [BuyerController::class, 'buyerLogin'])->name('buyer.buyerLogin');
    Route::get('buyer-viewcount/{userid}', [BuyerController::class, 'viewCount'])->name('buyer.viewCount');
    Route::get('buyer-contact-form', [BuyerController::class, 'contactForm'])->name('buyer.contact_form');
    Route::get('buyer-show-contact-form/{id}', [BuyerController::class, 'viewContactForm'])->name('buyer.show_contact_form');
    Route::delete('/delete-user/{id}', [BuyerController::class, 'destroy'])->name('user.destroy');
    Route::delete('/contact/{id}', [BuyerController::class, 'deleteContact'])->name('contact.delete');

    Route::get('/export-buyer-excel', [BuyerController::class, 'exportBuyerExcelList'])
        ->name('export.buyer.excel');
    Route::get('/export-buyer-csv', [BuyerController::class, 'exportBuyerCsvList'])
        ->name('export.buyer.csv');

    Route::get('/zoho/send/{type}/{id}', [BuyerController::class, 'sendToZoho'])
        ->name('zoho.send');
    Route::resource('seller', SellerController::class);
    Route::post('seller/custom-reviews/save', [SellerController::class, 'sellerSaveCustomReview'])->name('seller.save.custom.review');
    Route::get('/seller/get-credit/{user}', [SellerController::class, 'getCredit'])->name('seller.getCredit');
    Route::post('/seller/add-credit', [SellerController::class, 'addCredit'])->name('seller.addCredit');
    Route::post('/seller/deduct-credit', [SellerController::class, 'deductCredits'])->name('seller.deductCredits');
    Route::get('/seller/get-autobid-settings/{user}', [SellerController::class, 'getAutobidSettings'])->name('seller.getAutobidSettings');
    Route::post('/seller/update-autobid-settings', [SellerController::class, 'updateAutobidSettings'])->name('seller.updateAutobidSettings');
    Route::get('seller/{type}/{id}', [SellerController::class, 'show'])->name('seller.show.custom');
    Route::get('seller-complete-list', [SellerController::class, 'index'])->name('seller.complete');
    Route::get('seller-contact-form', [SellerController::class, 'contactForm'])->name('seller.contact_form');
    Route::get('seller-show-contact-form/{id}', [SellerController::class, 'viewContactForm'])->name('seller.show_contact_form');
    Route::get('seller-incomplete-list', [SellerController::class, 'incompletelist'])->name('seller.incomplete');
    Route::get('seller-services/{userid}', [SellerController::class, 'sellerServices'])->name('seller.services');
    Route::get('credit-plans/{userid}', [SellerController::class, 'creditPlans'])->name('seller.creditPlans');
    Route::get('seller-bids/{userid}', [SellerController::class, 'sellerBids'])->name('seller.sellerBids');
    Route::get('seller-login/{userid}', [SellerController::class, 'sellerLogin'])->name('seller.sellerLogin');
    Route::get('seller-login-history-list', [SellerController::class, 'allLoginHistoryList'])->name('seller.allloginhistorylist');
    Route::get('/export-login-history-excel', [SellerController::class, 'exportLoginHistoryExcel'])->name('export.login.history.excel');
    Route::get('/export-login-history-csv', [SellerController::class, 'exportLoginHistoryCsv'])->name('export.login.history.csv');
    Route::get('seller-accreditations/{userid}', [SellerController::class, 'sellerAccreditations'])->name('seller.sellerAccreditations');
    Route::get('seller-profile-services/{userid}', [SellerController::class, 'sellerProfileServices'])->name('seller.sellerProfileServices');
    Route::get('/export-seller-excel', [SellerController::class, 'exportCompleteSellerExcel'])->name('export.com.seller.excel');
    Route::get('/export-com-seller-csv', [SellerController::class, 'exportCompleteSellerCsv'])->name('export.com.seller.csv');
    Route::get('/zoho/seller/send/{type}/{id}', [SellerController::class, 'sellerSendToZoho'])->name('zoho.seller.send');
    Route::get('suggested-questions/{userid}', [SellerController::class, 'suggestedQuestions'])->name('seller.suggestedQuestions');
    Route::resource('servicequestion', ServiceQuestionsController::class);
    Route::POST('servicequestion/add-more-option', [ServiceQuestionsController::class, 'addMoreOption']);
    Route::resource('profilequestion', ProfileQuesController::class);
    Route::resource('coupon', CouponController::class);

    Route::resource('request-list', RequestListController::class);
    Route::resource('email-settings', EmailSettingsController::class);
    Route::post('email-settings/change-status', [EmailSettingsController::class, 'changeSettingStatus'])->name('email-settings.change-status');

    Route::resource('admin-users', AdminUserController::class);
    Route::resource('roles', RoleController::class);


    Route::get('d7-lead-supplier', [d7LeadSupplierController::class, 'd7LeadSupplierList'])->name('d7LeadSupplierList');
    Route::get('/d7-lead-supplier-excel', [d7LeadSupplierController::class, 'exportd7LeadSupplierExcel'])->name('d7-lead-supplier.excel');
    Route::get('/d7-lead-supplier-csv', [d7LeadSupplierController::class, 'exportd7LeadSupplierCsv'])->name('d7-lead-supplier.csv');


    Route::get('purchase-invoice-history', [PurchaseInvoiceHistoryController::class, 'purchaseInvoiceHistoryList'])->name('purchase.invoice.history');
    Route::post('/admin-download-invoice', [PaymentController::class, 'downloadInvoice'])->name('plan.download-invoice');;

    Route::get('/zoho-import', [ZohoAccountImportController::class, 'importZoho'])->name('zoho.viewimport');
    Route::post('/zoho-import', [ZohoAccountImportController::class, 'importZohoAccounts'])->name('zoho.import');
});


Route::get('/facebook-webhook', function (Request $request) {
    return WhatsAppMessage::verifyWebhook($request);
});

Route::post('/facebook-webhook', function (Request $request) {
    return WhatsAppMessage::handleWebhook($request);
});


// Route::get('/google/login', [GoogleReviewController::class, 'redirectToGoogle'])->name('google.login');
//Route::get('/google/callback', [GoogleReviewController::class, 'handleGoogleCallback'])->name('google.callback');
//Route::get('/google/reviews', [GoogleReviewController::class, 'getReviews']);

Route::get('/test-next-day-expired-emails', [CronController::class, 'sendNextDayExpiredQuoteEmail']);

Route::get('/test-customer-lead-status', [CronController::class, 'sendLeadRequestStatusEmailToCustomer']);
Route::get('/test-customer-credit-below', [CronController::class, 'sendCreditBelowFiftyEmail']);
Route::get('/test-customer-abandoned', [CronController::class, 'sendAbandonedCartReminderEmails']);
Route::get('/test-customer-reminder', [CronController::class, 'sendNotifyCustomerRequestRepliesReminderEmail']);
Route::get('/test-customer-todaymiss', [CronController::class, 'missedTodaySecuredTodayLastChanceToBidAndSecure']);
Route::get('/test-notify-postcode', [CronController::class, 'sendnotifyCustomerNewProfessionalinPostcodeEmail']);




Route::get('/zoho/scopes', function () {
    $scope = ZohoHelper::getnewAccessTokenTest();
    return $scope;
});


Route::get('/zoho/authorize', [ZohoOAuthTestController::class, 'authorize']); // Step 1: open auth URL
Route::get('/zoho/callback', [ZohoOAuthTestController::class, 'callback']);   // Step 2: get code
Route::get('/zoho/access-token', [ZohoOAuthTestController::class, 'getAccessToken']);

Route::get('/zohowelcome-email', function () {
    $scope = ZohoEmails::sendWelcomeEmailTest('1158', 'zqQKYVz6');
    return $scope;
});
Route::get('/testd7-lead-buyer', function () {
    $d7Service = app(D7LeadFinderService::class);
    $d7Response = $d7Service->getSearchSuppliers();
});

Route::get('/test-zeptomail/{leadId}', [d7LeadSupplierController::class, 'testZeptoMail']);

Route::get('/test-d7-lead-suppliers', [D7LeadSupplierController::class, 'testIntegrateD7LeadSuppliers']);

Route::get('/test-d7-lead-account-suppliers', [D7LeadSupplierController::class, 'testIntegrateD7LeadAccountSuppliers']);

Route::get('/test/zoho-delete', [D7LeadSupplierController::class, 'testDeleteZohoRecord']);

Route::get('/test/zoho-account-delete', [D7LeadSupplierController::class, 'testDeleteZohoAccountRecord']);




require __DIR__ . '/auth.php';
