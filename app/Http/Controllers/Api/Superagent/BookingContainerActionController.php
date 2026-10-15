<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\BookingRequest;
use App\Http\Requests\Api\Superagent\BookingContainerRequest;
use App\Http\Requests\Api\Superagent\NoteRequest;
use App\Http\Requests\Api\Superagent\StageApprovalRequest;
use App\Http\Resources\Api\Superagent\BookingContainerResource;
use App\Http\Resources\Api\Superagent\BookingResource;
use App\Http\Resources\Api\Superagent\NoteResource;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\DailyBookingContainer;
use App\Models\Note;
use App\Services\ContainerStageService;
use Illuminate\Http\Request;

class BookingContainerActionController extends Controller
{
    public function done_specification(StageApprovalRequest $request)
    {
        return $this->approveStage($request, 0);
    }


    public function done_loading(StageApprovalRequest $request)
    {
        return $this->approveStage($request, 1);
    }


    public function done_unloading(StageApprovalRequest $request)
    {
        return $this->approveStage($request, 2);
    }

    /**
     * Container row: approves that container only. Booking header: approves every ready container.
     */
    private function approveStage(StageApprovalRequest $request, int $type)
    {
        try {
            $service = app(ContainerStageService::class);
            $superagentId = auth()->guard('superagent')->id();

            if ($request->filled('booking_container_id')) {
                $service->approve((int) $request->booking_container_id, $type, $superagentId);
                $containers = BookingContainer::whereId($request->booking_container_id)->get();
                // done_specification has always returned a single container object.
                $data = $type === 0
                    ? new BookingContainerResource($containers->first())
                    : BookingContainerResource::collection($containers);
            } else {
                $service->approveBooking((int) $request->booking_id, $type, $superagentId);
                $data = BookingContainerResource::collection(
                    BookingContainer::where('booking_id', $request->booking_id)->orderBy('id')->get()
                );
            }

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }


    public function make_it_today(BookingContainerRequest $request)
    {
        try {

            $booking_container = BookingContainer::whereId($request->booking_container_id)->first();

            if ($booking_container->status != 0) {

                $daily_container = DailyBookingContainer::whereDate("created_at", now())->where([["booking_container_id", "=", $booking_container->id],])->first();

                if (!$daily_container) {
                    $data["superagent_id"] = auth()->guard("superagent")->id();
                    $data["booking_container_id"] = $booking_container->id;
                    $data["booking_container_status"] = $booking_container->status;

                    DailyBookingContainer::create($data);
                } else {

                    $daily_container->delete();
                }
            } else {

                foreach ($booking_container->booking->bookingContainers as $container) {

                    $daily_container = DailyBookingContainer::whereDate("created_at", now())->where([["booking_container_id", "=", $container->id],])->first();

                    if (!$daily_container) {
                        $data["superagent_id"] = auth()->guard("superagent")->id();
                        $data["booking_container_id"] = $container->id;
                        $data["booking_container_status"] = $container->status;

                        DailyBookingContainer::create($data);
                    } else {

                        $daily_container->delete();
                    }
                }
            }


            $response = new BookingContainerResource($booking_container);

            //response

            return $this->returnAllData($response, __('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }


    public function make_specification_today(BookingRequest $request)
    {
        try {
            $booking = Booking::findOrFail($request->booking_id);

            $booking_containers = $booking->bookingContainers()
                ->where("booking_containers.status", 0)
                ->get();

            $superagent_id = auth()->guard("superagent")->id();
            $today = now()->toDateString();

            foreach ($booking_containers as $booking_container) {
                $daily_container = DailyBookingContainer::whereDate("created_at", $today)
                    ->where("booking_container_id", $booking_container->id)
                    ->where("superagent_id", $superagent_id)
                    ->first();

                if (!$daily_container) {
                    $data = [
                        "superagent_id" => $superagent_id,
                        "booking_container_id" => $booking_container->id,
                        "booking_container_status" => $booking_container->status
                    ];

                    DailyBookingContainer::create($data);
                } else {
                    $daily_container->delete();
                }
            }

            $response = new BookingResource($booking);

            return $this->returnAllData($response, __('alerts.success'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }


    public function notes(NoteRequest $request)
    {
        try {

            $booking_container = BookingContainer::whereId($request->booking_container_id)->first();


            $data["attacher_id"] = auth()->guard("superagent")->id();
            $data["attacher_type"] = "App\Models\Superagent";
            $data["attached_id"] = $booking_container->id;
            $data["attached_type"] = "App\Models\BookingContainer";
            $data["notes"] = $request->notes;

            $note = Note::create($data);

            $response = new NoteResource($note);

            //response

            return $this->returnAllData($response, __('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }
}
