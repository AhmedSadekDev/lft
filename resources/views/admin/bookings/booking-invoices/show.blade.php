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
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cairo', sans-serif;
            background: #f5f5f5;
            padding: 10px;
            margin: 0;
            overflow-x: hidden;
            overflow-y: auto;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .print-btn, .buttons-container { display: none !important; }

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
                width: 100% !important;
                background-color: #fff !important;
                padding: 20px !important;
                margin-bottom: 20px !important;
                border: 1px solid #000 !important;
                border-bottom: 2px solid #8B4513 !important;
                page-break-inside: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .invoice-wrapper {
                width: 90% !important;
                max-width: 90% !important;
                margin: 0 auto !important;
            }

            .print { box-shadow: none !important; border: none !important; padding: 0 !important; }

            .invoice-section-block { page-break-inside: avoid; }

            .detailed-page-break {
                page-break-before: always;
                break-before: page;
            }

            /* الصفحة الثانية: بدون هيدر/فوتر — أبيض وأسود */
            body.print-detailed .attachments-print-page .letterhead-header,
            body.print-detailed .attachments-print-page .letterhead-footer,
            body.print-detailed .attachments-print-page .watermark {
                display: none !important;
            }

            body.print-combined .letterhead-header,
            body.print-combined .letterhead-footer,
            body.print-combined .watermark {
                display: none !important;
            }

            body.print-combined,
            body.print-combined *,
            body.print-detailed .attachments-print-page,
            body.print-detailed .attachments-print-page * {
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body.print-combined div,
            body.print-combined td,
            body.print-combined th,
            body.print-combined span,
            body.print-combined p,
            body.print-detailed .attachments-print-page div,
            body.print-detailed .attachments-print-page td,
            body.print-detailed .attachments-print-page th,
            body.print-detailed .attachments-print-page span,
            body.print-detailed .attachments-print-page p {
                background-color: #fff !important;
                background-image: none !important;
            }

            body.print-combined table,
            body.print-detailed .attachments-print-page table {
                border: 1px solid #000 !important;
            }

            body.print-combined table th,
            body.print-detailed .attachments-print-page table th {
                background-color: #e0e0e0 !important;
                border: 1px solid #000 !important;
                color: #000 !important;
            }

            body.print-combined table td,
            body.print-detailed .attachments-print-page table td {
                border: 1px solid #000 !important;
            }
        }

        .print-btn {
            background: linear-gradient(135deg, #DC143C 0%, #B22222 100%);
            color: #fff;
            font-family: 'Cairo', sans-serif;
            display: inline-block;
            font-weight: 600;
            border: none;
            padding: 12px 24px;
            font-size: 15px;
            border-radius: 8px;
            cursor: pointer;
            margin: 8px;
            box-shadow: 0 4px 15px rgba(220, 20, 60, 0.3);
        }

        .print-btn.secondary {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            box-shadow: 0 4px 15px rgba(73, 80, 87, 0.3);
        }

        .print-btn.danger-muted {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }

        .buttons-container {
            text-align: center;
            margin-bottom: 15px;
        }

        .invoices-container {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            justify-content: center;
            flex-wrap: wrap;
            margin: 0 auto;
            max-width: 96%;
            padding: 0;
        }

        .invoice-wrapper {
            flex: 1;
            min-width: 42%;
            max-width: 48%;
        }

        @media (max-width: 1200px) {
            .invoice-wrapper {
                min-width: 100%;
                max-width: 100%;
            }
        }

        table { border-collapse: collapse; }
        table tbody tr:nth-child(odd) { background-color: #fff; }
        table tbody tr:nth-child(even) { background-color: #f8f9fa; }
    </style>
    <title>Invoice {{ $invoice->invoice_number ?? '' }}</title>
</head>

<body>
    @php
        $hasPayments = $invoice->invoicePayments()->exists();
        $taxGroup = $taxGroup ?? $printData['tax'];
        $receiptGroup = $receiptGroup ?? $printData['receipt'];
        $additionalGroup = $additionalGroup ?? $printData['additional'];
        $combinedItems = $combinedItems ?? $printData['combined_items'];
        $combinedTotal = $combinedTotal ?? $printData['combined_total'];
        $attachmentsSubtotal = (float) ($receiptGroup['total'] ?? 0) + (float) ($additionalGroup['total'] ?? 0);
    @endphp

    <div class="buttons-container">
        <button type="button" class="btn print-btn" onclick="printInvoiceMode('printableAreaDetailed', false, true)">
            طباعة فاتورة مفصلة (I ثم R+S)
        </button>
        <button type="button" class="btn print-btn secondary" onclick="printInvoiceMode('printableAreaCombined', true, false)">
            طباعة كشف حساب (مجمّع)
        </button>
        @if (!$hasPayments)
            <form method="POST"
                  action="{{ route('booking-invoices.destroy', ['booking_invoice' => $invoice->id]) }}"
                  style="display:inline-block;"
                  onsubmit="return confirm('هل أنت متأكد من حذف الفاتورة؟');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn print-btn danger-muted">حذف الفاتورة</button>
            </form>
        @endif
    </div>

    <div class="invoices-container">
        {{-- الصفحة 1: الفاتورة الضريبية فقط (مع هيدر) --}}
        <div class="invoice-wrapper">
            <div class="print" id="printableAreaDetailedTax"
                 style="width: 100%; margin: 0; padding: 12px 16px; border: 2px solid #DC143C; border-radius: 8px; background: #fff;">
                <div class="invoice" style="overflow: visible;">
                    @include('admin.components.booking-invoices.printing.letterhead', [
                        'document_title' => __('admin.invoice'),
                        'document_number' => $invoice->invoice_number,
                    ])

                    @include('admin.components.booking-invoices.printing.section-block', [
                        'group' => $taxGroup,
                        'border_color' => '#DC143C',
                        'table_title' => 'تفاصيل الفاتورة',
                        'hide_title' => true,
                        'row_context' => 'tax',
                    ])
                </div>
            </div>
        </div>

        {{-- الصفحة 2: الإيصالات + خدمات بياتة (بدون هيدر/فوتر) --}}
        <div class="invoice-wrapper">
            <div class="print attachments-print-page" id="printableAreaDetailedAttachments"
                 style="width: 100%; margin: 0; padding: 12px 16px; border: 2px solid #333; border-radius: 8px; background: #fff;">
                <div class="invoice" style="overflow: visible;">
                    <div style="text-align: center; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 8px;">
                        <div style="font-size: 13px; font-weight: 700;">
                            رقم: {{ $invoice->invoice_number ?? '' }}-R/S
                            &nbsp;|&nbsp;
                            التاريخ: {{ optional($invoice->created_at)->format('d-m-Y') }}
                        </div>
                    </div>

                    @include('admin.components.booking-invoices.printing.section-block', [
                        'group' => $receiptGroup,
                        'border_color' => '#000',
                        'title_bg' => '#e0e0e0',
                        'table_title' => 'الإيصالات',
                        'section_title' => 'الإيصالات',
                        'bw' => true,
                        'row_context' => 'receipt',
                    ])

                    @include('admin.components.booking-invoices.printing.section-block', [
                        'group' => $additionalGroup,
                        'border_color' => '#000',
                        'title_bg' => '#e0e0e0',
                        'table_title' => 'خدمات بياتة',
                        'section_title' => 'خدمات بياتة',
                        'bw' => true,
                        'row_context' => 'additional',
                    ])

                    <div style="margin-top: 10px; border: 2px solid #000; padding: 10px 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; font-size: 13px;">إجمالي الملحقات (إيصالات + بياتة)</span>
                            <span style="font-weight: 700; font-size: 15px;">{{ number_format($attachmentsSubtotal, 2) }} ج.م</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- محتوى الطباعة المفصلة الموحّد (صفحة 1 ثم صفحة 2) --}}
    <div style="display: none;" aria-hidden="true">
        <div id="printableAreaDetailed">
            <div class="tax-print-page">
                @include('admin.components.booking-invoices.printing.letterhead', [
                    'document_title' => __('admin.invoice'),
                    'document_number' => $invoice->invoice_number,
                ])
                @include('admin.components.booking-invoices.printing.section-block', [
                    'group' => $taxGroup,
                    'border_color' => '#DC143C',
                    'table_title' => 'تفاصيل الفاتورة',
                    'hide_title' => true,
                    'row_context' => 'tax',
                ])
            </div>

            <div class="attachments-print-page detailed-page-break" style="padding-top: 12px;">
                <div style="text-align: center; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 8px;">
                    <div style="font-size: 13px; font-weight: 700;">
                        رقم: {{ $invoice->invoice_number ?? '' }}-R/S
                        &nbsp;|&nbsp;
                        التاريخ: {{ optional($invoice->created_at)->format('d-m-Y') }}
                    </div>
                </div>

                @include('admin.components.booking-invoices.printing.section-block', [
                    'group' => $receiptGroup,
                    'border_color' => '#000',
                    'title_bg' => '#e0e0e0',
                    'table_title' => 'الإيصالات',
                    'section_title' => 'الإيصالات',
                    'bw' => true,
                    'row_context' => 'receipt',
                ])

                @include('admin.components.booking-invoices.printing.section-block', [
                    'group' => $additionalGroup,
                    'border_color' => '#000',
                    'title_bg' => '#e0e0e0',
                    'table_title' => 'خدمات بياتة',
                    'section_title' => 'خدمات بياتة',
                    'bw' => true,
                    'row_context' => 'additional',
                ])

                <div style="margin-top: 10px; border: 2px solid #000; padding: 10px 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; font-size: 13px;">إجمالي الملحقات (إيصالات + بياتة)</span>
                        <span style="font-weight: 700; font-size: 15px;">{{ number_format($attachmentsSubtotal, 2) }} ج.م</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Combined / statement print --}}
    <div class="invoice-wrapper" style="display: none;" aria-hidden="true">
        <div class="print" id="printableAreaCombined"
             style="width: 100%; margin: 0; padding: 12px 16px; background: #fff;">
            <div class="invoice" style="overflow: visible;">
                <div style="text-align: center; margin-bottom: 12px; border-bottom: 2px solid #000; padding-bottom: 8px;">
                    <h2 style="font-family: 'Cairo', sans-serif; font-size: 18px; margin: 0 0 4px;">كشف حساب / فاتورة مجمّعة</h2>
                    <div style="font-size: 13px;">
                        رقم: {{ $invoice->invoice_number ?? '' }}
                        &nbsp;|&nbsp;
                        التاريخ: {{ optional($invoice->created_at)->format('d-m-Y') }}
                    </div>
                </div>

                @include('admin.components.booking-invoices.printing.table.layout', [
                    'items' => $combinedItems,
                    'table_title_override' => 'البنود',
                    'bw' => true,
                    'empty_message' => $combinedItems->isEmpty(),
                    'row_context' => null,
                ])

                <div style="margin-top: 10px; border: 2px solid #000; padding: 10px 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; font-size: 14px;">الإجمالي</span>
                        <span style="font-weight: 700; font-size: 16px;">{{ number_format($combinedTotal, 2) }} ج.م</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printInvoiceMode(divName, combined, detailed) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;
            var originalClassName = document.body.className;

            var style = '';
            if (combined) {
                document.body.classList.add('print-combined');
                style = '<style>' +
                    'body { font-family: Cairo, sans-serif; color: #000 !important; }' +
                    '.letterhead-header, .letterhead-footer, .watermark { display: none !important; }' +
                    'div, td, th, span, p { background: #fff !important; background-image: none !important; color: #000 !important; }' +
                    'table { border: 1px solid #000 !important; border-collapse: collapse; width: 100%; }' +
                    'table th { background: #e0e0e0 !important; border: 1px solid #000 !important; color: #000 !important; }' +
                    'table td { border: 1px solid #000 !important; }' +
                    '</style>';
            }

            if (detailed) {
                document.body.classList.add('print-detailed');
                style = '<style>' +
                    'body { font-family: Cairo, sans-serif; }' +
                    '.detailed-page-break { page-break-before: always; break-before: page; }' +
                    '.attachments-print-page .letterhead-header,' +
                    '.attachments-print-page .letterhead-footer,' +
                    '.attachments-print-page .watermark { display: none !important; }' +
                    '.attachments-print-page, .attachments-print-page * { color: #000 !important; }' +
                    '.attachments-print-page div, .attachments-print-page td, .attachments-print-page th,' +
                    '.attachments-print-page span, .attachments-print-page p {' +
                    '  background: #fff !important; background-image: none !important;' +
                    '}' +
                    '.attachments-print-page table { border: 1px solid #000 !important; border-collapse: collapse; width: 100%; }' +
                    '.attachments-print-page table th { background: #e0e0e0 !important; border: 1px solid #000 !important; color: #000 !important; }' +
                    '.attachments-print-page table td { border: 1px solid #000 !important; }' +
                    '</style>';
            }

            document.body.innerHTML = style + printContents;
            window.print();
            document.body.innerHTML = originalContents;
            document.body.className = originalClassName;
        }
    </script>
</body>

</html>
