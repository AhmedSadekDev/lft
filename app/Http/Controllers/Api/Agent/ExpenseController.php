<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\GeneralExpenseRequest;
use App\Http\Requests\Api\Agent\ReservationExpenseRequest;
use App\Http\Resources\Api\Agent\ExpenseResource;
use App\Models\Agent;
use App\Traits\HandlesAgentImageUploads;
use App\Traits\ImagesTrait;
use App\Models\AgentExpense;
use App\Models\BookingContainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Image;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    use HandlesAgentImageUploads, ImagesTrait;

    private function expenseFailure(\Throwable $e)
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->returnError(422, $e->validator->errors()->first());
        }
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->returnError(404, 'Receipt not found');
        }
        return $this->returnError($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : 500, $e->getMessage());
    }

    private function expenseImage(Request $request)
    {
        $stored = $this->storeAdminExpenseImage($request, 'image', false);
        if ($stored instanceof \Illuminate\Http\JsonResponse) {
            throw new \RuntimeException(json_decode($stored->getContent(), true)['message'] ?? 'Image upload failed');
        }
        return $stored;
    }

    public function update_expense(Request $request)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) { return $response; }
        try {
            $data = $request->validate([
                'id' => 'required|integer|exists:agent_expenses,id',
                'version' => 'required|integer|min:1',
                'value' => 'required|numeric|min:0.01',
                'service_id' => 'sometimes|integer|exists:services,id',
                'notes' => 'sometimes|nullable|string',
                'image' => 'sometimes|image|max:10000',
            ]);
            $expense = AgentExpense::findOrFail($data['id']);
            foreach (['booking_container_id', 'type_id'] as $field) {
                abort_if($request->has($field) && (string) $request->$field !== (string) $expense->$field, 422, 'Receipt container and stage cannot be changed');
            }
            unset($data['id'], $data['version'], $data['image']);
            app(\App\Services\StageExpenseService::class)->change(
                auth('agent')->id(), $expense->id, (int) $request->version, $data,
                $request->hasFile('image') ? fn () => $this->expenseImage($request) : null
            );
            return $this->returnAllData(new ExpenseResource($expense->fresh()), __('alerts.success'));
        } catch (\Throwable $e) { return $this->expenseFailure($e); }
    }

    public function delete_expense(Request $request)
    {
        try {
            $request->validate(['id' => 'required|integer|exists:agent_expenses,id', 'version' => 'required|integer|min:1']);
            app(\App\Services\StageExpenseService::class)->change(auth('agent')->id(), (int) $request->id, (int) $request->version, null);
            return $this->returnResponseSuccessMessage(__('alerts.success'), 200);
        } catch (\Throwable $e) { return $this->expenseFailure($e); }
    }

    public function fetch_financial_custody()
    {
        try {

            $agent = auth()->guard('agent')->user();

            $total_financial_custody  = (int)$agent->total_wallet;
            $spented_financial_custody  = $agent->spented_financial_custody;
            $remaining_financial_custody  = (int)$agent->wallet;

            //reponse
            $data["total_financial_custody"] = $total_financial_custody;
            $data["spented_financial_custody"] = $spented_financial_custody;
            $data["remaining_financial_custody"] = $remaining_financial_custody;


            return $this->returnAllData((object) $data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
    public function make_general_expenses(GeneralExpenseRequest $request)
    {
        return $this->createExpense($request, 1);
    }

    public function make_reservation_expenses(ReservationExpenseRequest $request)
    {
        return $this->createExpense($request, 2);
    }

    private function createExpense(Request $request, int $type)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) { return $response; }
        try {
            $request->validate(['request_key' => 'required|string|max:80', 'image' => 'sometimes|image|max:10000']);
            $data = $request->only(['value', 'service_id', 'notes', 'booking_container_id', 'type_id']);
            $data['type'] = $type;
            if ($request->filled('booking_container_id')) {
                $container = BookingContainer::findOrFail($request->booking_container_id);
                abort_if($request->filled('booking_id') && (int) $request->booking_id !== (int) $container->booking_id, 422, 'Container does not belong to booking');
                $data['booking_id'] = $container->booking_id;
            }
            $fingerprintData = $data;
            ksort($fingerprintData);
            $fingerprintData['image_hash'] = $request->hasFile('image') ? hash_file('sha256', $request->file('image')->getRealPath()) : null;
            $fingerprint = hash('sha256', json_encode($fingerprintData));
            $expense = app(\App\Services\StageExpenseService::class)->create(
                auth('agent')->id(), $data, $request->request_key, $fingerprint,
                fn () => $request->hasFile('image') ? $this->expenseImage($request) : null
            );
            if ($expense->wasRecentlyCreated) {
                $this->saveLogActivity(auth('agent')->id(), Agent::class, $expense->id, AgentExpense::class, (int) $expense->type_id);
            }
            return $this->returnAllData(new ExpenseResource($expense), __('alerts.Expense saved successfully'));
        } catch (\Throwable $e) { return $this->expenseFailure($e); }
    }

    public function fetch_all_expenses()
    {
        try {
            $type = request()->type;
            $agent = auth()->guard('agent')->user();
            $financial_custodies = collect();
            $expenses = collect();

            // إذا كان هناك booking_id، يجب أن يكون له الأولوية
            if (request()->booking_id) {
                $bookingId = request()->booking_id;

                $expensesQuery = $agent->expenses()->visibleToAgent()
                    ->where(function ($query) use ($bookingId) {
                        // المصروفات التي لها booking_id مباشرة يساوي الحجز المطلوب
                        $query->where('booking_id', $bookingId)
                              // أو المصروفات التي لها booking_container_id مرتبط بالحجز المطلوب فقط
                              ->orWhereHas('bookingContainer', function ($q) use ($bookingId) {
                                  $q->where('booking_id', $bookingId);
                              });
                    })
                    // استبعاد المصروفات التي لها booking_id مختلف عن الحجز المطلوب
                    ->where(function ($query) use ($bookingId) {
                        $query->whereNull('booking_id')
                              ->orWhere('booking_id', $bookingId);
                    });

                // إذا كان هناك type، أضف فلتر type
                if ($type == 2) {
                    $expensesQuery->where("type", 2);
                }

                $expenses = $expensesQuery->get();
            } elseif ($type == 1) {
                $financial_custodies = $agent->sended_financial_custodies()
                    ->whereDoesntHave('delivery_policy.booking_containers.booking.invoice')
                    ->where("delivery_policy_id", "!=", null)
                    ->whereDate("created_at", now())
                    ->get();
                $expenses = $agent->expenses()->visibleToAgent()
                    ->whereDate("created_at", now())
                    ->where("delivery_policy_id", "!=", null)
                    ->get();
            } elseif ($type == 2) {
                $expenses = $agent->expenses()->visibleToAgent()
                    ->whereDate("created_at", now())
                    ->where("type", 2)
                    ->get();
            } elseif (request()->delivery_policy_id) {
                $expenses = $agent->expenses()->visibleToAgent()
                    ->where("delivery_policy_id", request()->delivery_policy_id)
                    ->get();
            } else {
                $financial_custodies = $agent->sended_financial_custodies()
                    ->whereDoesntHave('delivery_policy.booking_containers.booking.invoice')
                    ->whereDate("created_at", now())
                    ->get();
                $expenses = $agent->expenses()->visibleToAgent()
                    ->whereDate("created_at", now())
                    ->get();
            }

            $merged = $financial_custodies->concat($expenses);

            $ordered = $merged->sortBy('created_at')->values();

            $data = ExpenseResource::collection($ordered);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $exception) {
            return $this->returnError(401, $exception->getMessage());
        }
    }
    public function fetch_latest_expenses()
    {
        try {

            $agent = auth()->guard('agent')->user();

            $financial_custodies = $agent->sended_financial_custodies()->whereDoesntHave('delivery_policy.booking_containers.booking.invoice')->whereDate("created_at", now())->get();
            $expenses = $agent->expenses()->visibleToAgent()->whereDate("created_at", now())->get();

            // Merge the collections
            $merged = $financial_custodies->concat($expenses);

            // Order the merged collection by the created_at timestamp
            $ordered = $merged->sortBy('created_at')->take(3);

            $data = ExpenseResource::collection($ordered);

            return $this->returnAllData($data, __('alerts.success'));
        } catch (\Exception $Exception) {
            return $this->returnError(401, $Exception->getMessage());
        }
    }
}
