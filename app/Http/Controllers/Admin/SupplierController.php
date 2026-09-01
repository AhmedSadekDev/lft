<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Supplier\StoreSupplierPaymentRequest;
use App\Http\Requests\Admin\Supplier\StoreSupplierRequest;
use App\Http\Requests\Admin\Supplier\UpdateSupplierRequest;
use App\Models\Agent;
use App\Models\MoneyTransfer;
use App\Models\Receipt;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$this->userCanManageSuppliers()) {
                abort(403);
            }

            return $next($request);
        })->only(['index', 'show', 'statement', 'create', 'store', 'showPaymentForm', 'processPayment', 'edit', 'update', 'destroy']);
    }

    private function userCanManageSuppliers(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->roles->pluck('name')->contains('Admin')) {
            return true;
        }

        return has_app_permission('suppliers.index')
            || has_app_permission('suppliers.create')
            || has_app_permission('suppliers.udpate')
            || has_app_permission('suppliers.update')
            || has_app_permission('suppliers.delete');
    }

    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->search($request->search)
            ->orderBy('name')
            ->paginate(20);

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $input = [
            'method' => 'POST',
            'action' => route('suppliers.store'),
        ];

        return view('admin.suppliers.create', $input);
    }

    public function store(StoreSupplierRequest $request)
    {
        Supplier::create([
            'name' => $request->name,
            'balance' => $request->input('balance', 0),
        ]);

        return redirect()
            ->route('suppliers.index')
            ->with('success', __('alerts.added_successfully'));
    }

    public function show(Supplier $supplier)
    {
        return redirect()->route('suppliers.statement', $supplier);
    }

    public function edit(Supplier $supplier)
    {
        $input = [
            'method' => 'PUT',
            'action' => route('suppliers.update', $supplier),
            'supplier' => $supplier,
        ];

        return view('admin.suppliers.edit', $input);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update([
            'name' => $request->name,
        ]);

        return redirect()
            ->route('suppliers.index')
            ->with('success', __('alerts.updated_successfully'));
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return response()->json([
            'staus' => true,
            'msg' => __('alerts.deleted_successfully'),
        ], 200);
    }

    /**
     * Supplier account statement.
     * Receipts are grouped by supplier_invoice_number with summed cost so that
     * multiple receipts for the same invoice (across bookings) appear once.
     */
    public function statement(Request $request, Supplier $supplier)
    {
        $fromDate = $request->from;
        $toDate = $request->to;

        $groupedInvoices = Receipt::query()
            ->forSupplier($supplier->id)
            ->fromSupplier()
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->groupedByInvoice()
            ->get();

        // Receipts without an invoice number — listed individually
        $ungroupedReceipts = Receipt::query()
            ->forSupplier($supplier->id)
            ->fromSupplier()
            ->with('booking')
            ->where(function ($q) {
                $q->whereNull('supplier_invoice_number')
                    ->orWhere('supplier_invoice_number', '');
            })
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->orderByDesc('created_at')
            ->get();

        $payments = SupplierPayment::query()
            ->forSupplier($supplier->id)
            ->with('agent')
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->orderByDesc('created_at')
            ->get();

        $totalInvoices = (float) $groupedInvoices->sum('total_cost')
            + (float) $ungroupedReceipts->sum('cost');
        $totalPayments = (float) $payments->sum('amount');

        return view('admin.suppliers.statement', compact(
            'supplier',
            'groupedInvoices',
            'ungroupedReceipts',
            'payments',
            'totalInvoices',
            'totalPayments',
            'fromDate',
            'toDate'
        ));
    }

    public function showPaymentForm(Supplier $supplier)
    {
        $agents = Agent::orderBy('name')->get(['id', 'name', 'wallet']);
        $vault = Vault::first();

        return view('admin.suppliers.payment', compact('supplier', 'agents', 'vault'));
    }

    /**
     * Process a payment to a supplier inside a DB transaction:
     * 1) record supplier_payments row
     * 2) deduct supplier balance
     * 3) deduct from vault (safe) or agent wallet (representative)
     */
    public function processPayment(StoreSupplierPaymentRequest $request, Supplier $supplier)
    {
        $amount = round((float) $request->amount, 2);
        $sourceType = $request->source_type;
        $sourceId = $sourceType === SupplierPayment::SOURCE_REPRESENTATIVE
            ? (int) $request->source_id
            : null;

        try {
            DB::transaction(function () use ($supplier, $amount, $sourceType, $sourceId, $request) {
                /** @var Supplier $lockedSupplier */
                $lockedSupplier = Supplier::query()->lockForUpdate()->findOrFail($supplier->id);

                if ((float) $lockedSupplier->balance < $amount) {
                    throw ValidationException::withMessages([
                        'amount' => 'رصيد المورد غير كافٍ. الرصيد المتاح: ' . number_format((float) $lockedSupplier->balance, 2),
                    ]);
                }

                $this->deductFromPaymentSource($sourceType, $sourceId, $amount, $lockedSupplier);

                $payment = SupplierPayment::create([
                    'supplier_id' => $lockedSupplier->id,
                    'amount' => $amount,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'notes' => $request->notes,
                ]);

                $lockedSupplier->balance = round((float) $lockedSupplier->balance - $amount, 2);
                $lockedSupplier->save();

                MoneyTransfer::create([
                    'value' => $amount,
                    'transfered_type' => SupplierPayment::class,
                    'transfered_id' => $payment->id,
                    'transferer_type' => User::class,
                    'transferer_id' => auth()->id(),
                    'type' => MoneyTransfer::supplierPayment,
                    'notes' => $request->notes,
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تسجيل سداد المورد: ' . $e->getMessage());
        }

        return redirect()
            ->route('suppliers.statement', $supplier)
            ->with('success', 'تم تسجيل سداد المورد بنجاح. المبلغ: ' . number_format($amount, 2) . ' جنيه');
    }

    /**
     * Deduct the payment amount from vault (safe) or agent wallet (representative).
     *
     * @throws ValidationException
     */
    private function deductFromPaymentSource(string $sourceType, ?int $sourceId, float $amount, Supplier $supplier): void
    {
        if ($sourceType === SupplierPayment::SOURCE_SAFE) {
            $vault = Vault::query()->lockForUpdate()->first();

            if (!$vault) {
                throw ValidationException::withMessages([
                    'source_type' => 'الخزنة غير موجودة',
                ]);
            }

            if ((float) $vault->amount < $amount) {
                throw ValidationException::withMessages([
                    'amount' => __('main.wallet_does_not_have_enough_amount'),
                ]);
            }

            $vault->amount = round((float) $vault->amount - $amount, 2);
            $vault->save();

            VaultTransaction::create([
                'name' => 'سداد مورد - ' . $supplier->name,
                'amount' => $amount,
                'type' => 0, // منصرف
            ]);

            return;
        }

        if ($sourceType === SupplierPayment::SOURCE_REPRESENTATIVE) {
            $agent = Agent::query()->lockForUpdate()->find($sourceId);

            if (!$agent) {
                throw ValidationException::withMessages([
                    'source_id' => 'المندوب المحدد غير موجود',
                ]);
            }

            if ((float) $agent->wallet < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'رصيد المندوب غير كافٍ. الرصيد المتاح: ' . number_format((float) $agent->wallet, 2),
                ]);
            }

            $agent->wallet = round((float) $agent->wallet - $amount, 2);
            $agent->save();

            // Column name matches VaultTransaction::agient() FK used across the app
            VaultTransaction::create([
                'name' => 'سداد مورد من عهدة المندوب - ' . $supplier->name . ' / ' . $agent->name,
                'amount' => $amount,
                'type' => 0,
                'agient_id' => $agent->id,
            ]);

            return;
        }

        throw ValidationException::withMessages([
            'source_type' => 'مصدر السداد غير صالح',
        ]);
    }
}
