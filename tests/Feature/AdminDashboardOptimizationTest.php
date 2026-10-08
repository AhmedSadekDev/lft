<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashbaordController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminDashboardOptimizationTest extends TestCase
{
    public function test_booking_controller_middleware_permissions(): void
    {
        $controller = new \ReflectionClass(BookingController::class);
        $this->assertTrue($controller->hasMethod('index'));

        $routes = Route::getRoutes();
        $indexRoute = $routes->getByName('bookings.index');
        $this->assertNotNull($indexRoute, 'Route bookings.index should exist');

        $middlewares = $indexRoute->gatherMiddleware();
        $this->assertContains('auth', $middlewares);
    }

    public function test_dashboard_controller_is_invokable_and_mapped(): void
    {
        $controller = new \ReflectionClass(DashbaordController::class);
        $this->assertTrue($controller->hasMethod('__invoke'));

        $routes = Route::getRoutes();
        $homeRoute = $routes->getByName('home');
        $this->assertNotNull($homeRoute, 'Admin dashboard home route should exist');
    }
}
