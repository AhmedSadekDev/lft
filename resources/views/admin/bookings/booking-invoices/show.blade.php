
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
                margin-top: 4.5cm;
                margin-bottom: 4.5cm;
                margin-left: 0;
                margin-right: 0;
            }

            .header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 4cm;
                background-color: #fff;
                z-index: 1000;
            }

            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 4cm;
                background-color: #fff;
                z-index: 1000;
            }

            .invoices-container {
                display: block !important;
                gap: 0 !important;
            }

            .invoice-wrapper {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 100% !important;
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
        }

        .print-btn {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
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
            max-width: 100%;
            padding: 0;
        }

        .invoice-wrapper {
            flex: 1;
            min-width: 45%;
            max-width: 48%;
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
            background-color: #e7f3ff;
        }
    </style>
    <title>Invoice</title>
</head>

<body>
    <div class="buttons-container">
        <button class="btn print-btn" onclick="printDiv('printableArea')">📄 طباعة الفاتورة</button>
        <button class="btn print-btn" onclick="printDiv('printableAreaAttachments')">📎 طباعة الملحقات</button>
    </div>

    <div class="invoices-container">
        <!-- Invoice Section -->
        <div class="invoice-wrapper">
            <div class="print" id="printableArea" style="width: 100%;margin: 0;padding: 10px 15px;border: 2px solid #007bff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0, 123, 255, 0.15); background: #fff;">
                <!-- First page -->
                <div class="invoice" style="overflow: visible;">
                    {{-- START HEADER --}}
                    @include('admin.components.booking-invoices.printing.header', [
                        'document_title' => __('admin.invoice'),
                    ])
                    {{-- END HEADER --}}

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
                    @include('admin.components.booking-invoices.printing.header', [
                        'document_title' => __('admin.attachments'),
                    ])
                    <div style="margin-bottom: .3rem;">
                        @if(count($booking->expenses) > 0)
                            @include('admin.components.booking-invoices.printing.table.expenses-row')
                        @endif
                        @if(count($attachment_rows) > 0)
                            @include('admin.components.booking-invoices.printing.table.layout', [
                                'items' => $attachment_rows,
                                'is_attachments' => true,
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

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
</body>

</html>
