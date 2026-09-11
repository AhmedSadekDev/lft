<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceRequest;
use App\Models\Booking;
use App\Models\Invoice;
use App\Services\InvoicePrintBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, Booking $booking)
    {
        $booking->loadExpensesForDisplay();
        $company = $booking->company;
        $invoice_number = Invoice::getNextInvoiceNumberForCompany($company->id);

        $transportation_total = $booking->transportation_total_price;
        $taxed_services_total = $booking->taxed_services_total_price;
        $untaxed_services_total = $booking->untaxed_services_total_price;
        $input = [
            'method'            => 'POST',
            'action'            => route('booking-invoices.store', ['booking' => $booking->id]),

            'booking'           => $booking,
            'invoice_number'    => $invoice_number,

            'transportation_total' => $transportation_total,
            'taxed_services_total' => $taxed_services_total,
            'untaxed_services_total' => $untaxed_services_total,
        ];



        return view('admin.bookings.booking-invoices.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(InvoiceRequest $request, Booking $booking)
    {
        DB::beginTransaction();
        try {
            // dd($request->all());
            $company = $booking->company;
            $company = $booking->company;
            // $invoice_number = date("Y") . '-'
            //     . invoiceNumberTrim($company->id) . '-'
            //     . invoiceNumberTrim(
            //         Invoice::getMaxCompanyInvoiceNumber($company->id)
            //             + 1
            //     );

            $invoice_data = array_merge(
                [
                    'invoice_number' => $request->invoice_number,
                    'booking_id' => $booking->id,

                    'transportation_json' => $booking->bookingContainers,
                    'taxed_services_json' => $booking->taxed_services,
                    'untaxed_services_json' => $booking->untaxed_services,

                    'transportation_total_before_vat' => $booking->transportation_total_price,
                    'taxed_services_total_before_vat' => $booking->taxed_services_total_price,
                    'untaxed_services_total_before_vat' => $booking->untaxed_services_total_price,
                ],
                $request->only([
                    'value_added_tax',
                    'discount'
                ])
            );
            $invoice = Invoice::create($invoice_data);
            DB::commit();
            return redirect()->route('bookings.show', $booking)->with('success', __('alerts.added_successfully'));
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
            \Illuminate\Support\Facades\Log::error($th);
            if (!$th->getMessage()) {
                redirect()->route('bookings.index')->with('error', $th->getResponse()?->getData());
            } elseif ($th->getMessage()) {
                redirect()->route('bookings.index')->with('error', $th->getMessage());
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $booking_invoice)
    {
        $booking = $booking_invoice->booking;
        if (!$booking) {
            return redirect()->back()->with('error', 'هذه الفاتورة لا يمكن عرضها لأن الحجز غير موجود');
        }

        $booking->loadExpensesForDisplay();

        $printData = (new InvoicePrintBuilder())->build($booking_invoice);

        // Backward-compatible aliases used by older partials
        $attachment_rows = $printData['receipt']['items']
            ->concat($printData['additional']['items'])
            ->values();

        return view('admin.bookings.booking-invoices.show', [
            'invoice' => $booking_invoice,
            'booking' => $booking,
            'printData' => $printData,
            'taxGroup' => $printData['tax'],
            'receiptGroup' => $printData['receipt'],
            'additionalGroup' => $printData['additional'],
            'combinedItems' => $printData['combined_items'],
            'combinedTotal' => $printData['combined_total'],
            'attachment_rows' => $attachment_rows,
            'agent_expenses_attachment_total' => $printData['additional']['items']
                ->filter(fn ($item) => is_object($item) && ($item->type ?? null) === 'agent_expense_attachment')
                ->sum(fn ($item) => (float) ($item->expense->value ?? $item->price ?? 0)),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $booking_invoice)
    {
        $booking = $booking_invoice->booking;
        if (!$booking)
            return redirect()->back()->with('error', 'هذه الفاتورة لا يمكن تعديلها لأن الحجز غير موجود');

        $booking->loadExpensesForDisplay();

        $transportation_total = $booking->transportation_total_price;
        $taxed_services_total = $booking->taxed_services_total_price;
        $untaxed_services_total = $booking->untaxed_services_total_price;


        $input = [
            'method'            => 'PUT',
            'action'            => route('booking-invoices.update', ['booking_invoice' => $booking_invoice->id]),

            'invoice'           => $booking_invoice,
            'booking'           => $booking,
            'invoice_number'    => $booking->invoice->invoice_number,

            'transportation_total' => $transportation_total,
            'taxed_services_total' => $taxed_services_total,
            'untaxed_services_total' => $untaxed_services_total,
        ];

        return view('admin.bookings.booking-invoices.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(InvoiceRequest $request, Invoice $booking_invoice)
    {
        DB::beginTransaction();
        try {
            $booking = $booking_invoice->booking;
            $invoice_data = array_merge(
                [
                    'booking_id' => $booking->id,

                    'transportation_json' => $booking->bookingContainers,
                    'taxed_services_json' => $booking->taxed_services,
                    'untaxed_services_json' => $booking->untaxed_services,

                    'transportation_total_before_vat' => $booking->transportation_total_price,
                    'taxed_services_total_before_vat' => $booking->taxed_services_total_price,
                    'untaxed_services_total_before_vat' => $booking->untaxed_services_total_price,
                    'invoice_number' => $request->invoice_number
                ],
                $request->only([
                    'value_added_tax',
                    'discount'
                ])
            );
            $booking_invoice = $booking_invoice->update($invoice_data);
            DB::commit();
            return back()
                ->with('success', __('alerts.added_successfully'));
        } catch (\Throwable $th) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error($th);
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $booking_invoice)
    {
        DB::beginTransaction();
        try {
            if ($booking_invoice->invoicePayments()->exists()) {
                DB::rollBack();
                return redirect()
                    ->back()
                    ->with('error', 'لا يمكن حذف الفاتورة بعد تسجيل أي عملية سداد عليها');
            }

            $booking = $booking_invoice->booking;
            $booking_invoice->delete();
            DB::commit();

            if ($booking) {
                return redirect()
                    ->route('bookings.show', $booking->id)
                    ->with('success', __('alerts.deleted_successfully'));
            }

            return redirect()
                ->route('bookings.index')
                ->with('success', __('alerts.deleted_successfully'));
        } catch (\Throwable $th) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error($th);
            return redirect()->back()->with('error', $th->getMessage());
        }
    }
}
