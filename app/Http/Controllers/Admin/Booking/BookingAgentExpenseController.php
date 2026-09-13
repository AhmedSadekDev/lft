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
        try {
            app(\App\Services\StageExpenseService::class)->change(
                (int) $agent_expense->agent_id, $agent_expense->id, (int) $request->validated('version'),
                ['service_id' => $request->validated('service_id'), 'value' => $request->validated('value'), 'notes' => $request->validated('notes')],
                $request->hasFile('image') ? function () use ($request) {
                    $name = (string) \Illuminate\Support\Str::uuid().'_expenses.'.$request->image->extension();
                    $this->uploadImage($request->image, $name, 'expenses', null);
                    return $name;
                } : null,
                true
            );

            $referer = session('booking_agent_expense_edit_referrer')
                ?? route('bookings.show', ['booking' => $booking->id]);
            session()->forget('booking_agent_expense_edit_referrer');

            return redirect($referer)->with('success', __('alerts.updated_successfully'));
        } catch (\Throwable $e) {

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
