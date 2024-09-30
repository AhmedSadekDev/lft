<h2 style="font-family: 'Cairo', sans-serif;text-align: start; margin-bottom: 0;">{{ $document_title ?? '' }}</h2>
<div class="data_invoice">
    <div class="data" style="width: fit-content;display: flex;align-items: center;justify-content: space-between;width: 100%;">
        <div class="company" style="display: flex;align-items: center;">
            <p class="title" style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">فاتورة رقم : </p>
            <p class="text" style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">{{ $invoice->invoice_number ?? "" }}</p>
        </div>
        <div class="invoice_number" style="display: flex;align-items: center;">
            <p class="title" style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">التاريخ : </p>
            <p class="text" style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0.5rem;">{{ $invoice->created_at ?? "" }}</p>
        </div>
    </div>
</div>
<div class="data_invoice" style="border-top: 4px solid #000;">
    <div class="data" style="width: fit-content;display: flex;align-items: center;justify-content: space-between;width: 100%;">
        <div class="company" style="display: flex;align-items: center; width: 33.3%;">
            <p class="title" style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-weight: 700;">اسم الشركة : </p>
            <p class="text" style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-size: .8rem;">{{ $invoice->booking->company->name ?? "" }}</p>
        </div>
        <div class="company" style="display: flex;align-items: center; width: 33.3%;">
            <p class="title" style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-weight: 700;">عناية : </p>
            <p class="text" style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0.5rem 0 0;font-size: .8rem;">{{ $invoice->booking->employee?->name ?? "" }}</p>
        </div>
        <div class="invoice_number" style="display: flex;align-items: center;width: 33.3%;">
            <p class="title" style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0.5rem 0;font-weight: 700;">الخط الملاحي : </p>
            <p class="text" style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0.5rem 0;font-size: .8rem;">{{ $invoice->booking->shippingAgent->title ?? "" }}</p>
        </div>
    </div>
</div>
<div class="data_invoice">
    <div class="data" style="width: fit-content;display: flex;align-items: center;justify-content: space-between;width: 100%;">
        <div class="invoice_number" style="display: flex;align-items: center;width: 33.3%;">
            <p class="title" style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-weight: 700;">رقم الحجز : </p>
            <p class="text" style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-size: .8rem;">{{ $invoice->booking->booking_number ?? "" }}</p>
        </div>
        <div class="company" style="display: flex;align-items: center;width: 33.3%;">
            <p class="title" style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-weight: 700;">رقم الشهادة : </p>
            <p class="text" style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-size: .8rem;">{{ $invoice->booking->certificate_number ?? "" }}</p>
        </div>
        <div class="invoice_number" style="display: flex;align-items: center; width: 33.3%;">
            <p class="title" style="width: fit-content;padding: 0 .5rem;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-weight: 700;">الرقم الضريبي : </p>
            <p class="text" style="width: fit-content;font-family: 'Cairo', sans-serif;margin: 0 0 0;font-size: .8rem;">{{ $invoice->booking->company->tax_no ?? "" }}</p>
        </div>
    </div>
</div>
