<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingServiceRequest;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Bank;
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

            // Handle financial transaction - deduct from bank account
            if ($booking_service->bank_id) {
                $bank = Bank::find($booking_service->bank_id);
                if ($bank) {
                    $bank->amount = ($bank->amount ?? 0) - $booking_service->price;
                    $bank->save();
                }
            }

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
        ];
        return view('admin.bookings.booking-services.edit', $inputs);
    }

    public function update(BookingServiceRequest $request, Booking $booking, BookingService $booking_service)
    {
        DB::beginTransaction();
        try {
            $oldPrice = $booking_service->price;
            $oldBankId = $booking_service->bank_id;

            $data = $request->validated();
            $data['updated_by'] = auth()->user()->id;

            $booking_service->update($data);

            $newPrice = $booking_service->price;
            $newBankId = $booking_service->bank_id;

            $priceDiff = $newPrice - $oldPrice;

            // Handle old bank - return the old amount
            if ($oldBankId) {
                $oldBank = Bank::find($oldBankId);
                if ($oldBank) {
                    $oldBank->amount = ($oldBank->amount ?? 0) + $oldPrice;
                    $oldBank->save();
                }
            }

            // Handle new bank - deduct the new amount
            if ($newBankId) {
                $newBank = Bank::find($newBankId);
                if ($newBank) {
                    $newBank->amount = ($newBank->amount ?? 0) - $newPrice;
                    $newBank->save();
                }
            }

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

    public function destroy(BookingService $booking_service)
    {
        $booking_service->delete();
        return response()->json([
            'status' => true,
            'message' => __('alerts.added_successfully')
        ], 200);
    }
}
