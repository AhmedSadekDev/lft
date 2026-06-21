<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\CarResource;
use App\Http\Resources\Api\Agent\YardResource;
use App\Http\Resources\Api\Superagent\BookingResource;
use App\Http\Resources\Api\Superagent\YardBookingResource;
use App\Models\Booking;
use App\Models\Superagent;
use App\Models\Yard;
use Illuminate\Http\Request;

class YardController extends Controller
{
    public function fetch_yards()
    {
        try {

            $yards = Yard::query()->withActiveYardContainersCount()->get();

            $data = YardResource::collection($yards);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
    public function fetch_active_yards()
    {
        try {

            $yards = Yard::query()
                ->withActiveYardContainersCount()
                ->havingRaw('active_containers_count > 0')
                ->get();

            $data = YardResource::collection($yards);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
    public function fetch_yard_bookings(Request $request)
    {
        try {
            $request->validate([
                'yard_id' => 'required|exists:yards,id'
            ]);

            $superagent = auth()->guard('superagent')->user();
            /** @var Superagent $superagent */

            // Get all bookings that belong to the specified yard
            // YardBookingResource will show all containers in the booking
            $bookings = Booking::where('yard_id', $request->yard_id)
                ->whereHas('bookingContainers', static function ($q) {
                    $q->whereIn('booking_containers.status', [0, 1]);
                })
                ->with([
                    'bookingContainers.branch.factory',
                    'bookingContainers.container',
                    'bookingContainers.booking.company',
                    'bookingContainers.booking.yard',
                    'company',
                    'yard',
                    'factory'
                ])
                ->orderBy('id', 'desc')
                ->get();

            $data = YardBookingResource::collection($bookings);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
}
