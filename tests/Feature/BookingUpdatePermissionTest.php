<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Tests\TestCase;

class BookingUpdatePermissionTest extends TestCase
{
    /** @dataProvider bookingRoutes */
    public function test_edit_and_save_use_the_existing_update_permission(string $routeName, bool $allowed): void
    {
        $route = Route::getRoutes()->getByName($routeName);
        $permissions = array_values(array_filter($route->gatherMiddleware(), fn ($middleware) =>
            is_string($middleware) && str_starts_with($middleware, 'permission:')
        ));
        $this->assertSame(['permission:bookings.update'], $permissions);

        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->once()->with('bookings.update')->andReturn($allowed);
        $this->actingAs($user, 'web');
        if (! $allowed) {
            $this->expectException(UnauthorizedException::class);
        }
        $response = app(PermissionMiddleware::class)->handle(
            Request::create('/bookings/236/edit'),
            fn () => response('Allowed'),
            substr($permissions[0], strlen('permission:')),
            'web'
        );
        $this->assertSame(200, $response->getStatusCode());
    }

    public static function bookingRoutes(): array
    {
        return [
            'edit allowed' => ['bookings.edit', true],
            'save allowed' => ['bookings.update', true],
            'edit denied' => ['bookings.edit', false],
            'save denied' => ['bookings.update', false],
        ];
    }
}
