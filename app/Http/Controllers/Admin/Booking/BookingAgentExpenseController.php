<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingAgentExpenseRequest;
use App\Models\AgentExpense;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\InvoicePrintOverrideService;
use App\Traits\ImagesTrait;
use Illuminate\Http\Request;

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
        $invoice = $booking->invoice;
        $invoiceOnly = (bool) $invoice;
        $overrideService = app(InvoicePrintOverrideService::class);
        $invoiceValue = $invoiceOnly
            ? $overrideService->effectiveExpenseAmount($invoice, $agent_expense)
            : (float) $agent_expense->value;

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
            'invoiceOnly' => $invoiceOnly,
            'invoiceValue' => $invoiceValue,
        ];

        return view('admin.bookings.booking-agent-expenses.edit', $inputs);
    }

    public function update(BookingAgentExpenseRequest $request, Booking $booking, AgentExpense $agent_expense)
    {
        $this->authorizeBookingExpense($booking, $agent_expense);

        $invoice = $booking->invoice;
        if ($invoice) {
            try {
                app(InvoicePrintOverrideService::class)->setExpenseOverride(
                    $invoice,
                    (int) $agent_expense->id,
                    (float) $request->validated('value'),
                    false
                );

                $referer = session('booking_agent_expense_edit_referrer')
                    ?? route('bookings.show', ['booking' => $booking->id]);
                session()->forget('booking_agent_expense_edit_referrer');

                return redirect($referer)->with(
                    'success',
                    'تم تحديث قيمة الفاتورة فقط — حساب المندوب والإيصال الأصلي بدون تغيير'
                );
            } catch (\Throwable $e) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', __('alerts.error_occurred') . ': ' . $e->getMessage());
            }
        }

        try {
            app(\App\Services\StageExpenseService::class)->change(
                (int) $agent_expense->agent_id,
                $agent_expense->id,
                (int) $request->validated('version'),
                [
                    'service_id' => $request->validated('service_id'),
                    'value' => $request->validated('value'),
                    'notes' => $request->validated('notes'),
                ],
                $request->hasFile('image') ? function () use ($request) {
                    $name = (string) \Illuminate\Support\Str::uuid() . '_expenses.' . $request->image->extension();
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

    /**
     * Hide agent expense from invoice print only — keeps agent custody & app receipt.
     */
    public function hideFromInvoice(Request $request, Booking $booking, AgentExpense $agent_expense)
    {
        $this->authorizeBookingExpense($booking, $agent_expense);
        $invoice = $booking->invoice;
        abort_unless($invoice, 422, 'لا توجد فاتورة لهذا الطلب');

        try {
            app(InvoicePrintOverrideService::class)->hideExpenseFromInvoice($invoice, (int) $agent_expense->id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إخفاء الإيصال من الفاتورة مع بقائه في حساب المندوب',
                ]);
            }

            return back()->with('success', 'تم إخفاء الإيصال من الفاتورة مع بقائه في حساب المندوب');
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
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
