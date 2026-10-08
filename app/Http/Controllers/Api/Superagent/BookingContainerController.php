<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Superagent\MissionBookingResource;
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
            $stageType = $request->get('stage_type');

            $condition = $this->allStageContainerConstraints($stageType);

            $query = Booking::query()
                ->withoutInvoice()
                ->whereHas('bookingContainers', $condition)
                ->with([
                    'bookingContainers' => function ($cQuery) use ($condition) {
                        $condition($cQuery);
                        $cQuery->select('*')
                            ->selectRaw("
                                CASE
                                    WHEN superagent_specification_approved = 1 AND superagent_loading_approved = 1 AND superagent_unloading_approved = 0 THEN 'unloading'
                                    WHEN superagent_specification_approved = 1 AND is_in_loading = 1 AND superagent_loading_approved = 0 AND superagent_unloading_approved = 0 THEN 'loading'
                                    WHEN superagent_specification_approved = 1 AND is_in_loading = 0 AND superagent_loading_approved = 0 AND superagent_unloading_approved = 0 THEN 'waiting'
                                    WHEN status = 0 OR (status = 1 AND superagent_specification_approved = 0) THEN 'specification'
                                    ELSE NULL
                                END AS stage_type
                            ")
                            ->with([
                                'booking.company',
                                'booking.factory',
                                'booking.yard',
                                'branch.factory',
                                'container',
                                'notes',
                                'agents',
                            ])
                            ->orderByDesc('id');
                    }
                ])
                ->orderByDesc('id');

            $paginator = $query->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

            $data = MissionBookingResource::collection($paginator)
                ->response()
                ->getData(true);

            return $this->returnAllData($data, __('alerts.success'));

        } catch (\Throwable $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }

    private function allStageContainerConstraints(?string $stageType): \Closure
    {
        return match ($stageType) {
            'specification' => function ($q) {
                $q->where(function ($sub) {
                    $sub->where('status', 0)
                        ->orWhere(function ($sub2) {
                            $sub2->where('status', 1)
                                 ->where('superagent_specification_approved', 0);
                        });
                });
            },
            'waiting' => function ($q) {
                $q->where('superagent_specification_approved', 1)
                  ->where('is_in_loading', 0)
                  ->where('superagent_loading_approved', 0)
                  ->where('superagent_unloading_approved', 0);
            },
            'loading' => function ($q) {
                $q->where('superagent_specification_approved', 1)
                  ->where('is_in_loading', 1)
                  ->where('superagent_loading_approved', 0)
                  ->where('superagent_unloading_approved', 0);
            },
            'unloading' => function ($q) {
                $q->where('superagent_specification_approved', 1)
                  ->where('superagent_loading_approved', 1)
                  ->where('superagent_unloading_approved', 0);
            },
            default => function ($q) {
                $q->where(function ($orQ) {
                    $orQ->where(function ($sub) {
                        $sub->where('status', 0)
                            ->orWhere(function ($sub2) {
                                $sub2->where('status', 1)
                                     ->where('superagent_specification_approved', 0);
                            });
                    })
                    ->orWhere(function ($sub) {
                        $sub->where('superagent_specification_approved', 1)
                            ->where('is_in_loading', 0)
                            ->where('superagent_loading_approved', 0)
                            ->where('superagent_unloading_approved', 0);
                    })
                    ->orWhere(function ($sub) {
                        $sub->where('superagent_specification_approved', 1)
                            ->where('is_in_loading', 1)
                            ->where('superagent_loading_approved', 0)
                            ->where('superagent_unloading_approved', 0);
                    })
                    ->orWhere(function ($sub) {
                        $sub->where('superagent_specification_approved', 1)
                            ->where('superagent_loading_approved', 1)
                            ->where('superagent_unloading_approved', 0);
                    });
                });
            },
        };
    }

    private function stageContainerConstraints(string $stage): \Closure
    {
        return match ($stage) {
            'specification' => function ($q) {
                $q->where(function ($query) {
                    $query->where('status', 0)
                        ->orWhere(function ($q2) {
                            $q2->where('status', 1)
                                ->where('superagent_specification_approved', 0);
                        });
                });
            },
            'waiting' => function ($q) {
                $q->where('superagent_specification_approved', 1)
                    ->where('is_in_loading', 0)
                    ->where('superagent_loading_approved', 0)
                    ->where('superagent_unloading_approved', 0);
            },
            'loading' => function ($q) {
                $q->where('superagent_specification_approved', 1)
                    ->where('is_in_loading', 1)
                    ->where('superagent_loading_approved', 0)
                    ->where('superagent_unloading_approved', 0);
            },
            'unloading' => function ($q) {
                $q->where('superagent_specification_approved', 1)
                    ->where('superagent_loading_approved', 1)
                    ->where('superagent_unloading_approved', 0);
            },
            default => fn ($q) => $q,
        };
    }

    /**
     * Paginate bookings with all their containers matching the requested stage.
     */
    private function paginateBookingsForStage(Request $request, string $stage)
    {
        $request->merge(['stage' => $stage]);
        $constrain = $this->stageContainerConstraints($stage);

        return Booking::query()
            ->withoutInvoice()
            ->whereHas('bookingContainers', $constrain)
            ->with([
                'bookingContainers' => function ($query) use ($constrain) {
                    $constrain($query);
                    $query->orderByDesc('id');
                },
                'bookingContainers.booking.company',
                'bookingContainers.booking.factory',
                'bookingContainers.booking.yard',
                'bookingContainers.branch.factory',
                'bookingContainers.container',
                'bookingContainers.notes',
                'bookingContainers.agents',
                'bookingContainers.delivery_policies.money_transfer',
                'bookingContainers.bookingPapers.image',
            ])
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 100));
    }

    public function specification(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, 'specification');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }

    /**
     * GET /superagent/booking/waiting
     * قائمة الانتظار (بعد التخصيص وقبل النقل للتحميل)
     */
    public function waiting(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, 'waiting');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }

    /**
     * POST /superagent/booking/move_to_loading
     * نقل طلبات/حاويات محددة من قائمة الانتظار إلى قائمة التحميل
     */
    public function move_to_loading(Request $request)
    {
        try {
            $bookingIds = $request->get('booking_ids') ?? $request->get('booking_id');
            $containerIds = $request->get('booking_container_ids') ?? $request->get('booking_container_id');
            $toLoading = $request->boolean('to_loading', true);

            $bookingIds = is_array($bookingIds) ? array_filter($bookingIds) : ($bookingIds ? [$bookingIds] : []);
            $containerIds = is_array($containerIds) ? array_filter($containerIds) : ($containerIds ? [$containerIds] : []);

            if (empty($bookingIds) && empty($containerIds)) {
                return $this->returnError(400, 'يجب تحديد معرفات الطلبات أو الحاويات (booking_ids or booking_container_ids)');
            }

            $query = BookingContainer::query()
                ->withoutInvoicedBooking()
                ->where('superagent_specification_approved', 1)
                ->where('superagent_loading_approved', 0)
                ->where('superagent_unloading_approved', 0);

            // الحاوية هي وحدة العمل: لو اتبعت حاويات محددة انقلهم فقط
            if (!empty($containerIds)) {
                $query->whereIn('id', $containerIds);
            } elseif (!empty($bookingIds)) {
                // توافق قديم: نقل كل حاويات الانتظار في الطلب فقط
                $query->whereIn('booking_id', $bookingIds)->where('is_in_loading', $toLoading ? 0 : 1);
            } else {
                return $this->returnError(400, 'يجب تحديد معرفات الحاويات (booking_container_ids)');
            }

            // عند النقل للتحميل تأكد أنها في الانتظار؛ والعكس عند الإرجاع
            if ($toLoading) {
                $query->where('is_in_loading', 0);
            } else {
                $query->where('is_in_loading', 1);
            }

            $ids = $query->pluck('id')->all();
            if (empty($ids)) {
                return $this->returnError(404, 'لا توجد حاويات مطابقة للنقل');
            }

            app(\App\Services\ContainerStageService::class)->moveToLoading($ids, $toLoading);

            $message = $toLoading
                ? 'تم نقل الحاويات المحددة إلى قائمة التحميل بنجاح'
                : 'تمت إعادة الحاويات المحددة إلى قائمة الانتظار';

            return $this->returnResponseSuccessMessage($message);
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }

    public function loading(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, 'loading');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }

    public function unloading(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, 'unloading');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
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
                } elseif ($typeId == 3) {
                    $request->merge(['stage' => 'waiting']);
                }
            }

            // Eager load booking containers and relations to prevent N+1 queries
            $booking->load([
                'factory',
                'bookingContainers' => function ($query) use ($bookingContainerId) {
                    if ($bookingContainerId) {
                        $query->whereKey($bookingContainerId);
                    }
                },
                'bookingContainers.booking.company',
                'bookingContainers.booking.factory',
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
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }
}

