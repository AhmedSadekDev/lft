<?php

use App\Http\Controllers\Api\Superagent\NotificationController;
use App\Http\Controllers\Api\Superagent\AgentController;
use App\Http\Controllers\Api\Superagent\Auth\ChangePasswordController;
use App\Http\Controllers\Api\Superagent\Auth\LoginController;
use App\Http\Controllers\Api\Superagent\Auth\OtpController;
use App\Http\Controllers\Api\Superagent\Auth\ProfileController;
use App\Http\Controllers\Api\Superagent\Auth\SetPasswordController;
use App\Http\Controllers\Api\Superagent\BookingContainerActionController;
use App\Http\Controllers\Api\Superagent\BookingContainerController;
use App\Http\Controllers\Api\Superagent\HomeController;
use App\Http\Controllers\Api\Superagent\ShippingAgentController;
use App\Http\Controllers\Api\Superagent\YardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::get('token_ininvalid', [ProfileController::class, 'token_ininvalid'])->name("token_ininvalid");

Route::group(['middleware' => 'localization'], function () {

    // Route::group(['middleware' => 'guest:superagent'], function () {

        //login

        Route::post('login', [LoginController::class, 'login']);

        //set password
        Route::post('set_password', [SetPasswordController::class, 'set_password']);

        Route::post('sendOtp', [OtpController::class, 'sendOtp']);
        Route::post('verifyOtp', [OtpController::class, 'verifyOtp']);
        Route::post('resetPassword', [OtpController::class, 'resetPassword']);
    // });
    Route::group(['middleware' => 'auth:superagent'], function () {

        //agent expensess-approve
        Route::post('container-step-approve', [AgentController::class, 'approve']);
        Route::get('containers-expenses', [AgentController::class, 'expenses']);

        //wallet
        Route::get('wallets', [\App\Http\Controllers\Api\Superagent\WalletController::class, 'index']);
        Route::post('charge-agents-wallet', [\App\Http\Controllers\Api\Superagent\WalletController::class, 'chargeAgentWallet']);

        //fetch_profile
        Route::get('fetch_profile', [ProfileController::class, 'fetch_profile']);

        //update_profile
        Route::post('update_profile', [ProfileController::class, 'update_profile']);

        //change_password
        Route::post('change_password', [ChangePasswordController::class, 'change_password']);

        //logout
        Route::get('logout', [LoginController::class, 'logout']);

        //home
        Route::get('fetch_home', [HomeController::class, 'fetch_home']);
        
        Route::post('change-container-status', [AgentController::class, 'changeStatus']);


        //booking_containers
        Route::group(['controller' => BookingContainerController::class], function () {

            Route::get("booking/specification", "specification");

            Route::get("booking/loading", "loading");

            Route::get("booking/unloading", "unloading");
        });

        //agents
        Route::group(['controller' => AgentController::class], function () {

            Route::post("booking/fetch_agents", "fetch_agents");

            Route::post("booking/assign_agents", "assign_agents");
            Route::post("booking/assign_specification_booking", "assign_specification_booking");
        });

        //booking container actions
        Route::group(['controller' => BookingContainerActionController::class], function () {

            Route::post("booking/done_specification", "done_specification");

            Route::post("booking/done_loading", "done_loading");


            Route::post("booking/done_unloading", "done_unloading");

            Route::post("booking/make_it_today", "make_it_today");
            Route::post("booking/make_specification_today", "make_specification_today");

            Route::post("booking/notes", "notes");
        });

        //agents
        Route::group(['controller' => ShippingAgentController::class], function () {

            Route::post("booking/save_specification_booking_yard", "save_specification_booking_yard");

            Route::get("booking/specification_assignments", "specification_assignments");

            Route::get("booking/unloading_assignments", "unloading_assignments");

            Route::get("booking/loading_assignments", "loading_assignments");

            Route::post("booking/save_loading_booking_container", "save_loading_booking_container");
        });

        Route::group(['controller' => NotificationController::class], function () {

            Route::post("fetch_your_notifications", "fetch_notifications");
            Route::post("fetch_agents_notifications", "fetch_agents_notifications");
        });
        Route::get('fetch_yards', [YardController::class, 'fetch_yards']);
    });
});
