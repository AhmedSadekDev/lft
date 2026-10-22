<?php

namespace Tests\Unit;

use App\Http\Resources\Api\Agent\ExpenseResource;
use App\Models\AgentExpense;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpenseResourceRegisteredAtTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('agent_expenses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agent_id')->nullable();
            $t->decimal('value', 14, 2)->default(0);
            $t->string('notes')->nullable();
            $t->integer('type')->default(2);
            $t->integer('type_id')->nullable();
            $t->integer('version')->default(1);
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->unsignedBigInteger('booking_container_id')->nullable();
            $t->string('request_key')->nullable();
            $t->string('image_agent_expenses')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->timestamps();
        });
    }

    public function test_exposes_date_time_and_registered_at_from_raw_created_at(): void
    {
        $expense = AgentExpense::create([
            'agent_id' => 1,
            'value' => 150,
            'notes' => 'نثريات',
            'type' => 2,
        ]);
        $expense->forceFill(['created_at' => '2026-10-22 14:35:12'])->save();
        $expense->refresh();

        // Model accessor still returns date-only.
        $this->assertSame('2026-10-22', $expense->created_at);

        $payload = (new ExpenseResource($expense))->toArray(Request::create('/'));

        $this->assertSame('2026-10-22', $payload['date']);
        $this->assertSame('14:35', $payload['time']);
        $this->assertSame('2026-10-22 14:35', $payload['registered_at']);
    }
}
