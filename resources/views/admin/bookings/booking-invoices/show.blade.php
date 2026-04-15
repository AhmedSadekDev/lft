
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: #f5f5f5;
            padding: 10px;
            margin: 0;
            overflow-x: hidden;
            overflow-y: auto;
        }

        html {
            overflow-x: hidden;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .print-btn {
                display: none !important;
            }

            /* Header and Footer for print */
            @page {
                margin-top: 0;
                margin-bottom: 0;
                margin-left: auto;
                margin-right: auto;
                size: A4;
            }

            .letterhead-header {
                position: relative !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                width: 100% !important;
                height: auto !important;
                min-height: auto !important;
                background-color: #fff !important;
                padding: 20px !important;
                margin-bottom: 20px !important;
                margin-top: 0 !important;
                border: 1px solid #000 !important;
                border-bottom: 2px solid #8B4513 !important;
                z-index: 1000 !important;
                page-break-inside: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .letterhead-footer {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                height: auto !important;
                min-height: 2.5cm !important;
                background-color: #fff !important;
                border-top: 2px solid #8B4513 !important;
                z-index: 1000 !important;
                page-break-inside: avoid !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .invoices-container {
                display: block !important;
                gap: 0 !important;
            }

            .invoice-wrapper {
                width: 90% !important;
                max-width: 90% !important;
                min-width: 90% !important;
                margin: 0 auto !important;
                page-break-after: always;
                margin-bottom: 20px;
            }

            .print {
                display: block;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
            }

            .invoice {
                margin: 0 !important;
                padding-top: 0 !important;
            }

            /* Ensure header is visible in print */
            .invoice > div:first-child {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                page-break-inside: avoid !important;
            }

            /* Ensure letterhead header is visible in print */
            .letterhead-header,
            .letterhead-header * {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* Ensure watermark is visible in print */
            .watermark {
                display: block !important;
                visibility: visible !important;
                opacity: 0.15 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* Ensure header gradient prints */
            div[style*="gradient"] {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* Ensure all elements print correctly */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* Ensure gradients and backgrounds print */
            div[style*="gradient"],
            div[style*="background"] {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Ensure borders and shadows print */
            table, div {
                box-shadow: none !important;
            }

            /* Page break settings */
            .invoice {
                page-break-after: auto;
                page-break-inside: avoid;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            /* Attachments print styles - Black and White, No Header/Footer */
            body.print-attachments .letterhead-header {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                max-height: 0 !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
            }

            body.print-attachments .letterhead-footer {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                max-height: 0 !important;
                position: static !important;
            }

            body.print-attachments,
            body.print-attachments * {
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body.print-attachments div,
            body.print-attachments td,
            body.print-attachments th,
            body.print-attachments span,
            body.print-attachments p {
                background-color: #fff !important;
                background-image: none !important;
                background: #fff !important;
            }

            body.print-attachments table {
                border: 1px solid #000 !important;
            }

            body.print-attachments table th {
                background-color: #e0e0e0 !important;
                border: 1px solid #000 !important;
                color: #000 !important;
            }

            body.print-attachments table td {
                border: 1px solid #000 !important;
            }

            body.print-attachments table tbody tr:nth-child(even) {
                background-color: #f5f5f5 !important;
            }

            body.print-attachments table tbody tr:nth-child(odd) {
                background-color: #fff !important;
            }

            body.print-attachments .watermark {
                display: none !important;
            }

            body.print-attachments div[style*="gradient"] {
                background: #e0e0e0 !important;
                background-image: none !important;
            }
        }

        .print-btn {
            background: linear-gradient(135deg, #DC143C 0%, #B22222 100%);
            color: #fff;
            font-family: 'Cairo', sans-serif;
            display: inline-block;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            line-height: 1.5;
            border-radius: 8px;
            cursor: pointer;
            margin: 10px;
            box-shadow: 0 4px 15px rgba(220, 20, 60, 0.3);
            transition: all 0.3s ease;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 20, 60, 0.4);
        }

        .print-btn:active {
            transform: translateY(0);
        }

        .buttons-container {
            text-align: center;
            margin-bottom: 15px;
            padding: 0;
        }

        .invoices-container {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            justify-content: center;
            flex-wrap: wrap;
            margin: 0 auto;
            max-width: 90%;
            padding: 0;
        }

        .invoice-wrapper {
            flex: 1;
            min-width: 40%;
            max-width: 42%;
        }

        @media (max-width: 1200px) {
            .invoice-wrapper {
                min-width: 100%;
                max-width: 100%;
            }
        }

        table {
            border-collapse: collapse;
        }

        table tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table tbody tr:hover {
            background-color: #ffe6e6;
        }
    </style>
    <title>Invoice</title>
</head>

<body>
    @php
        $hasPayments = $invoice->invoicePayments()->exists();
    @endphp
    <div class="buttons-container">
        <button class="btn print-btn" onclick="printDiv('printableArea')">📄 طباعة الفاتورة</button>
        <button class="btn print-btn" onclick="printDiv('printableAreaAttachments')">📎 طباعة الملحقات</button>
        @if (!$hasPayments)
            <form method="POST"
                  action="{{ route('booking-invoices.destroy', ['booking_invoice' => $invoice->id]) }}"
                  style="display:inline-block;"
                  onsubmit="return confirm('هل أنت متأكد من حذف الفاتورة؟');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn print-btn" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                    🗑️ حذف الفاتورة
                </button>
            </form>
        @endif
    </div>

    <div class="invoices-container">
        <!-- Invoice Section -->
        <div class="invoice-wrapper">
            <div class="print" id="printableArea" style="width: 100%;margin: 0;padding: 10px 15px;border: 2px solid #DC143C; border-radius: 8px; box-shadow: 0 4px 20px rgba(220, 20, 60, 0.15); background: #fff;">
                <!-- First page -->
                <div class="invoice" style="overflow: visible;">
                    {{-- START LETTERHEAD AND HEADER --}}
                    @include('admin.components.booking-invoices.printing.letterhead', [
                        'document_title' => __('admin.invoice'),
                    ])
                    {{-- END LETTERHEAD AND HEADER --}}

                    <div style="margin-bottom: .3rem;">
                        {{-- START TABLE --}}
                        @include('admin.components.booking-invoices.printing.table.layout', [
                            'items' => $fpr,
                        ])
                        {{-- END TABLE --}}
                    </div>

                    {{-- START PRICE --}}
                    @if (count($fpr) <= $fpr_hf_limit)
                        @include('admin.components.booking-invoices.printing.invoice-totals')
                    @endif
                    {{-- END PRICE --}}
                </div>
                <!-- First page -->

                <!-- Middle page(s) -->
                @forelse ($mps as $mpr)
                    <div class="invoice" style="overflow: visible;">
                        <div style="margin-bottom: .3rem;margin-top: .5rem;">
                            {{-- START TABLE --}}
                            @include('admin.components.booking-invoices.printing.table.layout', [
                                'items' => $mpr,
                            ])
                            {{-- END TABLE --}}
                        </div>
                    </div>
                @empty
                @endforelse
                <!-- Middle page(s) -->


                @if (count($fpr) > $fpr_hf_limit)
                    <!-- Last page -->
                    <div class="invoice" style="overflow: visible;">
                        <div style="margin-bottom: .3rem;margin-top: .5rem;">
                            {{-- START TABLE --}}
                            @if (count($lpr) > 0)
                                @include('admin.components.booking-invoices.printing.table.layout', [
                                    'items' => $lpr,
                                ])
                            @endif
                            {{-- END TABLE --}}
                        </div>
                        {{-- START PRICE --}}
                        @include('admin.components.booking-invoices.printing.invoice-totals')
                        {{-- END PRICE --}}
                    </div>
                    <!-- Last page -->
                @endif
            </div>
        </div>

        <!-- Attachments Section -->
        <div class="invoice-wrapper">
            <div class="printAttachments" id="printableAreaAttachments"
                style="width: 100%;margin: 0;padding: 10px 15px;border: 2px solid #dc3545; border-radius: 8px; box-shadow: 0 4px 20px rgba(220, 53, 69, 0.15); background: #fff;">
                <!-- First page -->
                <div class="invoice" style="overflow: visible;">
                    @include('admin.components.booking-invoices.printing.letterhead', [
                        'document_title' => __('admin.attachments'),
                    ])
                    <div style="margin-bottom: .3rem;">
                        @if(isset($attachment_rows) && count($attachment_rows) > 0)
                            @include('admin.components.booking-invoices.printing.table.layout', [
                                'items' => $attachment_rows,
                                'is_attachments' => true,
                            ])
                        @else
                            @include('admin.components.booking-invoices.printing.table.layout', [
                                'items' => [],
                                'is_attachments' => true,
                                'empty_message' => true,
                            ])
                        @endif
                    </div>

                    {{-- START INVOICE SUMMARY FOR ATTACHMENTS --}}
                    @include('admin.components.booking-invoices.printing.attachment-invoice-summary')
                    {{-- END INVOICE SUMMARY FOR ATTACHMENTS --}}
                </div>
                <!-- First page -->
            </div>
        </div>
    </div>

    <script>
        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;
            var originalClassName = document.body.className;

            // Add class for attachments printing (black & white, no header/footer)
            if (divName === 'printableAreaAttachments') {
                document.body.classList.add('print-attachments');
                // Also add inline style to hide header/footer for attachments
                printContents = '<style>.letterhead-header, .letterhead-footer { display: none !important; } * { color: #000 !important; } div, td, th, span, p { background: #fff !important; background-image: none !important; } table { border: 1px solid #000 !important; } table th { background: #e0e0e0 !important; border: 1px solid #000 !important; } table td { border: 1px solid #000 !important; } .watermark { display: none !important; } div[style*="gradient"] { background: #e0e0e0 !important; }</style>' + printContents;
            }

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
            document.body.className = originalClassName;
        }
    </script>
</body>

</html>
