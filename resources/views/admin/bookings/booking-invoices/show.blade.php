
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
        @media print {
            .header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 4cm;
                /* Adjust the height as needed */
                background-color: #ccc;
                /* Set your header background color */
            }

            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 4cm;
                /* Adjust the height as needed */
                background-color: #ccc;
                /* Set your footer background color */
            }

            .print {
                height: 21.7cm;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .invoice {
                margin: 4cm 0 4.2cm;
            }
        }

        table tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        table tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .print-btn {
            background-color: #007bff;
            color: #fff;
            font-family: 'Cairo', sans-serif;
            display: block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            cursor: pointer;
            margin: 1rem auto 0;
        }
    </style>
    <title>Invoice</title>
</head>

<body>


    <button class="btn print-btn" style="" onclick="printDiv('printableArea')">طباعة الفاتورة</button>


    <button class="btn print-btn" onclick="printDiv('printableAreaAttachments')">طباعة الملحقات</button>

    <div class="print" id="printableArea" style="width: 21cm;margin: 10px auto;padding: 0 5mm;border: 1px solid #5d5d5d;">
        <div class="header">
            <!-- Header content goes here -->
        </div>
        <!-- First page -->
        <div class="invoice" style="max-height: 21.7cm;overflow-y: hidden;">
            {{-- START HEADER --}}
            @include('admin.components.booking-invoices.printing.header', [
                'document_title' => __('admin.invoice'),
            ])
            {{-- END HEADER --}}

            <div style="max-height: 14cm;overflow-y: hidden;margin-bottom: .25cm;">
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
            <div class="invoice" style="max-height: 21.7cm;overflow-y: hidden">
                <div style="max-height: 18cm;overflow-y: hidden;margin-bottom: .25cm;margin-top: 4cm;">
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
            <div class="invoice" style="max-height: 21.7cm;overflow-y: hidden">
                <div style="max-height: 14cm;overflow-y: hidden;margin-bottom: .25cm;margin-top: 4cm;">
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

    <div class="printAttachments" id="printableAreaAttachments"
        style="width: 21cm;margin: auto;padding: 0 5mm;border: 1px solid #5d5d5d;">
        <!-- First page -->
        <div class="invoice">
            @include('admin.components.booking-invoices.printing.header', [
                'document_title' => __('admin.attachments'),
            ])
            <div style="margin-bottom: .25cm;">
                @if(count($booking->expenses) > 0)
                    @include('admin.components.booking-invoices.printing.table.expenses-row')
                @endif
                @if(count($attachment_rows) > 0)
                    @include('admin.components.booking-invoices.printing.table.layout', [
                        'items' => $attachment_rows,
                    ])
                @endif
            </div>
        </div>
        <!-- First page -->
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
