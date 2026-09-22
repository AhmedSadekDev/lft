<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('address')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    private function user(string $email = 'owner@example.test'): User
    {
        return User::create(['name' => 'Owner', 'email' => $email, 'password' => 'old-password']);
    }

    public function test_guests_cannot_access_or_update_profile(): void
    {
        $this->get('/dashboard/profile')->assertRedirect('/login');
        $this->put('/dashboard/profile', [])->assertRedirect('/login');
        $this->put('/dashboard/profile/password', [])->assertRedirect('/login');
    }

    public function test_profile_updates_only_authenticated_user_and_allowed_fields(): void
    {
        $user = $this->user();
        $other = $this->user('other@example.test');
        $this->actingAs($user)->put('/dashboard/profile', [
            'name' => 'Updated', 'email' => $user->email, 'phone' => '01234567890',
            'address' => 'Cairo', 'id' => $other->id, 'password' => 'injected-password', 'role' => 1,
        ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();
        $this->assertSame('Updated', $user->fresh()->name);
        $this->assertSame('Owner', $other->fresh()->name);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $user = $this->user();
        $other = $this->user('other@example.test');
        $this->actingAs($user)->put('/dashboard/profile', [
            'name' => 'Updated', 'email' => $other->email,
        ])->assertSessionHasErrors('email');
        $this->assertSame('Owner', $user->fresh()->name);
    }

    public function test_password_change_is_hashed_and_keeps_user_authenticated(): void
    {
        $user = $this->user();
        $this->actingAs($user)->put('/dashboard/profile/password', [
            'current_password' => 'old-password', 'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('old-password', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
    }

    /** @dataProvider invalidPasswords */
    public function test_invalid_password_changes_are_rejected(string $current, string $password, string $confirmation, string $error): void
    {
        $user = $this->user();
        $this->actingAs($user)->put('/dashboard/profile/password', [
            'current_password' => $current, 'password' => $password, 'password_confirmation' => $confirmation,
        ])->assertSessionHasErrors($error);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public static function invalidPasswords(): array
    {
        return [
            ['wrong-password', 'new-password', 'new-password', 'current_password'],
            ['old-password', 'new-password', 'different-password', 'password'],
            ['old-password', 'short', 'short', 'password'],
        ];
    }
}
