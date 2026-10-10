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
        $booking->load('bookingContainers.container');
        $containers = ContainerResource::collection($booking->bookingContainers);
    
        return response()->json(['status' => true, 'message' => 'Orders', 'data' => $containers]);
    }

    public function getContainerDetails(BookingContainer $booking_container)
    {
        $booking = $booking_container->booking()->with([
            'bookingContainers.container',
            'last_movements',
            'employee',
            'shippingAgent',
        ])->first();
        $booking_container->load(['branch', 'last_movement']);
        $data = [
            'bookingDetails' => new BookingResource($booking, $booking_container->id),
            'factoryDetails' => $booking_container->branch ? new FactoryResource($booking_container->branch) : null,
            'lastMovements'  => $booking_container->last_movement,
        ];
    
        return response()->json(['status' => true, 'message' => 'Orders', 'data' => $data]);
    }


    public function getCompanyBookings()
    {
        // relations read by BookingResource / ContainerResource
        $relations = ['bookingContainers.container', 'bookingContainers.branch', 'last_movements', 'employee', 'shippingAgent'];
        $perPage = (int) request()->get('per_page', 20);
        $page = (int) request()->get('page', 1);

        if (auth('employees')->check()) {
            $employeeId = auth('employees')->id();
            $query = Booking::with($relations)->where('employee_id', $employeeId);
        } else {
            $company = auth('api')->user() ?? auth()->user();
            $query = $company ? $company->bookings()->with($relations) : Booking::query()->whereRaw('1=0');
        }

        $bookings = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
        $data = BookingResource::collection($bookings->items());
        $pagination = [
            'total' => $bookings->total(),
            'per_page' => $bookings->perPage(),
            'current_page' => $bookings->currentPage(),
            'total_pages' => $bookings->lastPage(),
        ];

        return response()->json([
            'status' => true,
            'errNum' => "0000",
            'message' => '',
            'data' => $data,
            'pagination' => $pagination,
        ], 200);
    }
    public function booking_papers(Request $request)
    {
        $booking = Booking::where('booking_number', $request->booking_number)->first();
        if (is_null($booking)) {
            return response()->json(['status' => false, 'errNum' => '404', 'message' => __('admin.not_found'), 'data' => []], 200);
        }
        $booking_papers = BookingPaper::with('image')->where('booking_id', $booking->id)->get();
        return $this->returnAllData(BookingPaperResource::collection($booking_papers));
    }

}
