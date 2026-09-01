<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingServiceRequest;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Bank;
use App\Models\Vault;
use App\Models\Agent;
use App\Models\Supplier;
use App\Models\VaultTransaction;
use App\Models\BankTrnsaction;
use App\Services\BookingSupplierReceiptSync;
use Illuminate\Support\Facades\DB;

class BookingServiceController extends Controller
{
    public function create(Booking $booking)
    {
        $referer = request()->server('HTTP_REFERER');
        session(['booking_services_create_referrer' => $referer]);

        $service_types = ServiceCategory::all()->pluck('title', 'id');
        $services = Service::pluck('name', 'id');
        $company_prices = $booking
            ->company
            ->services
            ->pluck('pivot.cost', 'id');
        $banks = Bank::whereNotNull('name')->pluck('name', 'id');
        $agents = Agent::whereNotNull('name')->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name')->pluck('name', 'id');

        $inputs = [
            'method' => 'POST',
            'action' => route('booking-services.store', ['booking' => $booking->id]),
            'service_types' => $service_types,
            'services' => $services,
            'company_prices' => $company_prices,
            'booking' => $booking,
            'banks' => $banks,
            'agents' => $agents,
            'suppliers' => $suppliers,
        ];

        return view('admin.bookings.booking-services.create', $inputs);
    }

    public function store(BookingServiceRequest $request, Booking $booking)
    {
        DB::beginTransaction();
        try {
            $data = array_merge(
                $request->validated(),
                [
                    'booking_id' => $booking->id,
                    'created_by' => auth()->user()->id,
                ]
            );

            $booking_service = BookingService::create($data);

            $this->handlePaymentTransaction($booking_service, 'create');
            app(BookingSupplierReceiptSync::class)->syncFromBookingService($booking_service);

            DB::commit();

            $referer = session('booking_services_create_referrer')
                ?? route('bookings.show', ['booking' => $booking->id]);
            session()->forget('booking_services_create_referrer');

            return redirect($referer)->with('success', __('alerts.added_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('alerts.error_occurred') . ': ' . $e->getMessage());
        }
    }

    public function edit(Booking $booking, BookingService $booking_service)
    {
        $referer = request()->server('HTTP_REFERER');
        session(['booking_services_edit_referrer' => $referer]);

        $service_types = ServiceCategory::all()->pluck('title', 'id');
        $services = Service::pluck('name', 'id');
        $company_prices = $booking
            ->company
            ->services
            ->pluck('pivot.cost', 'id');

        $service_type_id = $booking_service->service->service_category_id ?? null;

        $banks = Bank::whereNotNull('name')->pluck('name', 'id');
        $agents = Agent::whereNotNull('name')->pluck('name', 'id');
        $suppliers = Supplier::orderBy('name')->pluck('name', 'id');

        $inputs = [
            'method' => 'PUT',
            'action' => route('booking-services.update', [
                'booking' => $booking->id,
                'booking_service' => $booking_service->id,
            ]),
            'service_types' => $service_types,
            'services' => $services,
            'company_prices' => $company_prices,
            'booking' => $booking,
            'booking_service' => $booking_service,
            'service_type_id' => $service_type_id,
            'banks' => $banks,
            'agents' => $agents,
            'suppliers' => $suppliers,
        ];

        return view('admin.bookings.booking-services.edit', $inputs);
    }

    public function update(BookingServiceRequest $request, Booking $booking, BookingService $booking_service)
    {
        DB::beginTransaction();
        try {
            $oldPrice = $booking_service->price;
            $oldPaymentType = $booking_service->payment_type;
            $oldBankId = $booking_service->bank_id;
            $oldVaultId = $booking_service->vault_id;
            $oldAgentId = $booking_service->agent_id;

            $data = $request->validated();
            $data['updated_by'] = auth()->user()->id;

            $booking_service->update($data);
            $booking_service->refresh();

            // Return old vault/bank/agent amount (supplier has no wallet debit)
            $this->handlePaymentTransaction(
                $booking_service,
                'return',
                $oldPrice,
                $oldPaymentType,
                $oldBankId,
                $oldVaultId,
                $oldAgentId
            );

            $this->handlePaymentTransaction($booking_service, 'create');
            app(BookingSupplierReceiptSync::class)->syncFromBookingService($booking_service);

            DB::commit();

            $referer = session('booking_services_edit_referrer')
                ?? route('bookings.show', ['booking' => $booking->id]);
            session()->forget('booking_services_edit_referrer');

            return redirect($referer)->with('success', __('alerts.updated_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('alerts.error_occurred') . ': ' . $e->getMessage());
        }
    }

    private function handlePaymentTransaction(
        $booking_service,
        $action = 'create',
        $amount = null,
        $payment_type = null,
        $bank_id = null,
        $vault_id = null,
        $agent_id = null
    ) {
        $price = $amount ?? $booking_service->price;
        $type = $payment_type ?? $booking_service->payment_type;
        $transaction_type = $action === 'return' ? 1 : 0;

        // Supplier payments are mirrored to receipts / supplier balance — no vault debit
        if ($type === 'supplier' || empty($type)) {
            return;
        }

        $service_name = $booking_service->service->name ?? 'خدمة';
        $transaction_name = "{$service_name} - طلب رقم {$booking_service->booking_id}";

        switch ($type) {
            case 'vault':
                $vault = Vault::first();
                if ($vault) {
                    if ($action === 'return') {
                        $vault->amount = ($vault->amount ?? 0) + $price;
                    } else {
                        $vault->amount = ($vault->amount ?? 0) - $price;
                    }
                    $vault->save();

                    VaultTransaction::create([
                        'bank_id' => null,
                        'name' => $transaction_name,
                        'amount' => $price,
                        'type' => $transaction_type,
                    ]);
                }
                break;

            case 'bank':
                $bankId = $bank_id ?? $booking_service->bank_id;
                if ($bankId) {
                    $bank = Bank::find($bankId);
                    if ($bank) {
                        if ($action === 'return') {
                            $bank->amount = ($bank->amount ?? 0) + $price;
                        } else {
                            $bank->amount = ($bank->amount ?? 0) - $price;
                        }
                        $bank->save();

                        BankTrnsaction::create([
                            'bank_id' => $bankId,
                            'user_id' => auth()->user()->id,
                            'name' => $transaction_name,
                            'type' => $transaction_type,
                            'amount' => $price,
                        ]);
                    }
                }
                break;

            case 'agent':
                $agentId = $agent_id ?? $booking_service->agent_id;
                if ($agentId) {
                    $agent = Agent::find($agentId);
                    if ($agent) {
                        if ($action === 'return') {
                            $agent->wallet = ($agent->wallet ?? 0) + $price;
                        } else {
                            $agent->wallet = ($agent->wallet ?? 0) - $price;
                        }
                        $agent->save();

                        VaultTransaction::create([
                            'agient_id' => $agentId,
                            'name' => $transaction_name,
                            'amount' => $price,
                            'type' => $transaction_type,
                        ]);
                    }
                }
                break;
        }
    }

    public function destroy(BookingService $booking_service)
    {
        try {
            DB::transaction(function () use ($booking_service) {
                $locked = BookingService::query()->lockForUpdate()->findOrFail($booking_service->id);

                $this->handlePaymentTransaction($locked, 'return');
                app(BookingSupplierReceiptSync::class)->deleteLinkedReceipt($locked);

                $locked->delete();
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => __('alerts.error_occurred') . ': ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => __('alerts.deleted_successfully'),
        ], 200);
    }
}
