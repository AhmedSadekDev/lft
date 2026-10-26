<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Company;
use App\Models\Employee;
use App\Models\OTP;
use App\Models\Superagent;
use App\Services\PasswordResetAgentService;
use App\Services\PasswordResetService;
use App\Services\PasswordResetSuperagentService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PasswordResetTokenSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::disconnect('sqlite');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::reconnect('sqlite');

        foreach (['companies', 'employees', 'agents', 'superagents', 'otps'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('session_id')->nullable();
            $t->timestamps();
        });

        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('session_id')->nullable();
            $t->timestamps();
        });

        Schema::create('agents', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->timestamps();
        });

        Schema::create('superagents', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->timestamps();
        });

        Schema::create('otps', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('employee_id')->nullable();
            $t->unsignedBigInteger('agent_id')->nullable();
            $t->unsignedBigInteger('superagent_id')->nullable();
            $t->string('otp')->nullable();
            $t->timestamp('expire_at')->nullable();
            $t->string('reset_token_hash', 64)->nullable();
            $t->timestamp('reset_token_expires_at')->nullable();
            $t->timestamp('reset_token_used_at')->nullable();
            $t->timestamps();
        });
    }

    public function test_company_happy_path_issues_hashed_token_and_resets_once(): void
    {
        $company = Company::create([
            'name' => 'Co',
            'email' => 'company@example.com',
            'password' => 'old-secret',
        ]);

        OTP::create([
            'company_id' => $company->id,
            'otp' => '123456',
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);

        $service = app(PasswordResetService::class);
        $verified = $service->verifyOtp(Request::create('/', 'POST', [
            'email' => $company->email,
            'otp' => '123456',
        ]));

        $this->assertSame('company', $verified['account_type']);
        $this->assertNotEmpty($verified['reset_token']);
        $this->assertSame(64, strlen($verified['reset_token']));

        $otp = OTP::where('company_id', $company->id)->first();
        $this->assertSame(hash('sha256', $verified['reset_token']), $otp->reset_token_hash);
        $this->assertNull($otp->reset_token_used_at);
        $this->assertStringNotContainsString($verified['reset_token'], (string) $otp->reset_token_hash);

        $service->resetPassword([
            'email' => $company->email,
            'password' => 'new-secret',
            'reset_token' => $verified['reset_token'],
        ]);

        $company->refresh();
        $this->assertTrue(Hash::check('new-secret', $company->password));
        $this->assertFalse(Hash::check('old-secret', $company->password));
        $this->assertNotNull($otp->fresh()->reset_token_used_at);
    }

    public function test_legacy_email_password_only_reset_is_rejected(): void
    {
        $company = Company::create([
            'name' => 'Co',
            'email' => 'legacy@example.com',
            'password' => 'old-secret',
        ]);

        $this->expectException(HttpException::class);

        app(PasswordResetService::class)->resetPassword([
            'email' => $company->email,
            'password' => 'hijacked-password',
        ]);
    }

    public function test_wrong_token_is_rejected(): void
    {
        $company = Company::create([
            'name' => 'Co',
            'email' => 'wrong@example.com',
            'password' => 'old-secret',
        ]);

        OTP::create([
            'company_id' => $company->id,
            'otp' => '111111',
            'expire_at' => Carbon::now()->addMinutes(10),
            'reset_token_hash' => hash('sha256', str_repeat('a', 64)),
            'reset_token_expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $this->expectException(HttpException::class);

        app(PasswordResetService::class)->resetPassword([
            'email' => $company->email,
            'password' => 'new-secret',
            'reset_token' => str_repeat('b', 64),
        ]);
    }

    public function test_expired_reset_token_is_rejected(): void
    {
        $company = Company::create([
            'name' => 'Co',
            'email' => 'expired@example.com',
            'password' => 'old-secret',
        ]);

        $plain = bin2hex(random_bytes(32));
        OTP::create([
            'company_id' => $company->id,
            'otp' => '222222',
            'expire_at' => Carbon::now()->subMinute(),
            'reset_token_hash' => hash('sha256', $plain),
            'reset_token_expires_at' => Carbon::now()->subMinute(),
        ]);

        $this->expectException(HttpException::class);

        app(PasswordResetService::class)->resetPassword([
            'email' => $company->email,
            'password' => 'new-secret',
            'reset_token' => $plain,
        ]);
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        $company = Company::create([
            'name' => 'Co',
            'email' => 'reuse@example.com',
            'password' => 'old-secret',
        ]);

        OTP::create([
            'company_id' => $company->id,
            'otp' => '333333',
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);

        $service = app(PasswordResetService::class);
        $verified = $service->verifyOtp(Request::create('/', 'POST', [
            'email' => $company->email,
            'otp' => '333333',
        ]));

        $service->resetPassword([
            'email' => $company->email,
            'password' => 'new-secret-1',
            'reset_token' => $verified['reset_token'],
        ]);

        $this->expectException(HttpException::class);

        $service->resetPassword([
            'email' => $company->email,
            'password' => 'new-secret-2',
            'reset_token' => $verified['reset_token'],
        ]);
    }

    public function test_company_email_wins_over_employee_with_same_email(): void
    {
        $shared = 'shared@example.com';

        $company = Company::create([
            'name' => 'Co',
            'email' => $shared,
            'password' => 'company-old',
        ]);

        $employee = Employee::create([
            'company_id' => $company->id,
            'name' => 'Emp',
            'email' => $shared,
            'password' => 'employee-old',
        ]);

        OTP::create([
            'company_id' => $company->id,
            'otp' => '444444',
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);

        $service = app(PasswordResetService::class);
        $verified = $service->verifyOtp(Request::create('/', 'POST', [
            'email' => $shared,
            'otp' => '444444',
        ]));

        $this->assertSame('company', $verified['account_type']);

        $service->resetPassword([
            'email' => $shared,
            'password' => 'company-new',
            'reset_token' => $verified['reset_token'],
        ]);

        $this->assertTrue(Hash::check('company-new', $company->fresh()->password));
        $this->assertTrue(Hash::check('employee-old', $employee->fresh()->password));
    }

    public function test_employee_reset_binds_to_employee_account(): void
    {
        $employee = Employee::create([
            'company_id' => 1,
            'name' => 'Emp',
            'email' => 'employee-only@example.com',
            'password' => 'employee-old',
        ]);

        OTP::create([
            'employee_id' => $employee->id,
            'otp' => '555555',
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);

        $service = app(PasswordResetService::class);
        $verified = $service->verifyOtp(Request::create('/', 'POST', [
            'email' => $employee->email,
            'otp' => '555555',
        ]));

        $this->assertSame('employee', $verified['account_type']);

        $service->resetPassword([
            'email' => $employee->email,
            'password' => 'employee-new',
            'reset_token' => $verified['reset_token'],
        ]);

        $this->assertTrue(Hash::check('employee-new', $employee->fresh()->password));
    }

    public function test_agent_and_superagent_reset_after_otp_without_reset_token(): void
    {
        $agent = Agent::create([
            'name' => 'Agent',
            'email' => 'agent@example.com',
            'password' => 'agent-old',
        ]);
        OTP::create([
            'agent_id' => $agent->id,
            'otp' => '666666',
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);

        $agentService = app(PasswordResetAgentService::class);
        $agentVerified = $agentService->verifyOtp(Request::create('/', 'POST', [
            'email' => $agent->email,
            'otp' => '666666',
        ]));
        $this->assertSame('agent', $agentVerified['account_type']);
        $this->assertArrayNotHasKey('reset_token', $agentVerified);
        $this->assertNotNull(OTP::where('agent_id', $agent->id)->first()->reset_token_hash);

        $agentService->resetPassword([
            'email' => $agent->email,
            'password' => 'agent-new',
        ]);
        $this->assertTrue(Hash::check('agent-new', $agent->fresh()->password));
        $this->assertNotNull(OTP::where('agent_id', $agent->id)->first()->reset_token_used_at);

        $superagent = Superagent::create([
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => 'super-old',
        ]);
        OTP::create([
            'superagent_id' => $superagent->id,
            'otp' => '777777',
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);

        $superService = app(PasswordResetSuperagentService::class);
        $superVerified = $superService->verifyOtp(Request::create('/', 'POST', [
            'email' => $superagent->email,
            'otp' => '777777',
        ]));
        $this->assertSame('superagent', $superVerified['account_type']);
        $this->assertArrayNotHasKey('reset_token', $superVerified);

        $superService->resetPassword([
            'email' => $superagent->email,
            'password' => 'super-new',
        ]);
        $this->assertTrue(Hash::check('super-new', $superagent->fresh()->password));
    }

    public function test_agent_reset_without_prior_otp_verify_is_rejected(): void
    {
        $agent = Agent::create([
            'name' => 'Owner',
            'email' => 'owner-agent@example.com',
            'password' => 'owner-old',
        ]);

        OTP::create([
            'agent_id' => $agent->id,
            'otp' => '888888',
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);

        $service = app(PasswordResetAgentService::class);

        $this->expectException(HttpException::class);

        $service->resetPassword([
            'email' => $agent->email,
            'password' => 'hijack-password',
        ]);
    }

    public function test_agent_verified_session_cannot_reset_another_agent(): void
    {
        $owner = Agent::create([
            'name' => 'Owner',
            'email' => 'owner-agent@example.com',
            'password' => 'owner-old',
        ]);
        $victim = Agent::create([
            'name' => 'Victim',
            'email' => 'victim-agent@example.com',
            'password' => 'victim-old',
        ]);

        OTP::create([
            'agent_id' => $owner->id,
            'otp' => '888888',
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);

        $service = app(PasswordResetAgentService::class);
        $service->verifyOtp(Request::create('/', 'POST', [
            'email' => $owner->email,
            'otp' => '888888',
        ]));

        $this->expectException(HttpException::class);

        $service->resetPassword([
            'email' => $victim->email,
            'password' => 'victim-hijack',
        ]);
    }

    public function test_password_is_not_double_hashed(): void
    {
        $company = Company::create([
            'name' => 'Co',
            'email' => 'hash@example.com',
            'password' => 'old-secret',
        ]);

        OTP::create([
            'company_id' => $company->id,
            'otp' => '999999',
            'expire_at' => Carbon::now()->addMinutes(10),
        ]);

        $service = app(PasswordResetService::class);
        $verified = $service->verifyOtp(Request::create('/', 'POST', [
            'email' => $company->email,
            'otp' => '999999',
        ]));

        $service->resetPassword([
            'email' => $company->email,
            'password' => 'plain-new-password',
            'reset_token' => $verified['reset_token'],
        ]);

        $stored = $company->fresh()->password;
        // Mutator hashes once: plain password must verify directly against the stored hash.
        $this->assertTrue(Hash::check('plain-new-password', $stored));
        $this->assertTrue(str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$'));
    }

    public function test_http_company_reset_requires_reset_token(): void
    {
        Company::create([
            'name' => 'Co',
            'email' => 'http-reset@example.com',
            'password' => 'old-secret',
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'http-reset@example.com',
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ]);

        $response->assertStatus(422);
        $this->assertFalse(
            Hash::check('new-secret', Company::where('email', 'http-reset@example.com')->first()->password)
        );
    }
}
