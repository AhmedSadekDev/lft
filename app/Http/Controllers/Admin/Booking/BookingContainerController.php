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

        // قائمة أسعار النقل للشركة (مجمّعة حسب نوع الحاوية) — بنية ثابتة لـ JSON/JS
        $company_prices = [];
        if ($booking->company) {
            $company_prices = $booking->company
                ->transportations()
                ->select(
                    'container_id',
                    'departure_id',
                    'aging_id',
                    'loading_id',
                    'price'
                )
                ->get()
                ->groupBy('container_id')
                ->map(fn ($rows) => $rows->map(fn ($row) => [
                    'departure_id' => $row->departure_id,
                    'loading_id' => $row->loading_id,
                    'aging_id' => $row->aging_id,
                    'price' => $row->price,
                ])->values())
                ->toArray();
        }

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

            // Get validated data and exclude factory_id (not stored in booking_containers table)
            $validatedData = $request->validated();
            unset($validatedData['factory_id']);

            // Insert booking container record
            $op = BookingContainer::create(
                array_merge(
                    $validatedData,
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
            // Get validated data and exclude factory_id (not stored in booking_containers table)
            $validatedData = $request->validated();
            unset($validatedData['factory_id']);

            $booking_container = BookingContainer::lockForUpdate()->findOrFail($booking_container->id);
            $targetStatus = isset($validatedData['status']) ? (int) $validatedData['status'] : (int) $booking_container->status;
            if ($targetStatus < (int) $booking_container->status) {
                app(\App\Services\ContainerStageService::class)->rewind($booking_container->id, $targetStatus);
                $booking_container->refresh();
            }
            unset($validatedData['status'], $validatedData['superagent_specification_approved'], $validatedData['superagent_loading_approved'], $validatedData['superagent_unloading_approved']);
            $oldStatus = (int) $booking_container->status;
            $booking_container->update($validatedData);
            for ($type = $oldStatus; $type < $targetStatus; $type++) {
                app(\App\Services\ContainerStageService::class)->approve($booking_container->id, $type);
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
