<?php

namespace Tests\Feature;

use App\Models\BankTrnsaction;
use App\Services\BankTransactionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BankTransactionServiceTest extends TestCase
{
    private const AT = '2026-10-01 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        // Isolated schema: never migrate, truncate, or connect to the project's real database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('banks', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('amount')->nullable();
            $t->timestamps();
        });
        Schema::create('vaults', function (Blueprint $t) {
            $t->id();
            $t->string('amount')->nullable();
            $t->timestamps();
        });
        Schema::create('vault_transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agient_id')->nullable();
            $t->unsignedBigInteger('bank_id')->nullable();
            $t->string('name')->nullable();
            $t->string('amount')->nullable();
            $t->boolean('type');
            $t->timestamps();
        });
        Schema::create('bank_trnsactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bank_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('name')->nullable();
            $t->integer('type')->nullable();
            $t->string('amount')->nullable();
            $t->string('image')->nullable();
            $t->timestamps();
            $t->unsignedBigInteger('trans_bank_id')->nullable();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->date('date')->nullable();
        });
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->double('opening_balance')->nullable();
            $t->timestamps();
        });
        Schema::create('invoice_payments', function (Blueprint $t) {
            $t->id();
            $t->string('value');
            $t->unsignedBigInteger('bank_id')->nullable();
            $t->unsignedBigInteger('bank_transaction_id')->nullable();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->timestamps();
        });

        DB::table('banks')->insert([
            ['id' => 1, 'name' => 'CIB', 'amount' => '700'],
            ['id' => 2, 'name' => 'QNB', 'amount' => '300'],
        ]);
        DB::table('vaults')->insert(['id' => 1, 'amount' => '300']);
        DB::table('companies')->insert(['id' => 1, 'name' => 'Co', 'opening_balance' => 0]);
    }

    private function vaultWithdrawal(float $amount = 300): BankTrnsaction
    {
        DB::table('vault_transactions')->insert([
            'bank_id' => 1, 'name' => 'سحب', 'amount' => (string) $amount, 'type' => 0,
            'created_at' => self::AT, 'updated_at' => self::AT,
        ]);

        return $this->bankRow(['type' => 0, 'name' => 'سحب', 'amount' => (string) $amount]);
    }

    private function bankRow(array $values): BankTrnsaction
    {
        $id = DB::table('bank_trnsactions')->insertGetId($values + [
            'bank_id' => 1, 'created_at' => self::AT, 'updated_at' => self::AT,
        ]);

        return BankTrnsaction::find($id);
    }

    private function balances(): array
    {
        return [
            'bank1' => (float) DB::table('banks')->where('id', 1)->value('amount'),
            'bank2' => (float) DB::table('banks')->where('id', 2)->value('amount'),
            'vault' => (float) DB::table('vaults')->value('amount'),
        ];
    }

    private function assertRejected(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a rejected operation');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_deleting_a_bank_to_vault_withdrawal_returns_the_money_to_the_bank(): void
    {
        $transaction = $this->vaultWithdrawal();

        app(BankTransactionService::class)->delete($transaction->id);

        $this->assertSame(['bank1' => 1000.0, 'bank2' => 300.0, 'vault' => 0.0], $this->balances());
        $this->assertDatabaseCount('bank_trnsactions', 0);
        $this->assertDatabaseCount('vault_transactions', 0);
    }

    public function test_withdrawal_is_not_deleted_when_the_vault_already_spent_the_money(): void
    {
        $transaction = $this->vaultWithdrawal();
        DB::table('vaults')->update(['amount' => '100']);

        $this->assertRejected(fn () => app(BankTransactionService::class)->delete($transaction->id));

        $this->assertSame(['bank1' => 700.0, 'bank2' => 300.0, 'vault' => 100.0], $this->balances());
        $this->assertDatabaseCount('bank_trnsactions', 1);
        $this->assertDatabaseCount('vault_transactions', 1);
    }

    public function test_editing_a_withdrawal_amount_moves_the_difference_on_both_sides(): void
    {
        $transaction = $this->vaultWithdrawal();
        $service = app(BankTransactionService::class);

        $service->update($transaction->id, ['name' => 'سحب نقدية', 'date' => '2026-10-01', 'amount' => 500]);
        $this->assertSame(['bank1' => 500.0, 'bank2' => 300.0, 'vault' => 500.0], $this->balances());
        $this->assertSame(['سحب نقدية', 500.0], [
            DB::table('vault_transactions')->value('name'),
            (float) DB::table('vault_transactions')->value('amount'),
        ]);

        $service->update($transaction->id, ['name' => 'سحب نقدية', 'date' => '2026-10-01', 'amount' => 200]);
        $this->assertSame(['bank1' => 800.0, 'bank2' => 300.0, 'vault' => 200.0], $this->balances());
        $this->assertDatabaseCount('vault_transactions', 1);

        $service->delete($transaction->id);
        $this->assertSame(['bank1' => 1000.0, 'bank2' => 300.0, 'vault' => 0.0], $this->balances());
    }

    public function test_bank_to_bank_transfer_is_reversed_on_both_banks(): void
    {
        $transaction = $this->bankRow(['type' => 2, 'name' => 'تحويل', 'amount' => '100', 'trans_bank_id' => 2]);
        $service = app(BankTransactionService::class);

        $service->update($transaction->id, ['name' => 'تحويل', 'date' => null, 'amount' => 150]);
        $this->assertSame(['bank1' => 650.0, 'bank2' => 350.0, 'vault' => 300.0], $this->balances());

        $service->delete($transaction->id);
        $this->assertSame(['bank1' => 800.0, 'bank2' => 200.0, 'vault' => 300.0], $this->balances());
    }

    public function test_service_payment_of_a_booking_is_not_removed_from_the_bank_screen(): void
    {
        $transaction = $this->bankRow(['type' => 0, 'name' => 'تخصيص - طلب رقم 20', 'amount' => '342']);
        $service = app(BankTransactionService::class);

        $this->assertRejected(fn () => $service->delete($transaction->id));
        $this->assertRejected(fn () => $service->update($transaction->id, ['name' => 'x', 'date' => null, 'amount' => 400]));
        $service->update($transaction->id, ['name' => 'تخصيص - طلب رقم 20 (مصحح)', 'date' => null, 'amount' => 342]);

        $this->assertSame(['bank1' => 700.0, 'bank2' => 300.0, 'vault' => 300.0], $this->balances());
        $this->assertSame('تخصيص - طلب رقم 20 (مصحح)', $transaction->fresh()->name);
    }

    public function test_company_payment_deletion_takes_it_out_of_the_bank_and_reopens_the_invoices(): void
    {
        $transaction = $this->bankRow(['type' => 1, 'name' => 'سداد فواتير - Co', 'amount' => '200', 'company_id' => 1]);
        DB::table('invoice_payments')->insert([
            ['value' => '120', 'bank_id' => 1, 'bank_transaction_id' => $transaction->id, 'invoice_id' => 1],
            ['value' => '80', 'bank_id' => 1, 'bank_transaction_id' => $transaction->id, 'invoice_id' => 2],
        ]);
        $service = app(BankTransactionService::class);

        $this->assertRejected(fn () => $service->update($transaction->id, ['name' => 'x', 'date' => null, 'amount' => 250]));
        $service->delete($transaction->id);

        $this->assertSame(500.0, $this->balances()['bank1']);
        $this->assertDatabaseCount('invoice_payments', 0);
        $this->assertDatabaseCount('bank_trnsactions', 0);
    }

    public function test_opening_balance_payment_deletion_restores_the_company_opening_balance(): void
    {
        $transaction = $this->bankRow(['type' => 1, 'name' => 'سداد الرصيد الافتتاحي - Co', 'amount' => '150', 'company_id' => 1]);

        app(BankTransactionService::class)->delete($transaction->id);

        $this->assertSame(550.0, $this->balances()['bank1']);
        $this->assertSame(150.0, (float) DB::table('companies')->value('opening_balance'));
    }

    public function test_company_payment_without_linked_invoices_is_rejected(): void
    {
        $transaction = $this->bankRow(['type' => 1, 'name' => 'سداد فواتير - Co', 'amount' => '200', 'company_id' => 1]);

        $this->assertRejected(fn () => app(BankTransactionService::class)->delete($transaction->id));

        $this->assertSame(700.0, $this->balances()['bank1']);
        $this->assertDatabaseCount('bank_trnsactions', 1);
    }
}
