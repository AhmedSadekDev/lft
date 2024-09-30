<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingServiceRequest;
use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Service;
use App\Models\ServiceCategory;

class BookingServiceController extends Controller
{
    public function create(Booking $booking)
    {
        // saving the caller page, as this page can be called from different sources
        $referer = request()->server('HTTP_REFERER');
        session(['booking_services_create_referrer' => $referer]);

        // sending the view
        $service_types = ServiceCategory::all()->pluck('title', 'id');
        $services = Service::get('id', 'title');
        $company_prices = $booking
            ->company
            ->services
            ->pluck(
                'pivot.cost',
                'id'
            );
        $inputs = [
            'method' => 'POST',
            'action' => route(
                'booking-services.store',
                ['booking' => $booking->id]
            ),
            'service_types' => $service_types,
            'services' => $services,
            'company_prices' => $company_prices,
            'booking' => $booking
        ];
        return view('admin.bookings.booking-services.create', $inputs);
    }
    public function store(BookingServiceRequest $request, Booking $booking)
    {
        $booking_service = BookingService::create(array_merge(
            $request->validated(),
            ['booking_id' => $booking->id]
        ));

        if ($booking_service) {
            $referer = session('booking_services_create_referrer')
                ?? route('bookings.show', ['booking' => $booking->id]);
            session()->forget('booking_services_create_referrer');

            return redirect($referer)->with(__('alerts.updated_successfully'));
        } else
            return redirect()
                ->back()
                ->with(__('alerts.updated_successfully'));
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
