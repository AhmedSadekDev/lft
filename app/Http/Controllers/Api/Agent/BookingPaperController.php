<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\BookingContainerRequest;
use App\Http\Requests\Api\Agent\CarPaperRequest;
use App\Http\Requests\Api\Agent\LoadingBookingRequest;
use App\Http\Requests\Api\Agent\SpecificationBookingYardRequest;
use App\Http\Requests\Api\Agent\unloadingBookingSailRequest;
use App\Http\Resources\Api\Agent\CarResource;
use App\Http\Resources\Api\Agent\BookingContainerResource;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\BookingPaper;
use App\Models\Image;
use Illuminate\Http\Request;

class BookingPaperController extends Controller
{
    public function save_specification_booking_yard(SpecificationBookingYardRequest $request)
    {
        try {

            $booking = Booking::whereId($request->booking_id)->first();
            

            $booking->update([
                "yard_id" => $request->yard_id
            ]);
            
            foreach($booking->bookingContainers as $container) {
                $container->update([
                    "yard_id" => $request->yard_id
                ]);
            }
            

            if ($request->image) {
                $paper["booking_id"] = $booking->id;
                $paper["type"] = 0;
                $paper['booking_container_id'] = $request->booking_container_id;
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
    public function save_unloading_booking_sail(unloadingBookingSailRequest $request)
    {
        try {

            $booking_container = BookingContainer::whereId($request->booking_container_id)->first();

            $booking_container->update([
                "sail_of_number" => $request->sail_of_number
            ]);

            if ($request->image) {
                $paper["booking_container_id"] = $booking_container->id;
                $paper["booking_id"] = $booking_container->booking_id;
                $paper["type"] = 2;
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
    public function send_car_papers(CarPaperRequest $request)
    {
        try {

            $booking_container = BookingContainer::whereId($request->booking_container_id)->first();



            if ($request->images && count($request->images) > 0) {
                $paper["booking_container_id"] = $booking_container->id;
                $paper["booking_id"] = $booking_container->booking_id;
                $paper["type"] = 3;
                $booking_paper = BookingPaper::create($paper);
                foreach ($request->images as $image) {
                    $image_data["image"] = $image;
                    $image_data["imageable_id"] = $booking_paper->id;
                    $image_data["imageable_type"] = "App\Models\BookingPaper";
                    Image::create($image_data);
                }
            }

            $data = new BookingContainerResource($booking_container);





            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
}
