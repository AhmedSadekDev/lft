<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\BookingContainerRequest;
use App\Http\Requests\Api\Agent\BookingRequest;
use App\Http\Requests\Api\Agent\NoteRequest;
use App\Http\Resources\Api\Agent\BookingContainerResource;
use App\Http\Resources\Api\Agent\NoteResource;
use App\Models\Agent;
use App\Models\AgentExpense;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\BookingContainerAgent;
use App\Models\DailyBookingContainer;
use App\Models\Image;
use App\Models\Note;
use App\Models\Superagent;
use App\Services\SaveNotification;
use App\Services\SendNotification;
use App\Traits\HandlesAgentImageUploads;
use Illuminate\Http\Request;

class BookingContainerActionController extends Controller
{
    use HandlesAgentImageUploads;
    public function done_specification(BookingRequest $request)
    {
        try {


            $agent = auth()->guard("agent")->user();
            $booking = Booking::whereId($request->booking_id)->first();

            $booking_container_ids =  $booking->bookingContainers()->where("booking_containers.status", 0)->pluck("id")->toArray();

            $booking->bookingContainers()->where("booking_containers.status", 0)->update([
                "status" => 1
            ]);

            // إزالة المندوب/المناديب بالكامل بعد التخصيص — المدير يعيّن مندوب التحميل من جديد
            BookingContainerAgent::whereIn("booking_container_id", $booking_container_ids)->delete();

            $dailyContainers = DailyBookingContainer::whereIn("booking_container_id", $booking_container_ids);

            foreach ($dailyContainers->get() as $dailyContainer) {

                $dailyContainer->update([
                    "booking_container_status" => 1
                ]);
            }



            $title = __('new_notification');
            $text = __('booking_specification', [
                'booking_number' => $booking->booking_number,
                'agent' => $agent->name
            ]);

            $first_container_id = !empty($booking_container_ids) ? $booking_container_ids[0] : ($booking->bookingContainers()->first()?->id ?? null);
            SaveNotification::create($title, $text, null, null, AppNotification::all, $first_container_id, 0);
            // SendNotification::send($agent->device_token ?? "", $title, $text);

            $superAgents = Superagent::get();
            \Illuminate\Support\Facades\Log::info('done_specification: Sending notification to all superagents', [
                'booking_id' => $booking->id,
                'superagents_count' => $superAgents->count(),
            ]);

            foreach ($superAgents as $superAgent) {
                $notificationData = [
                    'booking_id' => $booking->id,
                    'booking_container_id' => $first_container_id,
                    'type_id' => 0,
                    'action_type' => 'specification' // تخصيص
                ];
                \Illuminate\Support\Facades\Log::info('done_specification: Sending notification to superagent', [
                    'superagent_id' => $superAgent->id,
                    'name' => $superAgent->name,
                    'device_token' => $superAgent->device_token,
                ]);
                SendNotification::send($superAgent->device_token ?? "", $title, $text, $notificationData);
            }



            //response
            return $this->returnSuccessMessage(__('Wait For Superagent approval'));
        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }

    public function done_loading(BookingContainerRequest $request)
    {
        try {

            $agent = auth()->guard("agent")->user();
            $booking_container = BookingContainer::whereId($request->booking_container_id)->first();

            $booking_container->update([
                "status" => 2
            ]);

            // إزالة المندوب بالكامل بعد التحميل — المدير يعيّن مندوب التعتيق من جديد
            BookingContainerAgent::where("booking_container_id", $booking_container->id)->delete();

            $dailyContainers = DailyBookingContainer::where("booking_container_id", $booking_container->id);

            foreach ($dailyContainers->get() as $dailyContainer) {
                $dailyContainer->update([
                    "booking_container_status" => 2
                ]);
            }

            $data = new BookingContainerResource($booking_container);

            $title = __('new_notification');
            $text = __('container_loaded', [
                'container_no' => $booking_container->container_no,
                'agent' => $agent->name
            ]);

            SaveNotification::create($title, $text, null, null, AppNotification::all, $booking_container->id, 1);
            
            if ($agent->device_token) {
                $notificationData = [
                    'booking_id' => $booking_container->booking_id,
                    'booking_container_id' => $booking_container->id,
                    'type_id' => 1,
                    'action_type' => 'loading' // تحميل
                ];
                SendNotification::send($agent->device_token, $title, $text, $notificationData);
            }

            $superAgents = Superagent::get();
            \Illuminate\Support\Facades\Log::info('done_loading: Sending notification to all superagents', [
                'booking_container_id' => $booking_container->id,
                'superagents_count' => $superAgents->count(),
            ]);

            foreach ($superAgents as $superAgent) {
                $notificationData = [
                    'booking_id' => $booking_container->booking_id,
                    'booking_container_id' => $booking_container->id,
                    'type_id' => 1,
                    'action_type' => 'loading' // تحميل
                ];
                \Illuminate\Support\Facades\Log::info('done_loading: Sending notification to superagent', [
                    'superagent_id' => $superAgent->id,
                    'name' => $superAgent->name,
                    'device_token' => $superAgent->device_token,
                ]);
                SendNotification::send($superAgent->device_token ?? "", $title, $text, $notificationData);
            }

            $this->saveLogActivity(auth()->guard("agent")->user()->id, Agent::class, $booking_container->id, BookingContainer::class, $booking_container->status);

            //response

            return $this->returnAllData($data, __('Wait For Superagent approval'));
        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }

    public function done_unloading(BookingContainerRequest $request)
    {
        try {

            $agent = auth()->guard("agent")->user();
            $booking_container = BookingContainer::whereId($request->booking_container_id)->first();

            // if (!$booking_container->superagent_loading_approved) {
            //     return $this->returnError(400, __('main.superagent_not_approved'));
            // }

            $booking_container->update([
                "status" => 3
            ]);

            // إزالة المندوب بالكامل بعد التعتيق
            BookingContainerAgent::where("booking_container_id", $booking_container->id)->delete();

            $dailyContainers = DailyBookingContainer::where("booking_container_id", $booking_container->id);

            foreach ($dailyContainers->get() as $dailyContainer) {
                $dailyContainer->update([
                    "booking_container_status" => 3
                ]);
            }

            $data = new BookingContainerResource($booking_container);

            $title = __('new_notification');
            $text = __('container_unloaded', [
                'container_no' => $booking_container->container_no,
                'agent' => $agent->name
            ]);

            SaveNotification::create($title, $text, null, null, AppNotification::all, $booking_container->id, 2);
            
            if ($agent->device_token) {
                $notificationData = [
                    'booking_id' => $booking_container->booking_id,
                    'booking_container_id' => $booking_container->id,
                    'type_id' => 2,
                    'action_type' => 'unloading' // تعتيق
                ];
                SendNotification::send($agent->device_token, $title, $text, $notificationData);
            }

            $superAgents = Superagent::get();
            \Illuminate\Support\Facades\Log::info('done_unloading: Sending notification to all superagents', [
                'booking_container_id' => $booking_container->id,
                'superagents_count' => $superAgents->count(),
            ]);

            foreach ($superAgents as $superAgent) {
                $notificationData = [
                    'booking_id' => $booking_container->booking_id,
                    'booking_container_id' => $booking_container->id,
                    'type_id' => 2,
                    'action_type' => 'unloading' // تعتيق
                ];
                \Illuminate\Support\Facades\Log::info('done_unloading: Sending notification to superagent', [
                    'superagent_id' => $superAgent->id,
                    'name' => $superAgent->name,
                    'device_token' => $superAgent->device_token,
                ]);
                SendNotification::send($superAgent->device_token ?? "", $title, $text, $notificationData);
            }

            $this->saveLogActivity(auth()->guard("agent")->user()->id, Agent::class, $booking_container->id, BookingContainer::class, $booking_container->status);

            //response

            return $this->returnAllData($data, __('Wait For Superagent approval'));
        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }
    public function send_notes(NoteRequest $request)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) {
            return $response;
        }

        try {

            $booking_container = BookingContainer::whereId($request->booking_container_id)->first();


            $data["attacher_id"] = auth()->guard("agent")->id();
            $data["attacher_type"] = "App\Models\Agent";
            $data["attached_id"] = $booking_container->id;
            $data["attached_type"] = "App\Models\BookingContainer";
            $data["notes"] = $request->notes;

            $note = Note::create($data);

            if ($request->hasFile('images') || $request->has('images')) {
                if (! $request->hasFile('images')) {
                    return $this->returnError(422, 'فشل رفع صور الملاحظة: الحجم كبير جداً أو انقطع الاتصال أثناء الرفع.');
                }

                foreach ($request->file('images') as $index => $image) {
                    $upload = $this->imageUploadService()->storeUploadedFile($image, 'notes');
                    if (! $upload['ok']) {
                        return $this->returnError(422, $upload['error'] ?? ('فشل رفع صورة الملاحظة رقم ' . ($index + 1)));
                    }

                    $this->attachStoredImage($upload['path'], $note->id, Note::class);
                }
            }
            $response = new NoteResource($note);

            //response

            return $this->returnAllData($response, __('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }

    public function getContainers()
    {

    }
}
