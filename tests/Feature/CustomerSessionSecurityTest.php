<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomerSessionSecurityTest extends ProfileEmployeesAuthorizationTest
{
    public function test_password_changes_revoke_all_tokens_for_both_account_types(): void
    {
        \Illuminate\Database\Eloquent\Model::reguard();
        foreach ([Company::class, Employee::class] as $model) {
            $user = $model::create(['name' => 'Session fixture', 'password' => 'Before-password-123']);
            $first = JWTAuth::fromUser($user);
            $second = JWTAuth::fromUser($user);
            $this->requestWithToken($first, '/api/profile/bookings')->assertOk();
            $this->requestWithToken($second, '/api/profile/bookings')->assertOk();
            auth()->forgetGuards();
            app('tymon.jwt')->unsetToken();
            JWTAuth::unsetToken();
            $this->withToken($first)->putJson('/api/profile/update_information', [
                'password' => 'After-password-456', 'password_confirmation' => 'After-password-456',
            ])->assertOk()->assertJson(['status' => true]);
            $this->requestWithToken($first, '/api/profile/bookings')->assertUnauthorized();
            $this->requestWithToken($second, '/api/profile/bookings')->assertUnauthorized();
            $this->requestWithToken(JWTAuth::fromUser($user->fresh()), '/api/profile/bookings')->assertOk();
        }
    }

    public function test_logout_revokes_only_current_token_for_both_account_types(): void
    {
        foreach ([Company::class, Employee::class] as $model) {
            $user = $model::create(['name' => 'Session fixture', 'password' => 'Before-password-123']);
            $first = JWTAuth::fromUser($user);
            $second = JWTAuth::fromUser($user);
            $this->requestWithToken($first, '/api/logout', 'POST')->assertOk();
            $this->requestWithToken($first, '/api/profile/bookings')->assertUnauthorized();
            $this->requestWithToken($second, '/api/profile/bookings')->assertOk();
        }
    }

    public function test_password_invitation_is_single_use_and_rejects_logged_out_tokens(): void
    {
        foreach ([Company::class, Employee::class] as $model) {
            $user = $model::create(['name' => 'Invite fixture', 'password' => 'Initial-password']);
            $token = JWTAuth::fromUser($user);
            $user->update(['session_id' => $token]);
            $service = app(\App\Services\CustomerPasswordInvitation::class);
            $service->setPassword($model, $token, 'New-password-123');
            $this->assertNull($user->fresh()->session_id);
            try {
                $service->setPassword($model, $token, 'Replay-password');
                $this->fail('Consumed invitation accepted');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
            $token = JWTAuth::fromUser($user->fresh());
            $user->update(['session_id' => $token]);
            $this->requestWithToken($token, '/api/logout', 'POST')->assertOk();
            try {
                $service->setPassword($model, $token, 'Revoked-password');
                $this->fail('Revoked invitation accepted');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }

    public function test_booking_second_page_keeps_company_scope(): void
    {
        $company = Company::create(['name' => 'Paging fixture']);
        for ($i = 1; $i <= 21; $i++) {
            \App\Models\Booking::create(['company_id' => $company->id, 'booking_number' => 'PAGE-'.$i]);
        }
        \App\Models\Booking::create(['company_id' => $company->id + 1, 'booking_number' => 'OTHER']);
        $this->requestWithToken(JWTAuth::fromUser($company), '/api/profile/bookings?page=2&per_page=20')
            ->assertOk()->assertJsonPath('pagination.total', 21)->assertJsonPath('pagination.current_page', 2)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.Booking_number', 'PAGE-1');
    }

    public function test_tokens_without_password_binding_are_rejected(): void
    {
        $company = Company::create(['name' => 'Legacy fixture']);
        $jwt = app('tymon.jwt');
        $payload = $jwt->factory()->customClaims([
            'sub' => $company->id, 'prv' => sha1(Company::class),
        ])->make();
        $token = (string) $jwt->manager()->encode($payload);
        $this->requestWithToken($token, '/api/profile/bookings')->assertUnauthorized();
    }

    private function requestWithToken(string $token, string $url, string $method = 'GET')
    {
        auth()->forgetGuards();
        app('tymon.jwt')->unsetToken();
        JWTAuth::unsetToken();
        return $this->withToken($token)->json($method, $url);
    }
}
