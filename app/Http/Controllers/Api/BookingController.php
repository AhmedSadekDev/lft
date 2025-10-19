<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BookingResource;
use App\Http\Resources\Api\BookingPaperResource;
use App\Http\Resources\ContainerResource;
use App\Http\Resources\FactoryResource;
use App\Http\Resources\LastMovementResource;
use App\Models\Booking;
use App\Models\BookingPaper;
use Illuminate\Http\Request;
use App\Models\BookingContainer;

class BookingController extends Controller
{
    public function getBooking(Request $request)
    {
        $booking = Booking::where('booking_number', $request->order_number)->first();
        if (is_null($booking)) {
            return response()->json(['status' => false, 'message' => __('admin.not_found')]);
        }
        $booking->load('bookingContainers');
        $containers = ContainerResource::collection($booking->bookingContainers);
    
        return response()->json(['status' => true, 'message' => 'Orders', 'data' => $containers]);
    }

    public function getContainerDetails(BookingContainer $booking_container)
    {
        $booking = $booking_container->booking()->with('bookingContainers')->first();
        $data = [
            'bookingDetails' => new BookingResource($booking, $booking_container->id),
            'factoryDetails' => $booking_container->branch ? new FactoryResource($booking_container->branch) : null,
            'lastMovements'  => $booking_container->last_movement,
        ];
    
        return response()->json(['status' => true, 'message' => 'Orders', 'data' => $data]);
    }


    public function getCompanyBookings()
    {
        if (auth('employees')->check()) {
            $employeeId = auth('employees')->id();
            $bookings = Booking::where('employee_id', $employeeId)->get();
        } else {
            $company = auth()->user();
            $bookings = $company->bookings; // حسب العلاقة المعرفة في الموديل
        }
    
        return $this->returnAllData(BookingResource::collection($bookings));
    }
    public function booking_papers(Request $request){
        $booking = Booking::where('booking_number', $request->booking_number)->first();
        $booking_papers = BookingPaper::where('booking_id', $booking->id)->get();
        return $this->returnAllData(BookingPaperResource::collection($booking_papers));
    }

}
