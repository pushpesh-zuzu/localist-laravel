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
    Route::resource('seller', SellerController::class);
    Route::get('seller-complete-list', [SellerController::class, 'index'])->name('seller.complete');
    Route::get('seller-incomplete-list', [SellerController::class, 'incompletelist'])->name('seller.incomplete');
    Route::resource('servicequestion', ServiceQuestionsController::class);

});

require __DIR__.'/auth.php';
