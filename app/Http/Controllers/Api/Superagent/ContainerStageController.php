<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Agent\ExpenseResource;
use App\Models\AgentExpense;
use App\Models\BookingContainer;
use App\Services\ContainerStageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContainerStageController extends Controller
{
    public function pending(Request $request)
    {
        $data = $request->validate(['type_id' => 'required|integer|in:1,2']);
        $type = (int) $data['type_id'];
        $name = ContainerStageService::NAMES[$type];
        $containers = BookingContainer::where('superagent_'.$name.'_approved', 1)
            ->whereHas('agents', fn ($q) => $q->where('booking_container_agents.stage_type', $type))
            ->whereDoesntHave('stages', fn ($q) => $q->where('type_id', $type)->whereNotNull('receipts_closed_at'))
            ->with(['stages', 'agents' => fn ($q) => $q->where('booking_container_agents.stage_type', $type)])
            ->orderBy('id')->paginate(50);
        $containers->getCollection()->transform(fn ($container) => [
            'booking_container_id' => $container->id,
            'booking_id' => $container->booking_id,
            'container_number' => $container->container_no,
            'type_id' => $type,
            'agent_ids' => $container->agents->pluck('id')->unique()->values(),
            'receipts_version' => $container->stages->firstWhere('type_id', $type)?->version ?? 1,
        ]);

        return $this->returnAllData($containers, __('alerts.success'));
    }

    public function receipts(Request $request, ContainerStageService $service)
    {
        $data = $request->validate(['booking_container_id' => 'required|integer|exists:booking_containers,id', 'type_id' => 'required|integer|in:0,1,2']);

        return DB::transaction(function () use ($data, $service) {
            $container = BookingContainer::lockForUpdate()->findOrFail($data['booking_container_id']);
            $stage = $service->receipts($container, (int) $data['type_id']);
            $expenses = AgentExpense::where('booking_container_id', $container->id)->where('type_id', $data['type_id'])->get();

            return $this->returnAllData([
                'booking_container_id' => $container->id,
                'type_id' => (int) $data['type_id'],
                'receipts_version' => $stage->version,
                'receipts_closed_at' => $stage->receipts_closed_at,
                'receipts_closed_by' => $stage->receipts_closed_by,
                'expenses' => ExpenseResource::collection($expenses)->resolve(),
            ], __('alerts.success'));
        });
    }

    public function close(Request $request, ContainerStageService $service)
    {
        $data = $request->validate([
            'booking_container_id' => 'required|integer|exists:booking_containers,id',
            'type_id' => 'required|integer|in:0,1,2',
            'receipts_version' => 'required|integer|min:1',
        ]);
        $stage = $service->closeReceipts((int) $data['booking_container_id'], (int) $data['type_id'], auth('superagent')->id(), (int) $data['receipts_version']);

        return $this->returnAllData($stage, __('alerts.success'));
    }
}
