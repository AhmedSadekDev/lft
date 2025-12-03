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

            $yards = Yard::get();

            $data = YardResource::collection($yards);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
    public function fetch_active_yards()
    {
        try {

            $yards = Yard::select('yards.*')
                ->selectSub(function ($query) {
                    $query->selectRaw('COUNT(*)')
                        ->from('booking_containers')
                        ->join('bookings', 'booking_containers.booking_id', '=', 'bookings.id')
                        ->whereColumn('bookings.yard_id', 'yards.id')
                        ->where('booking_containers.status', '!=', 3);
                }, 'active_containers_count')
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
