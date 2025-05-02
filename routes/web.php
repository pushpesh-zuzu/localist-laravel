<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\BlogsController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceQuestionsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileQuesController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\RequestListController;



Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('config:cache');
    $exitCode = Artisan::call('view:clear');
	$exitCode = Artisan::call('optimize');
    return 'DONE'; //Return anything
});




Route::get('/install-api', function() {
    $exitCode = Artisan::call('install:api');

    return 'DONE'; //Return anything
});

Route::get('/check-mail', function() {
    $dataUser['email'] = 'pushpesh47@gmail.com';
    $dataUser['name'] = 'Pushpesh';
    $dataUser['service'] = 'Web Development';
    $dataUser['password'] = '12345678';
    $dataUser['otp'] = '1234';
    Mail::send('emails.buyer_registration', $dataUser, function ($message) use ($dataUser) {
            $message->to($dataUser['email']);
            $message->subject("Welcome to Localist " .$dataUser['name'] ."!");
        });

    return 'DONE'; //Return anything
});


// Route::get('/', function () {
//     return view('dashboard');
// })->middleware(['auth:admin', 'verified'])->name('dashboard');
// Route::get('/', [DashboardController::class, 'index'])->middleware(['auth:admin', 'verified'])->name('dashboard');
Route::middleware('auth:admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->middleware(['auth:admin'])->name('dashboard');
    Route::get('/users/{type?}', [UserController::class, 'index'])->name('user.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('categories', CategoryController::class);   

    Route::resource('subcategories', SubCategoryController::class);
    Route::resource('blogs', BlogsController::class);
    Route::resource('plans', PlansController::class);
    Route::resource('buyer', BuyerController::class);
    Route::get('buyer-lead-details/{leadid}', [BuyerController::class, 'leadDetails'])->name('buyer.leadDetails');
    Route::get('buyer-bids/{userid}', [BuyerController::class, 'buyerBids'])->name('buyer.buyerBids');
    Route::get('buyer-login/{userid}', [BuyerController::class, 'buyerLogin'])->name('buyer.buyerLogin');
    Route::resource('seller', SellerController::class);
    Route::get('seller-complete-list', [SellerController::class, 'index'])->name('seller.complete');
    Route::get('seller-incomplete-list', [SellerController::class, 'incompletelist'])->name('seller.incomplete');
    Route::get('seller-services/{userid}', [SellerController::class, 'sellerServices'])->name('seller.services');
    Route::get('credit-plans/{userid}', [SellerController::class, 'creditPlans'])->name('seller.creditPlans');
    Route::get('seller-bids/{userid}', [SellerController::class, 'sellerBids'])->name('seller.sellerBids');
    Route::get('seller-login/{userid}', [SellerController::class, 'sellerLogin'])->name('seller.sellerLogin');
    Route::get('seller-accreditations/{userid}', [SellerController::class, 'sellerAccreditations'])->name('seller.sellerAccreditations');
    Route::get('seller-profile-services/{userid}', [SellerController::class, 'sellerProfileServices'])->name('seller.sellerProfileServices');
    Route::get('suggested-questions/{userid}', [SellerController::class, 'suggestedQuestions'])->name('seller.suggestedQuestions');
    Route::resource('servicequestion', ServiceQuestionsController::class);
    Route::resource('profilequestion', ProfileQuesController::class);
    Route::resource('coupon', CouponController::class);

    Route::resource('request-list', RequestListController::class);

});

require __DIR__.'/auth.php';
