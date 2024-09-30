<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\BookingContainer;
use App\Observers\BookingContainerObserver;
use App\Observers\BookingObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('layouts.admin', function ($view) {
            $view->with('current_locale', app()->getLocale());
            $view->with('available_locales', config('app.available_locales'));
        });

        Booking::observe(BookingObserver::class);
        BookingContainer::observe(BookingContainerObserver::class);

    }
}
