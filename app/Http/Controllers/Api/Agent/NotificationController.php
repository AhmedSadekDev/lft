<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\CarResource;
use App\Http\Resources\Api\Agent\NotificationResource;
use App\Models\Agent;
use App\Models\AppNotification;
use App\Models\Car;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function fetch_notifications(Request $request)
    {
        try {
            $agent = auth('agent')->user();

            $notifications = AppNotification::where(function ($query) use ($agent) {
                $query->where('type', AppNotification::all)
                    ->orWhere(function ($q) use ($agent) {
                        $q->where("notificationable_id", $agent->id)->where("notificationable_type", Agent::class);
                    });
            })
                ->when($request->date, function ($query) use ($request) {
                    $formattedDate = \Carbon\Carbon::parse($request->date)->format('Y-m-d');
                    $query->whereDate('created_at', '=', $formattedDate);
                })
                ->orderBy("id","desc")
                ->get();


            $data = NotificationResource::collection($notifications);


            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
}
