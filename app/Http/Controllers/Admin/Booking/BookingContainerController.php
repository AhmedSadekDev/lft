<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingContainerRequest;
use App\Mappers\BookingContainerStatusMapper;
use App\Models\Booking;
use App\Models\CitiesAndRegions;
use App\Models\Factory;
use App\Models\BookingContainer;
use App\Models\BookingContainerAgent;
use App\Models\Container;
use App\Models\DailyBookingContainer;
use App\Models\Yard;
use Illuminate\Support\Facades\DB;

class BookingContainerController extends Controller
{

    public function getCreateFormInputs(Booking $booking): array
    {
        // saving the caller page, as this page can be called from different sources
        $referer = request()->server('HTTP_REFERER');
        session(['booking_containers_edit_referrer' => $referer]);

        // sending view data
        $company_prices = $booking
            ->company
            ->transportations()
            ->select(
                "container_id",
                "departure_id",
                "aging_id",
                "loading_id",
                "price"
            )
            ->get()
            ->groupBy('container_id')
            ->toArray();

        $factories = Factory::whereHas('branches')
            ->with('branches')
            ->get();

        $factory_branches = [];
        foreach ($factories as $factory)
            foreach ($factory->branches as $branch)
                $factory_branches[$factory->id][$branch->id] = $branch->name;

        return [
            'factories' => Factory::whereHas('branches')->pluck('name', 'id'),
            'factory_branches' => $factory_branches,
            'cities_and_regions' => CitiesAndRegions::pluck('title', 'id'),
            'company_prices' => $company_prices,
            'container_types' => Container::all()->pluck('full_name', 'id'),
            'available_statuses' => BookingContainerStatusMapper::getAll('ar'),
            'yards' => Yard::all()->pluck('title', 'id')
        ];
    }

    public function create(Booking $booking)
    {
        $inputs = array_merge(
            $this->getCreateFormInputs($booking),
            [
                'method'    => 'POST',
                'action'    => route(
                    'booking-containers.store',
                    ['booking' => $booking->id]
                )
            ]
        );

        return view(
            'admin.bookings.booking-containers.create',
            $inputs
        );
    }
    public function store(
        BookingContainerRequest $request,
        Booking $booking
    ) {
        try {
            $op = BookingContainer::inseFrt(
                array_merge(
                    $request->except('_token', 'factory_id'),
                    ['booking_id' => $booking->id]
                )
            );

            // Handling Redirection
            $referer =  session('booking_containers_edit_referrer')
                ?? route('bookings.show', ['booking' => $booking->id]);
            session()->forget('booking_containers_edit_referrer');

            return redirect($referer)->with(['success' => __('alerts.updated_successfully')]);
        } catch (\Throwable $th) {
            DB::rollBack();
            // throw $th;
            \Illuminate\Support\Facades\Log::error($th);
            return redirect()
                ->back()
                ->with(['error' => $th]);
        }
    }

    public function edit(
        BookingContainer $booking_container
    ) {
        $booking_container->factory_id = $booking_container->branch->factory_id;
        $inputs = array_merge(
            $this->getCreateFormInputs($booking_container->booking),
            [
                'branches' => $booking_container
                    ->branch
                    ->factory
                    ->branches
                    ->pluck('name', 'id')
                    ->toArray(),
                'booking_container' => $booking_container,
                'method'    => 'PUT',
                'action'    => route(
                    'booking-containers.update',
                    ['booking_container' => $booking_container->id]
                )
            ]
        );

        return view(
            'admin.bookings.booking-containers.edit',
            $inputs
        );
    }

    public function update(
        BookingContainerRequest $request,
        BookingContainer $booking_container
    ) {

        DB::beginTransaction();
        try {
            $invoiceTransportationRow = $booking_container
                ->update(
                    $request->validated()
                );
            $bookingAgent = BookingContainerAgent::where('booking_container_id', $booking_container->id)->first();
            $dailyBooking = DailyBookingContainer::where("booking_container_id", $booking_container->id)->first();


            if ($request->status == 0) {

                $booking_container->update([
                    'superagent_specification_approved' => 0,
                    'superagent_loading_approved' => 0,
                    'superagent_unloading_approved' => 0,
                ]);

                $bookingAgent->update([
                    'booking_container_status' => 0,
                    'superagent_specification_approved' => 0,
                    'superagent_loading_approved' => 0,
                    'superagent_unloading_approved' => 0,
                ]);

                $dailyBooking->update([
                    'booking_container_status' => 0,
                    'superagent_specification_approved' => 0,
                    'superagent_loading_approved' => 0,
                    'superagent_unloading_approved' => 0,
                ]);
            } elseif ($request->status == 1) {

                $booking_container->update([
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 0,
                    'superagent_unloading_approved' => 0,
                ]);

                $bookingAgent->update([
                    'booking_container_status' => 1,
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 0,
                    'superagent_unloading_approved' => 0,
                ]);

                $dailyBooking->update([
                    'booking_container_status' => 1,
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 0,
                    'superagent_unloading_approved' => 0,
                ]);
            } elseif ($request->status == 2) {

                $booking_container->update([
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 1,
                    'superagent_unloading_approved' => 0,
                ]);

                $bookingAgent->update([
                    'booking_container_status' => 1,
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 1,
                    'superagent_unloading_approved' => 0,
                ]);

                $dailyBooking->update([
                    'booking_container_status' => 1,
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 1,
                    'superagent_unloading_approved' => 0,
                ]);
            } else {

                $booking_container->update([
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 1,
                    'superagent_unloading_approved' => 1,
                ]);

                $bookingAgent->update([
                    'booking_container_status' => 1,
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 1,
                    'superagent_unloading_approved' => 1,
                ]);

                $dailyBooking->update([
                    'booking_container_status' => 1,
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 1,
                    'superagent_unloading_approved' => 1,
                ]);
            }


            DB::commit();

            // Handling Redirection
            $referer =  session('booking_containers_edit_referrer')
                ?? route('bookings.show', ['booking' => $booking_container->booking->id]);
            session()->forget('booking_containers_edit_referrer');

            return redirect($referer)->with(['success' => __('alerts.updated_successfully')]);
        } catch (\Throwable $th) {
            DB::rollBack();
            // throw $th;
            \Illuminate\Support\Facades\Log::error($th);
            return redirect()
                ->back()
                ->with(['error' => $th]);
        }
    }

    public function destroy(BookingContainer $booking_container)
    {
        $booking_container->delete();
        return response()->json([
            'status' => true,
            'message' => __('alerts.added_successfully')
        ], 200);
    }
}
