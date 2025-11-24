<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\BookingResource;
use App\Http\Resources\Api\Agent\CarResource;
use App\Http\Resources\Api\Agent\YardResource;
use App\Models\Agent;
use App\Models\Booking;
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

            $agent = auth()->guard('agent')->user();
            /** @var Agent $agent */

            // Get booking container IDs assigned to this agent today
            $agent_booking_containers = $agent->agent_booking_containers()
                ->wherePivot("created_at", ">=", now()->startOfDay())
                ->wherePivot("created_at", "<=", now()->endOfDay())
                ->get();

            // Get bookings that belong to the specified yard and have containers assigned to this agent
            $bookings = Booking::where('yard_id', $request->yard_id)
                ->whereHas('bookingContainers', function ($query) use ($agent_booking_containers) {
                    $query->whereIn('booking_containers.id', $agent_booking_containers->pluck('id')->toArray());
                })
                ->with(['bookingContainers' => function ($query) use ($agent_booking_containers) {
                    $query->whereIn('booking_containers.id', $agent_booking_containers->pluck('id')->toArray());
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
