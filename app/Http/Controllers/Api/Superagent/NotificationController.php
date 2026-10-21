<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
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
            $perPage = (int) $request->get('per_page', 20);
            $page = (int) $request->get('page', 1);

            $notifications = $this->superagentNotificationsQuery($superagent)
                ->when($request->date, function ($query) use ($request) {
                    $formattedDate = \Carbon\Carbon::parse($request->date)->format('Y-m-d');
                    $query->whereDate('created_at', '=', $formattedDate);
                })
                ->orderBy('id', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return $this->paginatedNotificationsResponse($notifications);
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }

    public function fetch_agents_notifications(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 20);
            $page = (int) $request->get('page', 1);

            $notifications = $this->agentsNotificationsQuery()
                ->when($request->date, function ($query) use ($request) {
                    $formattedDate = \Carbon\Carbon::parse($request->date)->format('Y-m-d');
                    $query->whereDate('created_at', '=', $formattedDate);
                })
                ->orderBy('id', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return $this->paginatedNotificationsResponse($notifications);
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }

    public function mark_as_read(Request $request)
    {
        try {
            $user = auth('superagent')->user();
            if (! $user) {
                return $this->returnError(401, 'Unauthorized');
            }

            // Scope must match the list the UI is looking at:
            // - agents_alerts=1 / scope=agents → تنبيهات المندوبين only
            // - otherwise mark-all clears both own + agent alerts (covers current app)
            $scope = (string) $request->get('scope', '');
            $agentsAlerts = $request->boolean('agents_alerts')
                || $scope === 'agents'
                || $scope === 'agents_notifications';

            if ($request->filled('id')) {
                $updated = AppNotification::query()
                    ->where('id', $request->id)
                    ->where(function ($q) use ($user) {
                        $q->where('type', AppNotification::all)
                            ->orWhere(function ($sub) use ($user) {
                                $sub->where('notificationable_id', $user->id)
                                    ->where('notificationable_type', Superagent::class);
                            })
                            ->orWhere('notificationable_type', Agent::class);
                    })
                    ->update(['is_read' => true]);
            } elseif ($agentsAlerts) {
                $updated = $this->agentsNotificationsQuery()->update(['is_read' => true]);
            } else {
                $updated = $this->superagentNotificationsQuery($user)->update(['is_read' => true]);
                $updated += $this->agentsNotificationsQuery()
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
            }

            return $this->returnAllData([
                'updated_count' => (int) $updated,
            ], __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }

    private function superagentNotificationsQuery(Superagent $superagent)
    {
        return AppNotification::with('bookingContainer:id,booking_id')
            ->whereDoesntHave('bookingContainer.booking.invoice')
            ->where(function ($query) use ($superagent) {
                $query->where('type', AppNotification::all)
                    ->orWhere(function ($q) use ($superagent) {
                        $q->where('notificationable_id', $superagent->id)
                            ->where('notificationable_type', Superagent::class);
                    });
            });
    }

    private function agentsNotificationsQuery()
    {
        return AppNotification::with('bookingContainer:id,booking_id')
            ->whereDoesntHave('bookingContainer.booking.invoice')
            ->where('notificationable_type', Agent::class);
    }

    private function paginatedNotificationsResponse($notifications)
    {
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
    }
}
