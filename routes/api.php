<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\SetPasswordController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ChooseUsController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OurServiceController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SponserController;
use App\Http\Controllers\Api\StaticPageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user', function (Request $request) {
    dd('test');
});

Route::group(['middleware' => 'localization'], function(){
    // ------------------ Dashboard ------------------
    Route::group(['namespace' => 'App\Http\Controllers\Api', 'prefix' => 'booking'], function() {
        Route::get('track', [BookingController::class, 'getBooking']);
        Route::get('container/{container}', [BookingController::class,'getContainerDetails']);
    });

    // ------------------ Profile ------------------
    Route::group(['namespace' => 'App\Http\Controllers\Api', 'prefix' => 'profile', 'middleware' => 'auth:api'], function() {
        Route::get('employees', [EmployeeController::class,'getEmployees']);
        Route::get('bookings', [BookingController::class,'getCompanyBookings']);
        Route::put('/update_information', [CompanyController::class,'update']);
    });

    // ------------------ Auth ------------------
    Route::post('login', [LoginController::class, 'login']);
    //Reset Password
    Route::post('forget-password', [PasswordResetController::class, 'forgetPassword']);
    Route::post('verify-otp', [PasswordResetController::class, 'verifyOtp']);
    Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
    Route::post('set-password', [SetPasswordController::class, 'setPassword']);
    Route::post('logout', [LoginController::class, 'logout']);

    // ------------------ Static-Pages ------------------
    Route::get('page/{key}', [StaticPageController::class, 'page'])->name('static.pages.index');

    // ------------------ Choose-Us ------------------
    Route::get('choose-us', [ChooseUsController::class, 'getChooses'])->name('choose.us');

    // ------------------ Reviews ------------------
    Route::get('/reviews', [ReviewController::class, 'getReviews'])->name('reviews');

    // ------------------ Our-Serivce ------------------
    Route::get('/our-services', [OurServiceController::class, 'getServices'])->name('our.services');

    // ------------------ Our-Serivce ------------------
    Route::get('/services', [OurServiceController::class, 'getServices']);

    // ------------------ Sponsers ------------------
    Route::get('sponsers', [SponserController::class, 'getSponsers'])->name('sponsers');

    // ------------------ Settings ------------------
    Route::get('settings', [SettingController::class, 'getSettings'])->name('settings');

    // ------------------ Contact Us ------------------
    Route::post('contact_us', [ContactUsController::class, 'store'])->name('contact_us');

});



