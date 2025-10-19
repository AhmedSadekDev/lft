<?php

use App\Http\Controllers\Api\Desktop\Auth\LoginController;
use App\Http\Controllers\Api\Desktop\Orders\CompanyController;
use App\Http\Controllers\Api\Desktop\Orders\OrderController;
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

Route::group(['middleware' => 'localization'], function () {

    // ------------------ Auth ------------------
    Route::post('login', [LoginController::class, 'login']);
    Route::group(['middleware' => 'auth:desktop'], function () {
        Route::post('logout', [LoginController::class, 'logout']);
        Route::group(['prefix' => 'orders'], function () {
            Route::get('all', [OrderController::class, 'all']);
            Route::post('invoices', [OrderController::class, 'invoices']);
            Route::post('submitInvoices', [OrderController::class, 'submitInvoices']);
            Route::post('updateStatus', [OrderController::class, 'updateStatus']);
        });
        Route::group(['prefix' => 'companies'], function () {
            Route::get('all', [CompanyController::class, 'all']);
        });
    });

});
