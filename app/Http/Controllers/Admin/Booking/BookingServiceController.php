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
use App\Models\VaultTransaction;
use App\Models\BankTrnsaction;
use Illuminate\Support\Facades\DB;

class BookingServiceController extends Controller
{
    public function create(Booking $booking)
    {
        // saving the caller page, as this page can be called from different sources
        $referer = request()->server('HTTP_REFERER');
        session(['booking_services_create_referrer' => $referer]);

        // sending the view
        $service_types = ServiceCategory::all()->pluck('title', 'id');
        $services = Service::pluck('name', 'id');
        $company_prices = $booking
            ->company
            ->services
            ->pluck(
                'pivot.cost',
                'id'
            );
        $banks = Bank::whereNotNull('name')->pluck('name', 'id');
        $agents = Agent::whereNotNull('name')->pluck('name', 'id');

        $inputs = [
            'method' => 'POST',
            'action' => route(
                'booking-services.store',
                ['booking' => $booking->id]
            ),
            'service_types' => $service_types,
            'services' => $services,
            'company_prices' => $company_prices,
            'booking' => $booking,
            'banks' => $banks,
            'agents' => $agents,
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

            // Handle financial transaction based on payment type
            $this->handlePaymentTransaction($booking_service, 'create');


            DB::commit();

            if ($booking_service) {
                $referer = session('booking_services_create_referrer')
                    ?? route('bookings.show', ['booking' => $booking->id]);
                session()->forget('booking_services_create_referrer');

                return redirect($referer)->with('success', __('alerts.added_successfully'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', __('alerts.error_occurred') . ': ' . $e->getMessage());
        }
    }
    public function edit(Booking $booking, BookingService $booking_service)
    {
        // saving the caller page, as this page can be called from different sources
        $referer = request()->server('HTTP_REFERER');
        session(['booking_services_edit_referrer' => $referer]);

        // sending the view
        $service_types = ServiceCategory::all()->pluck('title', 'id');
        $services = Service::pluck('name', 'id');
        $company_prices = $booking
            ->company
            ->services
            ->pluck(
                'pivot.cost',
                'id'
            );

        // Get the service type ID from the service
        $service_type_id = $booking_service->service->service_category_id ?? null;

        $banks = Bank::whereNotNull('name')->pluck('name', 'id');
        $agents = Agent::whereNotNull('name')->pluck('name', 'id');

        $inputs = [
            'method' => 'PUT',
            'action' => route(
                'booking-services.update',
                ['booking' => $booking->id, 'booking_service' => $booking_service->id]
            ),
            'service_types' => $service_types,
            'services' => $services,
            'company_prices' => $company_prices,
            'booking' => $booking,
            'booking_service' => $booking_service,
            'service_type_id' => $service_type_id,
            'banks' => $banks,
            'agents' => $agents,
        ];
        return view('admin.bookings.booking-services.edit', $inputs);
    }

    public function update(BookingServiceRequest $request, Booking $booking, BookingService $booking_service)
    {
        DB::beginTransaction();
        try {
            // Store old values
            $oldPrice = $booking_service->price;
            $oldPaymentType = $booking_service->payment_type;
            $oldBankId = $booking_service->bank_id;
            $oldVaultId = $booking_service->vault_id;
            $oldAgentId = $booking_service->agent_id;

            $data = $request->validated();
            $data['updated_by'] = auth()->user()->id;

            $booking_service->update($data);

            // Return old amount
            $this->handlePaymentTransaction($booking_service, 'return', $oldPrice, $oldPaymentType, $oldBankId, $oldVaultId, $oldAgentId);

            // Deduct new amount
            $this->handlePaymentTransaction($booking_service, 'create');

            DB::commit();

            $referer = session('booking_services_edit_referrer')
                ?? route('bookings.show', ['booking' => $booking->id]);
            session()->forget('booking_services_edit_referrer');

            return redirect($referer)->with('success', __('alerts.updated_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', __('alerts.error_occurred') . ': ' . $e->getMessage());
        }
    }

    /**
     * Handle payment transaction based on payment type
     */
    private function handlePaymentTransaction($booking_service, $action = 'create', $amount = null, $payment_type = null, $bank_id = null, $vault_id = null, $agent_id = null)
    {
        $price = $amount ?? $booking_service->price;
        $type = $payment_type ?? $booking_service->payment_type;
        $transaction_type = $action === 'return' ? 1 : 0; // 0 = debit (خصم), 1 = credit (إضافة)

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

                    // Record vault transaction
                    VaultTransaction::create([
                        'bank_id' => null,
                        'name' => $transaction_name,
                        'amount' => $price,
                        'type' => $transaction_type
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

                        // Record bank transaction
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

                        // Record vault transaction for agent (as per existing pattern)
                        VaultTransaction::create([
                            'agient_id' => $agentId,
                            'name' => $transaction_name,
                            'amount' => $price,
                            'type' => $transaction_type
                        ]);
                    }
                }
                break;
        }
    }

    public function destroy(BookingService $booking_service)
    {
        $booking_service->delete();
        return response()->json([
            'status' => true,
            'message' => __('alerts.added_successfully')
        ], 200);
    }
}
