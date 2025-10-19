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
            // Begin transaction if needed
            DB::beginTransaction();
    
            // Insert booking container record
            $op = BookingContainer::insert(
                array_merge(
                    $request->except('_token', 'factory_id'),
                    ['booking_id' => $booking->id]
                )
            );
    
            // Commit the transaction
            DB::commit();
    
            // Handling Redirection
            $referer = session('booking_containers_edit_referrer') 
                ?? route('bookings.show', ['booking' => $booking->id]);
            session()->forget('booking_containers_edit_referrer');
    
            return redirect($referer)->with(['success' => __('alerts.updated_successfully')]);
        } catch (\Throwable $th) {
            // Roll back the transaction in case of an error
            DB::rollBack();
    
            // Log only the message and stack trace to avoid serialization issues
            \Illuminate\Support\Facades\Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
    
            // Redirect back with the error message
            return redirect()->back()->with(['error' => $th->getMessage()]);
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
            // Updating the booking_container with validated request data
            $invoiceTransportationRow = $booking_container->update($request->validated());
        
            // Fetching related models
            $bookingAgent = BookingContainerAgent::where('booking_container_id', $booking_container->id)->first();
            $dailyBooking = DailyBookingContainer::where("booking_container_id", $booking_container->id)->first();
        
            // Define the updates based on status
            $containerUpdates = [
                0 => [
                    'superagent_specification_approved' => 0,
                    'superagent_loading_approved' => 0,
                    'superagent_unloading_approved' => 0,
                    'booking_container_status' => 0
                ],
                1 => [
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 0,
                    'superagent_unloading_approved' => 0,
                    'booking_container_status' => 1
                ],
                2 => [
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 1,
                    'superagent_unloading_approved' => 0,
                    'booking_container_status' => 1
                ],
                3 => [
                    'superagent_specification_approved' => 1,
                    'superagent_loading_approved' => 1,
                    'superagent_unloading_approved' => 1,
                    'booking_container_status' => 1
                ]
            ];
        
            // Get the update data for the current status
            $statusUpdate = $containerUpdates[$request->status] ?? $containerUpdates[0];
        
            // Update booking_container, bookingAgent, and dailyBooking
            $booking_container->update([
                'superagent_specification_approved' => $statusUpdate['superagent_specification_approved'],
                'superagent_loading_approved' => $statusUpdate['superagent_loading_approved'],
                'superagent_unloading_approved' => $statusUpdate['superagent_unloading_approved']
            ]);
        
            // Check for null and update only if the model exists
            if ($bookingAgent) {
                $bookingAgent->update($statusUpdate);
            }
            if ($dailyBooking) {
                $dailyBooking->update($statusUpdate);
            }
        
            DB::commit();
        
            // Handling Redirection
            $referer = session('booking_containers_edit_referrer')
                ?? route('bookings.show', ['booking' => $booking_container->booking->id]);
            session()->forget('booking_containers_edit_referrer');
        
            return redirect($referer)->with(['success' => __('alerts.updated_successfully')]);
        } catch (\Throwable $th) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error($th);
            return redirect()->back()->with(['error' => $th]);
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
