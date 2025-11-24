<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\CarResource;
use App\Http\Resources\Api\Agent\YardResource;
use App\Http\Resources\Api\Superagent\BookingResource;
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

    public function fetch_yard_bookings(Request $request)
    {
        try {
            $request->validate([
                'yard_id' => 'required|exists:yards,id'
            ]);

            $superagent = auth()->guard('superagent')->user();
            /** @var Superagent $superagent */

            // Get booking container IDs assigned to this superagent today
            $superagent_booking_containers = $superagent->superagent_booking_containers()
                ->wherePivot("created_at", ">=", now()->startOfDay())
                ->wherePivot("created_at", "<=", now()->endOfDay())
                ->get();

            // Get bookings that belong to the specified yard and have containers assigned to this superagent
            $bookings = Booking::where('yard_id', $request->yard_id)
                ->whereHas('bookingContainers', function ($query) use ($superagent_booking_containers) {
                    $query->whereIn('booking_containers.id', $superagent_booking_containers->pluck('id')->toArray());
                })
                ->with(['bookingContainers' => function ($query) use ($superagent_booking_containers) {
                    $query->whereIn('booking_containers.id', $superagent_booking_containers->pluck('id')->toArray());
                }])
                ->orderBy('id', 'desc')
                ->get();

            $data = BookingResource::collection($bookings);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(500, $Exception->getMessage());
        }
    }
}
