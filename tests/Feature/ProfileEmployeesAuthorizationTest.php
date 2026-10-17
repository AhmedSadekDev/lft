<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ROLE-01: GET /api/profile/employees — company allowed, employee denied, guest unauthenticated.
 */
class ProfileEmployeesAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        auth()->forgetGuards();

        $ref = new \ReflectionProperty(\Illuminate\Database\Eloquent\Model::class, 'guardableColumns');
        $ref->setAccessible(true);
        $ref->setValue(null, []);
        \Illuminate\Database\Eloquent\Model::unguard();

        DB::disconnect('sqlite');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::reconnect('sqlite');
        Schema::dropAllTables();

        Schema::create('companies', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('Company');
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('session_id')->nullable();
            $t->timestamps();
        });

        Schema::create('employees', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('company_id')->nullable();
            $t->string('name')->default('Employee');
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('session_id')->nullable();
            $t->timestamps();
        });

        Schema::create('bookings', function (Blueprint $t) {
            $t->increments('id');
            $t->string('booking_number')->default('BK-001');
            $t->unsignedInteger('company_id')->nullable();
            $t->unsignedInteger('employee_id')->nullable();
            $t->unsignedInteger('shipping_agent_id')->nullable();
            $t->string('certificate_number')->nullable();
            $t->string('type_of_action')->nullable();
            $t->date('discharge_date')->nullable();
            $t->date('permit_end_date')->nullable();
            $t->timestamps();
        });

        Schema::create('booking_containers', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_id');
            $t->unsignedInteger('container_id')->nullable();
            $t->unsignedInteger('branch_id')->nullable();
            $t->timestamps();
        });

        Schema::create('booking_movements', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('booking_id');
            $t->string('status')->nullable();
            $t->timestamps();
        });

        Schema::create('shipping_agents', function (Blueprint $t) {
            $t->increments('id');
            $t->string('title')->nullable();
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        auth()->forgetGuards();
        \Illuminate\Database\Eloquent\Model::reguard();
        Schema::dropAllTables();
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    public function test_guest_cannot_list_employees(): void
    {
        $response = $this->getJson('/api/profile/employees');

        $response->assertStatus(401);
        $this->assertArrayNotHasKey('data', $response->json() ?? []);
    }

    public function test_company_can_list_own_employees(): void
    {
        $company = Company::create(['name' => 'Acme', 'email' => 'acme@example.test']);
        $own = Employee::create([
            'company_id' => $company->id,
            'name' => 'Own Staff',
            'email' => 'staff@example.test',
        ]);
        $otherCompany = Company::create(['name' => 'Other', 'email' => 'other@example.test']);
        Employee::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Staff',
            'email' => 'other-staff@example.test',
        ]);

        $this->withToken(\Tymon\JWTAuth\Facades\JWTAuth::fromUser($company));

        $response = $this->getJson('/api/profile/employees');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'errNum' => '0000',
            ]);

        $data = $response->json('data');
        $this->assertIsIterable($data);
        $ids = collect($data)->pluck('id')->all();
        $this->assertContains($own->id, $ids);
        $this->assertCount(1, $ids);
        $this->assertNotContains(
            Employee::where('email', 'other-staff@example.test')->value('id'),
            $ids
        );
    }

    public function test_employee_is_forbidden_from_listing_employees(): void
    {
        $company = Company::create(['name' => 'Acme', 'email' => 'acme@example.test']);
        Employee::create([
            'company_id' => $company->id,
            'name' => 'Colleague',
            'email' => 'colleague@example.test',
        ]);
        $employee = Employee::create([
            'company_id' => $company->id,
            'name' => 'Caller',
            'email' => 'caller@example.test',
        ]);

        $this->withToken(\Tymon\JWTAuth\Facades\JWTAuth::fromUser($employee));

        $response = $this->getJson('/api/profile/employees');

        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'errNum' => 403,
            ]);

        $json = $response->json();
        $this->assertArrayNotHasKey('data', $json);
        $this->assertNotEmpty($json['message'] ?? null);
        $this->assertStringNotContainsStringIgnoringCase('Colleague', (string) ($json['message'] ?? ''));
        $this->assertStringNotContainsString('colleague@example.test', json_encode($json));
    }

    public function test_employee_bookings_endpoint_unchanged(): void
    {
        $company = Company::create(['name' => 'Acme', 'email' => 'acme@example.test']);
        $employee = Employee::create([
            'company_id' => $company->id,
            'name' => 'Caller',
            'email' => 'caller@example.test',
        ]);
        Booking::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'booking_number' => 'BK-EMP-1',
        ]);
        Booking::create([
            'company_id' => $company->id,
            'employee_id' => null,
            'booking_number' => 'BK-CO-1',
        ]);

        $this->withToken(\Tymon\JWTAuth\Facades\JWTAuth::fromUser($employee));

        $response = $this->getJson('/api/profile/bookings');

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $numbers = collect($response->json('data'))->pluck('Booking_number')->all();
        $this->assertContains('BK-EMP-1', $numbers);
        $this->assertNotContains('BK-CO-1', $numbers);
    }
}
