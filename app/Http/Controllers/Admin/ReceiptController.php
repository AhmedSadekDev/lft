<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Receipt\StoreReceiptRequest;
use App\Http\Requests\Admin\Receipt\UpdateReceiptRequest;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Receipt;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user) {
                abort(403);
            }

            if ($user->roles->pluck('name')->contains('Admin')
                || has_app_permission('suppliers.index')
                || has_app_permission('suppliers.create')
                || has_app_permission('suppliers.udpate')
                || has_app_permission('suppliers.update')
                || has_app_permission('suppliers.delete')
            ) {
                return $next($request);
            }

            abort(403);
        });
    }

    public function index(Request $request)
    {
        $receipts = Receipt::query()
            ->with(['supplier', 'booking'])
            ->when($request->filled('payment_source'), fn ($q) => $q->where('payment_source', $request->payment_source))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->orderByDesc('id')
            ->paginate(20);

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('admin.receipts.index', compact('receipts', 'suppliers'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->pluck('name', 'id');
        $bookings = Booking::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'booking_number']);

        $input = [
            'method' => 'POST',
            'action' => route('receipts.store'),
            'suppliers' => $suppliers,
            'bookings' => $bookings,
            'selectedSupplierId' => $request->get('supplier_id'),
        ];

        return view('admin.receipts.create', $input);
    }

    public function store(StoreReceiptRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $data = $this->normalizedReceiptData($request->validated());

                $receipt = Receipt::create($data);

                if ($receipt->payment_source === Receipt::PAYMENT_SOURCE_SUPPLIER && $receipt->supplier_id) {
                    $this->adjustSupplierBalance((int) $receipt->supplier_id, (float) $receipt->cost);
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'تعذر حفظ الإيصال: ' . $e->getMessage());
        }

        return redirect()
            ->route('receipts.index')
            ->with('success', __('alerts.added_successfully'));
    }

    public function edit(Receipt $receipt)
    {
        $receipt->loadMissing('booking');

        $suppliers = Supplier::orderBy('name')->pluck('name', 'id');
        $bookings = Booking::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'booking_number']);

        $input = [
            'method' => 'PUT',
            'action' => route('receipts.update', $receipt),
            'receipt' => $receipt,
            'suppliers' => $suppliers,
            'bookings' => $bookings,
            'selectedSupplierId' => $receipt->supplier_id,
        ];

        return view('admin.receipts.edit', $input);
    }

    public function update(UpdateReceiptRequest $request, Receipt $receipt)
    {
        try {
            DB::transaction(function () use ($request, $receipt) {
                /** @var Receipt $locked */
                $locked = Receipt::query()->lockForUpdate()->findOrFail($receipt->id);
                $oldSource = $locked->payment_source;
                $oldSupplierId = $locked->supplier_id ? (int) $locked->supplier_id : null;
                $oldCost = (float) $locked->cost;

                if ($oldSource === Receipt::PAYMENT_SOURCE_SUPPLIER && $oldSupplierId) {
                    $this->adjustSupplierBalance($oldSupplierId, -$oldCost);
                }

                $data = $this->normalizedReceiptData($request->validated());
                $locked->update($data);
                $locked->refresh();

                if ($locked->payment_source === Receipt::PAYMENT_SOURCE_SUPPLIER && $locked->supplier_id) {
                    $this->adjustSupplierBalance((int) $locked->supplier_id, (float) $locked->cost);
                }

                $this->mirrorToBookingService($locked);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'تعذر تحديث الإيصال: ' . $e->getMessage());
        }

        return redirect()
            ->route('receipts.index')
            ->with('success', __('alerts.updated_successfully'));
    }

    public function destroy(Receipt $receipt)
    {
        try {
            DB::transaction(function () use ($receipt) {
                /** @var Receipt $locked */
                $locked = Receipt::query()->lockForUpdate()->findOrFail($receipt->id);
                $bookingServiceId = $locked->booking_service_id;

                if ($locked->payment_source === Receipt::PAYMENT_SOURCE_SUPPLIER && $locked->supplier_id) {
                    $this->adjustSupplierBalance((int) $locked->supplier_id, -((float) $locked->cost));
                }

                $locked->delete();

                // Remove mirrored booking service without re-touching supplier balance
                if ($bookingServiceId) {
                    BookingService::query()
                        ->where('id', $bookingServiceId)
                        ->where('payment_type', 'supplier')
                        ->delete();
                }
            });
        } catch (\Throwable $e) {
            return response()->json([
                'staus' => false,
                'msg' => 'تعذر حذف الإيصال: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'staus' => true,
            'msg' => __('alerts.deleted_successfully'),
        ]);
    }

    private function normalizedReceiptData(array $data): array
    {
        unset($data['booking_number']);

        if (($data['payment_source'] ?? null) !== Receipt::PAYMENT_SOURCE_SUPPLIER) {
            $data['supplier_id'] = null;
            $data['supplier_invoice_number'] = null;
        }

        if (array_key_exists('booking_id', $data) && !$data['booking_id']) {
            $data['booking_id'] = null;
        }

        $data['cost'] = round((float) ($data['cost'] ?? 0), 2);

        return $data;
    }

    /**
     * Keep linked BookingService fields in sync when editing from receipts screen.
     */
    private function mirrorToBookingService(Receipt $receipt): void
    {
        if (!$receipt->booking_service_id) {
            return;
        }

        $bookingService = BookingService::query()
            ->lockForUpdate()
            ->find($receipt->booking_service_id);

        if (!$bookingService) {
            return;
        }

        $bookingService->update([
            'booking_id' => $receipt->booking_id,
            'price' => $receipt->cost,
            'note' => $receipt->notes,
            'payment_type' => $receipt->payment_source === Receipt::PAYMENT_SOURCE_SUPPLIER
                ? 'supplier'
                : $bookingService->payment_type,
            'supplier_id' => $receipt->supplier_id,
            'supplier_invoice_number' => $receipt->supplier_invoice_number,
        ]);
    }

    private function adjustSupplierBalance(int $supplierId, float $delta): void
    {
        $supplier = Supplier::query()->lockForUpdate()->findOrFail($supplierId);
        $supplier->balance = round((float) $supplier->balance + $delta, 2);
        $supplier->save();
    }
}
