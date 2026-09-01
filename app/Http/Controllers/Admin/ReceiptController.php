<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Receipt\StoreReceiptRequest;
use App\Http\Requests\Admin\Receipt\UpdateReceiptRequest;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Receipt;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Supplier;
use App\Services\BookingSupplierReceiptSync;
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
            ->with(['supplier', 'booking', 'bookingService.service'])
            ->when($request->filled('payment_source'), fn ($q) => $q->where('payment_source', $request->payment_source))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->orderByDesc('id')
            ->paginate(20);

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('admin.receipts.index', compact('receipts', 'suppliers'));
    }

    public function create(Request $request)
    {
        return view('admin.receipts.create', $this->formInputs([
            'method' => 'POST',
            'action' => route('receipts.store'),
            'selectedSupplierId' => $request->get('supplier_id'),
        ]));
    }

    public function store(StoreReceiptRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $data = $this->normalizedReceiptData($validated);
                $sync = app(BookingSupplierReceiptSync::class);

                // Linked to a booking: create BookingService first (same as order receipt form)
                // so it appears on the order + invoice receipts section.
                if (!empty($data['booking_id']) && !empty($validated['service_id'])) {
                    $paymentType = $this->mapPaymentType($data['payment_source'] ?? null);

                    $bookingService = BookingService::create([
                        'booking_id' => $data['booking_id'],
                        'service_id' => (int) $validated['service_id'],
                        'price' => $data['cost'],
                        'note' => $data['notes'] ?? null,
                        'payment_type' => $paymentType,
                        'supplier_id' => $data['supplier_id'] ?? null,
                        'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                        'image' => $request->file('image'),
                        'created_by' => auth()->id(),
                    ]);

                    if ($paymentType === 'supplier') {
                        $sync->syncFromBookingService($bookingService->fresh());
                    } else {
                        Receipt::create(array_merge($data, [
                            'booking_service_id' => $bookingService->id,
                        ]));
                    }

                    return;
                }

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
        $receipt->loadMissing(['booking', 'bookingService.service']);

        $bookingService = $receipt->bookingService;
        $serviceTypeId = $bookingService?->service?->service_category_id;

        return view('admin.receipts.edit', $this->formInputs([
            'method' => 'PUT',
            'action' => route('receipts.update', $receipt),
            'receipt' => $receipt,
            'selectedSupplierId' => $receipt->supplier_id,
            'booking_service' => $bookingService,
            'service_type_id' => $serviceTypeId,
        ]));
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
                $hadLinkedService = (bool) $locked->booking_service_id;

                $validated = $request->validated();
                $data = $this->normalizedReceiptData($validated);
                $sync = app(BookingSupplierReceiptSync::class);

                // Already mirrored as booking service (supplier): update BS then re-sync receipt/balance
                if ($hadLinkedService && $locked->bookingService?->payment_type === 'supplier') {
                    $bookingService = BookingService::query()
                        ->lockForUpdate()
                        ->findOrFail($locked->booking_service_id);

                    if (empty($data['booking_id'])) {
                        // Keep receipt standalone; remove only the booking service mirror
                        $bookingService->delete();
                        $locked->refresh();

                        if ($oldSource === Receipt::PAYMENT_SOURCE_SUPPLIER && $oldSupplierId) {
                            $this->adjustSupplierBalance($oldSupplierId, -$oldCost);
                        }

                        $locked->update(array_merge($data, ['booking_service_id' => null]));
                        $locked->refresh();

                        if ($locked->payment_source === Receipt::PAYMENT_SOURCE_SUPPLIER && $locked->supplier_id) {
                            $this->adjustSupplierBalance((int) $locked->supplier_id, (float) $locked->cost);
                        }

                        return;
                    }

                    $bsPayload = [
                        'booking_id' => $data['booking_id'],
                        'price' => $data['cost'],
                        'note' => $data['notes'] ?? null,
                        'payment_type' => 'supplier',
                        'supplier_id' => $data['supplier_id'] ?? null,
                        'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                        'updated_by' => auth()->id(),
                    ];

                    if (!empty($validated['service_id'])) {
                        $bsPayload['service_id'] = (int) $validated['service_id'];
                    }

                    if ($request->hasFile('image')) {
                        $bsPayload['image'] = $request->file('image');
                    }

                    $bookingService->update($bsPayload);
                    $sync->syncFromBookingService($bookingService->fresh());

                    return;
                }

                if ($oldSource === Receipt::PAYMENT_SOURCE_SUPPLIER && $oldSupplierId) {
                    $this->adjustSupplierBalance($oldSupplierId, -$oldCost);
                }

                $locked->update($data);
                $locked->refresh();

                if ($locked->payment_source === Receipt::PAYMENT_SOURCE_SUPPLIER && $locked->supplier_id) {
                    $this->adjustSupplierBalance((int) $locked->supplier_id, (float) $locked->cost);
                }

                // Newly linking to a booking with a service (create BookingService, keep current balance)
                if (!empty($data['booking_id']) && !empty($validated['service_id']) && !$hadLinkedService) {
                    $extra = ['service_id' => $validated['service_id']];
                    if ($request->hasFile('image')) {
                        $extra['image'] = $request->file('image');
                    }
                    $sync->syncBookingServiceFromReceipt($locked, $extra);
                } elseif ($hadLinkedService) {
                    $this->mirrorToBookingService($locked, $validated, $request->file('image'));
                }
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

                if ($bookingServiceId) {
                    BookingService::query()
                        ->where('id', $bookingServiceId)
                        ->whereIn('payment_type', ['supplier', 'vault', 'agent'])
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

    private function formInputs(array $overrides = []): array
    {
        $serviceTypes = ServiceCategory::orderBy('title')->pluck('title', 'id');
        $suppliers = Supplier::orderBy('name')->pluck('name', 'id');
        $bookings = Booking::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'booking_number']);

        return array_merge([
            'suppliers' => $suppliers,
            'bookings' => $bookings,
            'service_types' => $serviceTypes,
            'services' => Service::pluck('name', 'id'),
        ], $overrides);
    }

    private function normalizedReceiptData(array $data): array
    {
        unset($data['booking_number'], $data['service_type_id'], $data['service_id'], $data['image']);

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

    private function mapPaymentType(?string $paymentSource): string
    {
        return match ($paymentSource) {
            Receipt::PAYMENT_SOURCE_SUPPLIER => 'supplier',
            Receipt::PAYMENT_SOURCE_SAFE => 'vault',
            Receipt::PAYMENT_SOURCE_REPRESENTATIVE => 'agent',
            default => 'supplier',
        };
    }

    private function mirrorToBookingService(Receipt $receipt, array $validated = [], $image = null): void
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

        $payload = [
            'booking_id' => $receipt->booking_id,
            'price' => $receipt->cost,
            'note' => $receipt->notes,
            'payment_type' => $this->mapPaymentType($receipt->payment_source),
            'supplier_id' => $receipt->supplier_id,
            'supplier_invoice_number' => $receipt->supplier_invoice_number,
            'updated_by' => auth()->id(),
        ];

        if (!empty($validated['service_id'])) {
            $payload['service_id'] = (int) $validated['service_id'];
        }

        if ($image) {
            $payload['image'] = $image;
        }

        $bookingService->update($payload);
    }

    private function adjustSupplierBalance(int $supplierId, float $delta): void
    {
        $supplier = Supplier::query()->lockForUpdate()->findOrFail($supplierId);
        $supplier->balance = round((float) $supplier->balance + $delta, 2);
        $supplier->save();
    }
}
