<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingAgentExpenseRequest;
use App\Models\AgentExpense;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Traits\ImagesTrait;
use Illuminate\Support\Facades\DB;

class BookingAgentExpenseController extends Controller
{
    use ImagesTrait;

    public function edit(Booking $booking, AgentExpense $agent_expense)
    {
        $this->authorizeBookingExpense($booking, $agent_expense);
        $agent_expense->load(['service.serviceCategory']);

        $referer = request()->server('HTTP_REFERER');
        session(['booking_agent_expense_edit_referrer' => $referer]);

        $service_types = ServiceCategory::all()->pluck('title', 'id');
        $services = Service::pluck('name', 'id');
        $company_prices = $booking
            ->company
            ->services
            ->pluck('pivot.cost', 'id');

        $service_type_id = $agent_expense->service?->service_category_id;

        $inputs = [
            'method' => 'PUT',
            'action' => route('booking-agent-expenses.update', [
                'booking' => $booking->id,
                'agent_expense' => $agent_expense->id,
            ]),
            'service_types' => $service_types,
            'services' => $services,
            'company_prices' => $company_prices,
            'booking' => $booking,
            'agent_expense' => $agent_expense,
            'service_type_id' => $service_type_id,
        ];

        return view('admin.bookings.booking-agent-expenses.edit', $inputs);
    }

    public function update(BookingAgentExpenseRequest $request, Booking $booking, AgentExpense $agent_expense)
    {
        $this->authorizeBookingExpense($booking, $agent_expense);
        $agent_expense->load(['delivery_policy.money_transfer', 'agent']);

        DB::beginTransaction();
        try {
            $oldValue = (float) $agent_expense->value;
            $newValue = (float) $request->validated('value');
            $diff = $newValue - $oldValue;

            if ($agent_expense->delivery_policy_id && $agent_expense->delivery_policy) {
                $policy = $agent_expense->delivery_policy;
                if ((int) $policy->is_settled === 1) {
                    DB::rollBack();

                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', __('main.delivery_policy is settled'));
                }
                if ($diff !== 0.0 && $policy->money_transfer) {
                    $moneyTransfer = $policy->money_transfer;
                    $moneyTransfer->update([
                        'value' => ((float) $moneyTransfer->value) - $diff,
                    ]);
                }
            } else {
                $agent = $agent_expense->agent;
                if ($agent && $diff > 0 && (float) $agent->wallet < $diff) {
                    DB::rollBack();

                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', __('main.you dont have enougth money'));
                }
                if ($agent && $diff !== 0.0) {
                    $agent->update(['wallet' => ((float) $agent->wallet) - $diff]);
                }
            }

            $imageName = $agent_expense->getRawOriginal('image_agent_expenses');
            if ($request->hasFile('image')) {
                $newImageName = time() . '_expenses.' . $request->image->extension();
                $oldPath = $imageName ? 'Admin/images/expenses/' . $imageName : null;
                $this->uploadImage($request->image, $newImageName, 'expenses', $oldPath);
                $imageName = $newImageName;
            }

            $agent_expense->update([
                'service_id' => $request->validated('service_id'),
                'value' => $newValue,
                'notes' => $request->validated('notes') ?? null,
                'image_agent_expenses' => $imageName,
            ]);

            DB::commit();

            $referer = session('booking_agent_expense_edit_referrer')
                ?? route('bookings.show', ['booking' => $booking->id]);
            session()->forget('booking_agent_expense_edit_referrer');

            return redirect($referer)->with('success', __('alerts.updated_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('alerts.error_occurred') . ': ' . $e->getMessage());
        }
    }

    private function authorizeBookingExpense(Booking $booking, AgentExpense $agent_expense): void
    {
        abort_unless((int) $agent_expense->booking_id === (int) $booking->id, 404);
        if ($agent_expense->booking_container_id) {
            $ok = BookingContainer::query()
                ->whereKey($agent_expense->booking_container_id)
                ->where('booking_id', $booking->id)
                ->exists();
            abort_unless($ok, 404);
        }
    }
}
