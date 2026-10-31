<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\TransferAgentRequest;
use App\Http\Resources\Api\Agent\NameResource;
use App\Models\Agent;
use App\Models\AppNotification;
use App\Models\MoneyTransfer;
use App\Services\AgentVaultReturnService;
use App\Services\SaveNotification;
use App\Services\SendNotification;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TransferAgentController extends Controller
{
    public function fetch_agents()
    {
        try {

            $agent = auth()->guard('agent')->user();
            $perPage = (int) request()->get('per_page', 20);
            $page = (int) request()->get('page', 1);

            $agents = Agent::where("id", "!=", $agent->id)->ofFilter()->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

            $data = NameResource::collection($agents->items());
            $pagination = [
                'total' => $agents->total(),
                'per_page' => $agents->perPage(),
                'current_page' => $agents->currentPage(),
                'total_pages' => $agents->lastPage(),
            ];

            return response()->json([
                'status' => true,
                'errNum' => "0000",
                'message' => __('alerts.success'),
                'data' => $data,
                'pagination' => $pagination,
            ], 200);
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }

    public function transfer_to_agent(TransferAgentRequest $request)
    {
        try {
            $agent = auth()->guard('agent')->user();
            $toVault = $request->boolean('vault') || ! $request->filled('agent_id');

            if ($toVault) {
                app(AgentVaultReturnService::class)->request(
                    $agent,
                    (float) $request->value,
                    $request->input('notes')
                );

                return $this->returnResponseSuccessMessage(__('alerts.success'));
            }

            if ((float) $agent->wallet < (float) $request->value) {
                return $this->returnError(200, __('main.you dont have enougth money'));
            }

            $value = (float) $request->value;

            DB::transaction(function () use ($agent, $request, $value) {
                $agent = Agent::lockForUpdate()->findOrFail($agent->id);
                abort_unless((float) $agent->wallet >= $value, 422, __('main.you dont have enougth money'));

                $transAgent = Agent::lockForUpdate()->findOrFail($request->agent_id);

                $moneyTransfer = MoneyTransfer::create([
                    'value' => $value,
                    'type' => MoneyTransfer::transferAgent,
                    'transferer_type' => Agent::class,
                    'transferer_id' => $agent->id,
                    'transfered_type' => Agent::class,
                    'transfered_id' => $transAgent->id,
                    'notes' => $request->input('notes'),
                ]);

                $agent->wallet = round((float) $agent->wallet - $value, 2);
                $agent->save();

                $transAgent->wallet = round((float) $transAgent->wallet + $value, 2);
                $transAgent->save();

                $this->saveLogActivity($agent->id, Agent::class, $moneyTransfer->id, MoneyTransfer::class);

                $title = __('new_notification');
                $text = __('money_transferred', [
                    'agent' => $agent->name,
                    'value' => $value,
                ]);

                SaveNotification::create($title, $text, $moneyTransfer->transfered_id, Agent::class, AppNotification::specific);
                SendNotification::send($transAgent->device_token ?? '', $title, $text);
            });

            return $this->returnResponseSuccessMessage(__('alerts.success'));
        } catch (HttpExceptionInterface $e) {
            return $this->returnError($e->getStatusCode(), $e->getMessage());
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
}
