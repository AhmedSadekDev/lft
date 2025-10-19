<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\LoadingBookingRequest;
use App\Http\Requests\Api\Agent\SpecificationBookingYardRequest;
use App\Http\Resources\Api\Superagent\BookingContainerResource;
use App\Http\Resources\Api\Superagent\LoadingYardResource;
use App\Http\Resources\Api\Superagent\SpecificationShippingAgentResource;
use App\Http\Resources\Api\Superagent\SuperagentAssignmentResource;
use App\Http\Resources\Api\Superagent\UnloadingShippingAgentResource;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\BookingPaper;
use App\Models\Image;
use App\Models\shippingAgent;
use App\Models\Yard;
use Illuminate\Http\Request;

class ShippingAgentController extends Controller
{
    public function all()
{
    try {
        $superagent = auth()->guard('superagent')->user();

        // حاوية الربط اليومية مع السوبر إيجنت
        $superagent_booking_containers = $superagent->superagent_booking_containers()
            ->wherePivot('created_at', '>=', now()->startOfDay())
            ->wherePivot('created_at', '<=', now()->endOfDay())
            ->get();

        /* =======================
         * 1) SPECIFICATION
         * ======================= */
        $spec_shipping_agent_ids = Booking::has('shippingAgent')
            ->whereHas('bookingContainers', function ($query) use ($superagent_booking_containers) {
                $query->where(function ($q) {
                        $q->where('booking_containers.status', 0)
                          ->orWhere('booking_containers.superagent_specification_approved', 0);
                    })
                    ->whereIn('booking_containers.id', $superagent_booking_containers->pluck('id')->toArray());
            })
            ->orderBy('id', 'desc')
            ->pluck('shipping_agent_id')
            ->toArray();

        $spec_shipping_agents = ShippingAgent::whereIn('id', $spec_shipping_agent_ids)->get();

        $specification = $spec_shipping_agents->map(function ($agent) use ($superagent_booking_containers) {
            // نفس فلترة Resource القديمة
            $bookings = $agent->bookings()
                ->whereHas('bookingContainers', function ($q) use ($superagent_booking_containers) {
                    $q->where(function ($qq) {
                            $qq->where('booking_containers.status', 0)
                               ->orWhere('booking_containers.superagent_specification_approved', 0);
                        })
                      ->whereIn('booking_containers.id', $superagent_booking_containers->pluck('id')->toArray());
                })
                ->get();

            return [
                'type'       => 'specification',
                'entity'     => 'shipping_agent',
                'id'         => $agent->id,
                'title'      => $agent->title ?? '',
                'items_type' => 'bookings',
                'items'      => \App\Http\Resources\Api\Superagent\BookingResource::collection($bookings),
            ];
        });

        /* =======================
         * 2) LOADING
         * ======================= */
        $yards = Yard::whereHas('bookingContainers', function ($qc) use ($superagent_booking_containers) {
                $qc->where('booking_containers.superagent_loading_approved', 0)
                   ->where('booking_containers.superagent_specification_approved', 1)
                   ->whereIn('booking_containers.id', $superagent_booking_containers->pluck('id')->toArray());
            })
            ->orderBy('id', 'desc')
            ->get();

        $loading = $yards->map(function ($yard) {
            $containers = $yard->bookingContainers()
                ->where('superagent_loading_approved', 0)
                ->where('superagent_specification_approved', 1)
                ->get();

            return [
                'type'       => 'loading',
                'entity'     => 'yard',
                'id'         => $yard->id,
                'title'      => $yard->title ?? '',
                'items_type' => 'booking_containers',
                'items'      => \App\Http\Resources\Api\Superagent\BookingContainerResource::collection($containers),
            ];
        });

        /* =======================
         * 3) UNLOADING
         * ======================= */
        $unload_shipping_agent_ids = Booking::has('shippingAgent')
            ->whereHas('bookingContainers', function ($qc) {
                $qc->where('booking_containers.superagent_loading_approved', 1)
                   ->where('booking_containers.superagent_specification_approved', 1)
                   ->where('booking_containers.superagent_unloading_approved', 0);
            })
            ->orderBy('id', 'desc')
            ->pluck('shipping_agent_id')
            ->toArray();

        $unload_shipping_agents = ShippingAgent::whereIn('id', $unload_shipping_agent_ids)->get();

        $unloading = $unload_shipping_agents->map(function ($agent) {
            $containers = $agent->bookingContainers()
                ->where('superagent_loading_approved', 1)
                ->where('superagent_specification_approved', 1)
                ->where('superagent_unloading_approved', 0)
                ->get();

            return [
                'type'       => 'unloading',
                'entity'     => 'shipping_agent',
                'id'         => $agent->id,
                'title'      => $agent->title ?? '',
                'items_type' => 'booking_containers',
                'items'      => \App\Http\Resources\Api\Superagent\BookingContainerResource::collection($containers),
            ];
        });

        // دمج الكل في Collection واحدة
        $merged = $specification->concat($loading)->concat($unloading)->values();

        // Resource موحّد لكل عنصر
        $data = SuperagentAssignmentResource::collection($merged);

        return $this->returnAllData($data, __('alerts.success'));

    } catch (\Exception $ex) {
        return $this->returnError(500, $ex->getMessage());
    }
}
    public function specification_assignments()
    {
        try {
            $superagent = auth()->guard('superagent')->user();
    
            // Get all booking containers for the superagent created today
            $superagent_booking_containers = $superagent->superagent_booking_containers()
                ->wherePivot('created_at', '>=', now()->startOfDay())
                ->wherePivot('created_at', '<=', now()->endOfDay())
                ->get();
                
                
    
            // Retrieve shipping agent IDs where booking containers have status 0 and match superagent booking container IDs
            $shipping_agent_ids = Booking::has('shippingAgent')
                ->whereHas('bookingContainers', function ($query) use ($superagent_booking_containers) {
                    $query->where(function($q) {
                        $q->where('status', 0)->orWhere('superagent_specification_approved', 0);
                    })
                        ->whereIn('id', $superagent_booking_containers->pluck('id')->toArray());
                })
                ->orderBy('id', 'desc')
                ->pluck('shipping_agent_id')
                ->toArray();
                
    
            // Retrieve shipping agents using the retrieved IDs
            $shipping_agents = ShippingAgent::whereIn('id', $shipping_agent_ids)->get();
    
            // Transform data using the resource collection
            $data = SpecificationShippingAgentResource::collection($shipping_agents);
    
            // Response
            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError(500, $ex->getMessage());
        }
    }


    public function unloading_assignments()
    {
        // try {

            $superagent = auth()->guard('superagent')->user();
            $superagent_booking_containers = $superagent->superagent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
                ->wherePivot("created_at", "<=", now()->endOfDay())
                //->wherePivot("booking_container_status", 2)
                    ->get();
                    
                    
                

            $shipping_agent_ids = Booking::has("shippingAgent")
                ->whereHas("bookingContainers", function ($qc) use ($superagent_booking_containers) {
                    $qc->where('superagent_loading_approved', 1)->where('superagent_specification_approved', 1)->where('superagent_unloading_approved', 0);
                })
                ->orderBy("id", "desc")
                ->get()
                ->pluck("shipping_agent_id")
                ->toArray();
                
            $shipping_agents = shippingAgent::whereIn("id", $shipping_agent_ids)->get();

            $data = UnloadingShippingAgentResource::collection($shipping_agents);

            //response

            return $this->returnAllData($data, __('alerts.success'));
        // } catch (\Exception $ex) {


        //     return $this->returnError(500, $ex->getMessage());
        // }
    }

    public function loading_assignments()
    {
      
        $superagent = auth()->guard('superagent')->user();
        $superagent_booking_containers = $superagent->superagent_booking_containers()->wherePivot("created_at", ">=", now()->startOfDay())
            ->wherePivot("created_at", "<=", now()->endOfDay())
            //->wherePivot("booking_container_status", 1)
            ->get();
            

        $yards = Yard::whereHas("bookingContainers", function ($qc) use ($superagent_booking_containers) {
            $qc->where('superagent_loading_approved', 0)->where('superagent_specification_approved', 1)->whereIn("booking_containers.id", $superagent_booking_containers->pluck("id")->toArray());
        })->orderBy("id", "desc")->get();
        
        
        $data = LoadingYardResource::collection($yards);

        //response

        return $this->returnAllData($data, __('alerts.success'));

    }
    public function save_specification_booking_yard(SpecificationBookingYardRequest $request)
    {
        try {

            $booking = Booking::whereId($request->booking_id)->first();

            $booking->update([
                "yard_id" => $request->yard_id
            ]);

            if ($request->image) {
                $paper["booking_id"] = $booking->id;
                $paper["type"] = 0;
                $booking_paper = BookingPaper::create($paper);
                $image_data["image"] = $request->image;
                $image_data["imageable_id"] = $booking_paper->id;
                $image_data["imageable_type"] = "App\Models\BookingPaper";
                Image::create($image_data);
            }

            return $this->returnSuccessMessage( __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
    public function save_loading_booking_container(LoadingBookingRequest $request)
    {
        try {

            $booking_container = BookingContainer::whereId($request->booking_container_id)->first();

            $booking_container->update([
                "container_no" => $request->container_number
            ]);

            if ($request->image) {
                $paper["booking_container_id"] = $booking_container->id;
                $paper["booking_id"] = $booking_container->booking_id;
                $paper["type"] = 1;
                $booking_paper = BookingPaper::create($paper);
                $image_data["image"] = $request->image;
                $image_data["imageable_id"] = $booking_paper->id;
                $image_data["imageable_type"] = "App\Models\BookingPaper";
                Image::create($image_data);
            }

            $data = new BookingContainerResource($booking_container);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
}
