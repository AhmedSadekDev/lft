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
        // تفعيل Telescope في بيئة local فقط، وفقط إذا كانت الحزمة مثبّتة
        // (يمنع انهيار الموقع على السيرفر عند تشغيل composer install --no-dev)
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
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
