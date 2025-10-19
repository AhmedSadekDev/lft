<?php

namespace App\Http\Controllers\Admin\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceRequest;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
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
        $company = $booking->company;
        $invoice_number = date("Y") . '-'
            . invoiceNumberTrim($company->id) . '-'
            . invoiceNumberTrim(count($company->invoices) + 1);

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
                    'sales_tax',
                    'discount'
                ])
            );
            $invoice = Invoice::create($invoice_data);
            DB::commit();
            return redirect()->route('bookings.index')->with('success', __('alerts.added_successfully'));
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
        // first page rows limit with header and footer
        $fpr_hf_limit = 6;
        // first page rows limit with header only
        $fpr_h_limit = 8;
        // middle page rows limit
        $mpr_limit = 10;
        // last page rows limit with footer only
        $lpr_limit = 8;

        $booking = $booking_invoice->booking;
        $invoice_rows = $booking->bookingContainers
            ->concat($booking->getTaxedServices);
        $fpr = [];
        $mps = [];
        $lpr = [];

        $fpr = $invoice_rows->shift(
            count($invoice_rows) <= $fpr_hf_limit ? $fpr_hf_limit : $fpr_h_limit
        );

        $mps_count = floor(count($invoice_rows) / $mpr_limit);
        $mps_modulus = count($invoice_rows) % $mpr_limit;
        if ($mps_modulus <= $lpr_limit)
            $lpr = $invoice_rows->pop($mps_modulus);
        else
            $mps_count++;


        for ($i = 0; $i < $mps_count; $i++)
            $mps[] = $invoice_rows->shift($mpr_limit);

        if (!is_array($fpr) && !($fpr instanceof Collection))
            $fpr = [$fpr];
        foreach ($mps as $key => $mp)
            if (!is_array($mp) && !($mp instanceof Collection))
                $mps[$key] = [$mp];
        if (!is_array($lpr) && !($lpr instanceof Collection))
            $lpr = [$lpr];

        return view('admin.bookings.booking-invoices.show', [
            'invoice' => $booking_invoice,
            'fpr' => $fpr,
            'mps' => $mps,
            'lpr' => $lpr,
            'fpr_hf_limit' => $fpr_hf_limit,
            'fpr_h_limit' => $fpr_h_limit,
            'mpr_limit' => $mpr_limit,
            'lpr_limit' => $lpr_limit,
            'booking' => $booking,
            'attachment_rows' => $booking->getUnTaxedServices
        ])->render();
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
                    'sales_tax',
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
            if (!$th->getMessage()) {
                redirect()->back()->with('error', $th->getResponse()?->getData());
            } elseif ($th->getMessage()) {
                redirect()->back()->with('error', $th->getMessage());
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        //
    }
}
