<?php

use App\Http\Controllers\Api\Agent\Auth\ChangePasswordController;
use App\Http\Controllers\Api\Agent\Auth\LoginController;
use App\Http\Controllers\Api\Agent\Auth\OtpController;
use App\Http\Controllers\Api\Agent\Auth\ProfileController;
use App\Http\Controllers\Api\Agent\Auth\SetPasswordController;
use App\Http\Controllers\Api\Agent\CarController;
use App\Http\Controllers\Api\Agent\CarExpenseController;
use App\Http\Controllers\Api\Agent\DeliveryPolicyController;
use App\Http\Controllers\Api\Agent\DriverController;
use App\Http\Controllers\Api\Agent\ExpenseController;
use App\Http\Controllers\Api\Agent\NotificationController;
use App\Http\Controllers\Api\Agent\ServiceCategoryController;
use App\Http\Controllers\Api\Agent\TransferAgentController;
use App\Http\Controllers\Api\Agent\BookingContainerActionController;
use App\Http\Controllers\Api\Agent\BookingContainerAssignmentController;
use App\Http\Controllers\Api\Agent\BookingPaperController;
use App\Http\Controllers\Api\Agent\CityController;
use App\Http\Controllers\Api\Agent\YardController;
use Illuminate\Support\Facades\Route;




Route::group(['middleware' => 'localization'], function () {



    // Route::group(['middleware' => 'guest:agent'], function () {

        //login

        Route::post('login', [LoginController::class, 'login']);
    // });

    //set password
    Route::post('set_password', [SetPasswordController::class, 'set_password']);

    //OTP
    Route::post('sendOtp', [OtpController::class, 'sendOtp']);
    Route::post('verifyOtp', [OtpController::class, 'verifyOtp']);
    Route::post('resetPassword', [OtpController::class, 'resetPassword']);

    Route::group(['middleware' => 'auth:agent'], function () {



        //wallet
        Route::get('wallets', [\App\Http\Controllers\Api\Agent\WalletController::class, 'index']);
        Route::post('charge-cars-wallet', [\App\Http\Controllers\Api\Agent\WalletController::class, 'chargeCarWallet']);


        //fetch_profile
        Route::get('fetch_profile', [ProfileController::class, 'fetch_profile']);

        //update_profile
        Route::post('update_profile', [ProfileController::class, 'update_profile']);


        Route::post('change_image', [ProfileController::class, 'change_image']);

        Route::get('cities', [CityController::class, 'index']);
        Route::get('containers-with-no-policies', [BookingContainerActionController::class, 'getContainers']);


        //change_password
        Route::post('change_password', [ChangePasswordController::class, 'change_password']);

        //fetch_financial_custody
        Route::get('fetch_financial_custody', [ExpenseController::class, 'fetch_financial_custody']);

        //make_general_expenses
        Route::post('make_general_expenses', [ExpenseController::class, 'make_general_expenses']);

        //fetch_all_expenses
        Route::any('fetch_all_expenses', [ExpenseController::class, 'fetch_all_expenses']);

        //fetch_latest_expenses
        Route::get('fetch_latest_expenses', [ExpenseController::class, 'fetch_latest_expenses']);




        //make_reservation_expenses
        Route::post('make_reservation_expenses', [ExpenseController::class, 'make_reservation_expenses']);


        //fetch_service_categories
        Route::get('fetch_service_categories', [ServiceCategoryController::class, 'fetch_service_categories']);

        //fetch_agents
        Route::post('fetch_agents', [TransferAgentController::class, 'fetch_agents']);

        //transfer_to_agent
        Route::post('transfer_to_agent', [TransferAgentController::class, 'transfer_to_agent']);

        //create_delivery_policy
        Route::post('create_delivery_policy', [DeliveryPolicyController::class, 'create_delivery_policy']);
        //fetch_delivery_policies
        Route::get('fetch_delivery_policies', [DeliveryPolicyController::class, 'fetch_delivery_policies']);
        //delivery_policy_details
        Route::post('delivery_policy_details', [DeliveryPolicyController::class, 'delivery_policy_details']);
        //delivery_policy_expenses
        Route::post('delivery_policy_expenses', [DeliveryPolicyController::class, 'delivery_policy_expenses']);
        //settle_delivery_policy
        Route::post('settle_delivery_policy', [DeliveryPolicyController::class, 'settle_delivery_policy']);


        //make_car_expenses
        Route::post('make_car_expenses', [CarExpenseController::class, 'make_car_expenses']);


        Route::group(['controller' => NotificationController::class], function () {

            Route::post("fetch_your_notifications", "fetch_notifications");
        });


        //logout
        Route::get('logout', [LoginController::class, 'logout']);
    });

    //fetch_drivers
    Route::get('fetch_drivers', [DriverController::class, 'fetch_drivers']);

    //fetch_cars
    Route::get('fetch_cars', [CarController::class, 'fetch_cars']);

    //booking container actions
    Route::group(['controller' => BookingContainerActionController::class], function () {

        Route::post("booking/done_specification", "done_specification");

        Route::post("booking/done_loading", "done_loading");


        Route::post("booking/done_unloading", "done_unloading");

        Route::post("booking/send_notes", "send_notes");
    });

    //fetch today assigments of the agent
    Route::group([
        'controller' => BookingContainerAssignmentController::class,
        'middleware' => [
            'api',
            'auth:agent'
        ]
    ], function () {

        Route::get("booking/fetch_loading_assignments", "fetch_loading_assignments");

        Route::get("booking/fetch_specification_assignments", "fetch_specification_assignments");


        Route::get("booking/fetch_unloading_assignments", "fetch_unloading_assignments");

        Route::post("booking/fetch_bookings", "fetch_bookings");

        Route::get("booking/fetch_booking_containers", "fetch_booking_containers");
        Route::get("booking/fetch_home_statistics", "fetch_home_statistics");
    });

    //save booking papers
    Route::group(['controller' => BookingPaperController::class], function () {

        Route::post("booking/save_specification_booking_yard", "save_specification_booking_yard");
        Route::post("booking/save_loading_booking_container", "save_loading_booking_container");
        Route::post("booking/save_unloading_booking_sail", "save_unloading_booking_sail");
        Route::post("booking/send_car_papers", "send_car_papers");
    });

    //fetch_yards
    Route::get('fetch_yards', [YardController::class, 'fetch_yards']);
});
