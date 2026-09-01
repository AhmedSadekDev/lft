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

            $notifications = AppNotification::with('bookingContainer:id,booking_id')
                ->where(function ($query) use ($superagent) {
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

            $notifications = AppNotification::with('bookingContainer:id,booking_id')
                ->where("notificationable_type", Agent::class)
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

    public function mark_as_read(Request $request)
    {
        try {
            $user = auth('agent')->user() ?: auth('superagent')->user();
            $class = auth('agent')->check() ? \App\Models\Agent::class : \App\Models\Superagent::class;

            if (!$user) {
                return $this->returnError(401, 'Unauthorized');
            }

            $query = AppNotification::where(function ($q) use ($user, $class) {
                $q->where('type', AppNotification::all)
                    ->orWhere(function ($sub) use ($user, $class) {
                        $sub->where("notificationable_id", $user->id)
                            ->where("notificationable_type", $class);
                    });
            });

            if ($request->filled('id')) {
                $query->where('id', $request->id);
            }

            $query->update(['is_read' => true]);

            return $this->returnSuccessMessage(__('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
}
