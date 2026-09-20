<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\Agent\DeliveryPolicyController;
use App\Http\Controllers\Api\Agent\ExpenseController;
use App\Models\AgentExpense;
use App\Models\BookingContainer;
use App\Models\DeliveryPolicy;
use App\Models\Invoice;
use App\Traits\ResponseTrait;
use Closure;
use Illuminate\Http\Request;

class BlockInvoicedAgentBookings
{
    use ResponseTrait;

    public function handle(Request $request, Closure $next)
    {
        // Resolve both submitted references and persisted references on edits/deletes.
        $bookingIds = $this->ids($request->input('booking_id'));
        $containerIds = array_merge($this->ids($request->input('booking_container_id')), $this->ids($request->input('booking_container_ids')));
        $policyIds = $this->ids($request->input('delivery_policy_id'));
        $controller = $request->route()?->getAction('controller') ?? '';

        if (in_array($controller, [ExpenseController::class.'@update_expense', ExpenseController::class.'@delete_expense'], true)) {
            $expense = AgentExpense::withTrashed()->whereKey($this->ids($request->input('id')))->first();
            if ($expense) {
                $bookingIds = array_merge($bookingIds, $this->ids($expense->booking_id));
                $containerIds = array_merge($containerIds, $this->ids($expense->booking_container_id));
                $policyIds = array_merge($policyIds, $this->ids($expense->delivery_policy_id));
            }
        }

        if (in_array($controller, [DeliveryPolicyController::class.'@update_delivery_policy', DeliveryPolicyController::class.'@delete_delivery_policy'], true)) {
            $policyIds = array_merge($policyIds, $this->ids($request->input('id')));
        }

        $blocked = ($bookingIds && Invoice::whereIn('booking_id', $bookingIds)->exists())
            || ($containerIds && BookingContainer::whereKey($containerIds)->whereHas('booking.invoice')->exists())
            || ($policyIds && DeliveryPolicy::whereKey($policyIds)->whereHas('booking_containers.booking.invoice')->exists());

        if ($blocked) {
            return $this->returnError(409, 'تم إصدار فاتورة لهذا الطلب، ولم يعد متاحاً للمندوب.');
        }

        return $next($request);
    }

    private function ids($value): array
    {
        return array_values(array_filter((array) $value, fn ($id) => is_scalar($id) && ctype_digit((string) $id) && (int) $id > 0));
    }
}
