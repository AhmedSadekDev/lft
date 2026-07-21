<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Superagent\allBookingContainerResource;
use App\Http\Resources\Api\Superagent\SpecificationBookingResource;
use App\Models\Booking;
use App\Models\BookingContainer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BookingContainerController extends Controller
{
    /**
     * GET /superagent/booking-containers/all
     * اختياري: ?per_page=10&page=1&stage_type=specification|loading|unloading
     */
    public function all(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', default: 100);
            $page    = (int) $request->get('page', 1);
            $stageType = $request->get('stage_type'); // فلتر حسب نوع المرحلة

            // نفس العلاقات اللي كانت بتتجاب في القديم عشان نتجنب N+1
            $with = [
                'booking.company',
                'booking.yard',
                'branch.factory',
                'container',
                'notes',
                'agents',
            ];

            // 1) specification (status=0 أو status=1 و superagent_specification_approved=0)
            $specItems = collect();
            if (!$stageType || $stageType === 'specification') {
                $specItems = BookingContainer::with($with)
                    ->select('*')
                    ->selectRaw("'specification' as stage_type")
                    ->where(function ($q) {
                        $q->where('status', 0)
                          ->orWhere(function($q2) {
                              $q2->where('status', 1)
                                 ->where('superagent_specification_approved', 0);
                          });
                    })
                    ->get();
            }

            // 2) loading
            $loadingItems = collect();
            if (!$stageType || $stageType === 'loading') {
                $loadingItems = BookingContainer::with($with)
                    ->select('*')
                    ->selectRaw("'loading' as stage_type")
                    ->where('superagent_specification_approved', 1)
                    ->where('superagent_loading_approved', 0)
                    ->where('superagent_unloading_approved', 0)
                    ->get();
            }

            // 3) unloading
            $unloadingItems = collect();
            if (!$stageType || $stageType === 'unloading') {
                $unloadingItems = BookingContainer::with($with)
                    ->select('*')
                    ->selectRaw("'unloading' as stage_type")
                    ->where('superagent_specification_approved', 1)
                    ->where('superagent_loading_approved', 1)
                    ->where('superagent_unloading_approved', 0)
                    ->get();
            }

            // دمج مع أولوية أعلى مرحلة (unloading > loading > specification) + إزالة التكرار + ترتيب
            // ترتيب حسب id تنازلي (الأحدث أولاً) مثل الداش بورد
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
    public function specification(Request $request)
    {
        try {
            $request->merge(['stage' => 'specification']);
            $bookings = Booking::has('bookingContainers') // Ensure there are containers
                ->whereHas('bookingContainers', function ($qc) {
                    // الحاويات التي تحتاج موافقة على التخصيص:
                    // 1. status = 0 (لم يتم التخصيص بعد)
                    // 2. status = 1 و superagent_specification_approved = 0 (تم التخصيص لكن لم يتم الموافقة)
                    $qc->where(function($q) {
                        $q->where('status', 0)
                          ->orWhere(function($q2) {
                              $q2->where('status', 1)
                                 ->where('superagent_specification_approved', 0);
                          });
                    });
                })
                ->with(['bookingContainers'])
                ->join('booking_containers', 'bookings.id', '=', 'booking_containers.booking_id')
                ->select('bookings.*')
                ->selectRaw('MIN(booking_containers.arrival_date) as min_arrival_date')
                ->groupBy('bookings.id')
                ->orderBy('bookings.id', 'desc')
                ->paginate(100);
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);


            return $this->returnAllData($data, __('alerts.success'));

        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }

    public function loading(Request $request)
    {
        try {
            $request->merge(['stage' => 'loading']);
            $bookings = Booking::has('bookingContainers') // Ensure there are containers
                ->whereHas('bookingContainers', function ($qc) {
                    $qc->whereIn('status', [0, 1, 2])
                    ->where('superagent_unloading_approved', 0)->where('superagent_specification_approved', 1)->where('superagent_loading_approved', 0);
                })
                ->with(['bookingContainers'])
                ->join('booking_containers', 'bookings.id', '=', 'booking_containers.booking_id')
                ->select('bookings.*')
                ->selectRaw('MIN(booking_containers.arrival_date) as min_arrival_date')
                ->groupBy('bookings.id')
                ->orderBy('bookings.id', 'desc')
                ->paginate(100);
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {

            return $this->returnError(500, $ex->getMessage());
        }
    }

    public function unloading(Request $request)
    {
        try {
            $request->merge(['stage' => 'unloading']);
            $bookings = Booking::has('bookingContainers') // Ensure there are containers
                ->whereHas('bookingContainers', function ($qc) {
                    $qc->whereIn('status', [0, 1, 2, 3])
                    ->where('superagent_unloading_approved', 0)->where('superagent_specification_approved', 1)->where('superagent_loading_approved', 1);
                })
                ->with(['bookingContainers'])
                ->join('booking_containers', 'bookings.id', '=', 'booking_containers.booking_id')
                ->select('bookings.*')
                ->selectRaw('MIN(booking_containers.arrival_date) as min_arrival_date')
                ->groupBy('bookings.id')
                ->orderBy('bookings.id', 'desc')
                ->paginate(100);
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }

    public function details(Request $request)
    {
        try {
            $bookingContainerId = $request->get('booking_container_id');
            $bookingId = null;

            if ($bookingContainerId) {
                $container = BookingContainer::find($bookingContainerId);
                if (!$container) {
                    return $this->returnError(404, __('alerts.not_found') ?? 'Booking container not found');
                }
                $bookingId = $container->booking_id;
            } else {
                $bookingId = $request->get('booking_id') ?? $request->get('id');
            }

            if (!$bookingId) {
                return $this->returnError(400, 'booking_id or booking_container_id is required');
            }

            $booking = Booking::find($bookingId);
            if (!$booking) {
                return $this->returnError(404, __('alerts.not_found') ?? 'Booking not found');
            }

            // Map type_id to stage if provided
            if ($request->has('type_id')) {
                $typeId = $request->get('type_id');
                if ($typeId == 0) {
                    $request->merge(['stage' => 'specification']);
                } elseif ($typeId == 1) {
                    $request->merge(['stage' => 'loading']);
                } elseif ($typeId == 2) {
                    $request->merge(['stage' => 'unloading']);
                }
            }

            // Eager load booking containers and relations to prevent N+1 queries
            $booking->load([
                'bookingContainers.booking.company',
                'bookingContainers.booking.yard',
                'bookingContainers.branch.factory',
                'bookingContainers.container',
                'bookingContainers.notes',
                'bookingContainers.agents',
                'bookingContainers.delivery_policies.money_transfer',
                'bookingContainers.bookingPapers.image',
            ]);

            $data = new SpecificationBookingResource($booking);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError(500, $ex->getMessage());
        }
    }
}

