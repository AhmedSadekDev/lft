<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BookingResource;
use App\Http\Resources\ContainerResource;
use App\Http\Resources\FactoryResource;
use App\Http\Resources\LastMovementResource;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\BookingContainer;

class BookingController extends Controller
{
    public function getBooking(Request $request)
    {
        $booking = Booking::where('booking_number', $request->order_number)->first();
        if (is_null($booking))
            return response()->json(['message' => __('admin.not_found')]);

        $containers = ContainerResource::collection(
            $booking->load('bookingContainers')
        );
        return response()->json(['status' => true, 'message' => 'Orders', 'data' => $containers]);
    }

    public function getContainerDetails(BookingContainer $booking_container)
    {
        $data = [
            'bookingDetails'    => new BookingResource($booking_container),
            'factoryDetails'    => $booking_container->branch ? new FactoryResource($booking_container->branch) : null,
            'lastMovements'     =>
            // LastMovementResource::collection($booking_container->last_movement)
            $booking_container->last_movement
        ];

        return response()->json(['status' => true, 'message' => 'Orders', 'data' => $data]);
    }

    public function getCompanyBookings()
    {
        $bookings = auth()->user()->bookings;
        return $this->returnAllData(BookingResource::collection($bookings));
    }
}
