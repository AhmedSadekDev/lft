<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\UsdVault;
use App\Models\UsdVaultTransaction;
use App\Services\UsdVaultService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsdVaultAndFollowUpTest extends TestCase
{
    public function test_usd_vault_deposit_and_agent_custody_transfer(): void
    {
        if (! Schema::hasTable('usd_vaults') || ! Schema::hasColumn('agents', 'wallet_usd')) {
            $this->markTestSkipped('USD wallet SQL not applied in this environment');
        }

        $vault = UsdVault::query()->first() ?? UsdVault::create([
            'name' => 'خزنة الدولار',
            'opening_balance' => 0,
            'balance' => 0,
            'is_active' => true,
        ]);
        $vault->update(['balance' => 0]);

        $agent = Agent::query()->create([
            'name' => 'USD Test Agent',
            'email' => 'usd-test-'.uniqid('', true).'@example.com',
            'phone' => '01000000000',
            'password' => 'password',
            'wallet' => 1000,
            'wallet_usd' => 0,
        ]);

        $service = app(UsdVaultService::class);
        $service->deposit(500, 'test deposit', null, null);
        $vault->refresh();
        $this->assertEquals(500.0, (float) $vault->balance);

        $service->transferToAgent(200, $agent->id, 'test custody', null);
        $vault->refresh();
        $agent->refresh();

        $this->assertEquals(300.0, (float) $vault->balance);
        $this->assertEquals(200.0, (float) $agent->wallet_usd);
        $this->assertEquals(1000.0, (float) $agent->wallet);

        $this->assertDatabaseHas('usd_vault_transactions', [
            'category' => UsdVaultTransaction::CAT_AGENT_CUSTODY,
            'direction' => UsdVaultTransaction::DIR_OUT,
            'agent_id' => $agent->id,
        ]);
    }
}
