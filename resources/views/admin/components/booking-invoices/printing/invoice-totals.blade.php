<div class="price" style="border-bottom: 2px solid #000;">
    <div class="info" style="display: flex;align-items: center;justify-content: start;flex-wrap: wrap;">
        <div class="info" style="display: flex;align-items: center;width: 33.3%;">
            <p class="title" style="width: fit-content;padding: .25rem;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">ضريبة القيمة المضافة ({{$invoice->value_added_tax}}%) : </p>
            <p class="text" style="width: fit-content;text-align: center;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">{{$invoice->value_added_tax_amount}}</p>
        </div>
        <div class="info" style="display: flex;align-items: center;width: 33.3%;">
            <p class="title" style="width: fit-content;padding: .25rem;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">ضريبة عامة ({{$invoice->sales_tax}}%) : </p>
            <p class="text" style="width: fit-content;text-align: center;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">{{$invoice->sales_tax_amount}}</p>
        </div>
        <div class="info" style="display: flex;align-items: center;width: 33.3%;">
            <p class="title" style="width: fit-content;padding: .25rem;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">إجمالي الفاتورة قبل الضريبة : </p>
            <p class="text" style="width: fit-content;text-align: center;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">{{$invoice->invoice_total_before_tax}}</p>
        </div>
        <div class="info" style="display: flex;align-items: center;width: 33.3%;">
            <p class="title" style="width: fit-content;padding: .25rem;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">الخصم  : </p>
            <p class="text" style="width: fit-content;text-align: center;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">{{$invoice->discount_amount}}</p>
        </div>
        <div class="info" style="display: flex;align-items: center;width: 33.3%;">
            <p class="title" style="width: fit-content;padding: .25rem;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">اجمالي الفاتورة بعد الضريبة : </p>
            <p class="text" style="width: fit-content;width: 15%;text-align: center;font-family: 'Cairo', sans-serif;margin: 0;font-size: .8rem;">{{$invoice->invoice_total_after_tax}}</p>
        </div>
    </div>
</div>
