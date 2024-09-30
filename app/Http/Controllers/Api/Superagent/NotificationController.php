<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\CarResource;
use App\Http\Resources\Api\Agent\NotificationResource;
use App\Models\Agent;
use App\Models\AppNotification;
use App\Models\Superagent;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function fetch_notifications(Request $request)
    {
        try {
            $superagent = auth('superagent')->user();

            $notifications = AppNotification::where(function ($query) use ($superagent) {
                $query->where('type', AppNotification::all)
                    ->orWhere(function ($q) use ($superagent) {
                        $q->where("notificationable_id", $superagent->id)->where("notificationable_type", Superagent::class);
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
    public function fetch_agents_notifications(Request $request)
    {
        try {
            $superagent = auth('superagent')->user();

            $notifications = AppNotification::where("notificationable_type", Agent::class)
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
