<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Superagent\allBookingContainerResource;
use App\Http\Resources\Api\Superagent\BookingContainerResource;
use App\Http\Resources\Api\Superagent\BookingResource;
use App\Http\Resources\Api\Superagent\SpecificationBookingResource;
use App\Models\Booking;
use App\Models\BookingContainer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BookingContainerController extends Controller
{
    /**
     * GET /superagent/booking-containers/all
     * اختياري: ?per_page=10&page=1
     */
    public function all(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            $page    = (int) $request->get('page', 1);

            // نفس العلاقات اللي كانت بتتجاب في القديم عشان نتجنب N+1
            $with = [
                'booking.company',
                'booking.yard',
                'branch.factory',
                'container',
                'notes',
                'agents',
            ];

            // 1) specification (status=0 أو superagent_specification_approved=0)
            $specItems = BookingContainer::with($with)
                ->select('*')
                ->selectRaw("'specification' as stage_type")
                ->where(function ($q) {
                    $q->where('status', 0)
                      ->orWhere('superagent_specification_approved', 0);
                })
                ->get();

            // 2) loading
            $loadingItems = BookingContainer::with($with)
                ->select('*')
                ->selectRaw("'loading' as stage_type")
                ->where('superagent_specification_approved', 1)
                ->where('superagent_loading_approved', 0)
                ->where('superagent_unloading_approved', 0)
                ->get();

            // 3) unloading
            $unloadingItems = BookingContainer::with($with)
                ->select('*')
                ->selectRaw("'unloading' as stage_type")
                ->where('superagent_specification_approved', 1)
                ->where('superagent_loading_approved', 1)
                ->where('superagent_unloading_approved', 0)
                ->get();

            // دمج مع أولوية أعلى مرحلة (unloading > loading > specification) + إزالة التكرار + ترتيب
            $merged = $unloadingItems
                ->merge($loadingItems)
                ->merge($specItems)
                ->unique('id')
                ->sortByDesc('id')
                ->values();

            // Manual pagination بنفس فورمات لارافيل
            $total     = $merged->count();
            $results   = $merged->forPage($page, $perPage)->values();
            $paginator = new LengthAwarePaginator(
                $results,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $data = allBookingContainerResource::collection($paginator)
                ->response()
                ->getData(true);

            return $this->returnAllData($data, __('alerts.success'));

        } catch (\Throwable $ex) {
            return $this->returnError(500, $ex->getMessage());
        }
    }
    public function specification()
    {
        try {

            $bookings = Booking::has('bookingContainers') // Ensure there are containers
                ->whereHas('bookingContainers', function ($qc) {
                    $qc->whereIn('status', [0, 1])
                       ->orWhere('superagent_specification_approved', 0);
                })
                ->orderBy('id', 'desc')
                ->paginate(10);
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);


            return $this->returnAllData($data, __('alerts.success'));

        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }

    public function loading()
    {
        try {
            $bookings = Booking::has('bookingContainers') // Ensure there are containers
                ->whereHas('bookingContainers', function ($qc) {
                    $qc->whereIn('status', [0, 1, 2])
                       ->where('superagent_unloading_approved', 0)->where('superagent_specification_approved', 1)->where('superagent_loading_approved', 0);
                })
                ->orderBy('id', 'desc')
                ->paginate(10);
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {

            return $this->returnError(500, $ex->getMessage());
        }
    }

    public function unloading()
    {
        try {
            $bookings = Booking::has('bookingContainers') // Ensure there are containers
                ->whereHas('bookingContainers', function ($qc) {
                    $qc->whereIn('status', [0, 1, 2, 3])
                       ->where('superagent_unloading_approved', 0)->where('superagent_specification_approved', 1)->where('superagent_loading_approved', 1);
                })
                ->orderBy('id', 'desc')
                ->paginate(10);
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }
}

