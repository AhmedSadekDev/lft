<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\NotificationResource;
use App\Models\Agent;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function fetch_notifications(Request $request)
    {
        try {
            $agent = auth('agent')->user();
            $perPage = (int) $request->get('per_page', 20);
            $page = (int) $request->get('page', 1);

            $notifications = $this->agentNotificationsQuery($agent)
                ->when($request->date, function ($query) use ($request) {
                    $formattedDate = \Carbon\Carbon::parse($request->date)->format('Y-m-d');
                    $query->whereDate('created_at', '=', $formattedDate);
                })
                ->orderBy('id', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $data = NotificationResource::collection($notifications->items());
            $pagination = [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'total_pages' => $notifications->lastPage(),
            ];

            return response()->json([
                'status' => true,
                'errNum' => '0000',
                'message' => __('alerts.success'),
                'data' => $data,
                'pagination' => $pagination,
            ], 200);
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }

    public function mark_as_read(Request $request)
    {
        try {
            $agent = auth('agent')->user();
            if (! $agent) {
                return $this->returnError(401, 'Unauthorized');
            }

            // Same scope as the list, so "read all" clears what the app shows as unread.
            $query = $this->agentNotificationsQuery($agent);

            if ($request->filled('id')) {
                $query->where('id', $request->id);
            }

            $updated = $query->update(['is_read' => true]);

            return $this->returnAllData([
                'updated_count' => (int) $updated,
            ], __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }

    private function agentNotificationsQuery(Agent $agent)
    {
        return AppNotification::with('bookingContainer:id,booking_id')
            ->whereDoesntHave('bookingContainer.booking.invoice')
            ->where(function ($query) use ($agent) {
                $query->where('type', AppNotification::all)
                    ->orWhere(function ($q) use ($agent) {
                        $q->where('notificationable_id', $agent->id)
                            ->where('notificationable_type', Agent::class);
                    });
            });
    }
}
