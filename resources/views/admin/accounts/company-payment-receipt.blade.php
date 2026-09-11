<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>بيان سداد — {{ $company->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 10pt;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 3px solid #333;
            padding-bottom: 16px;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 22px;
            color: #333;
        }
        .info-section table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .info-section td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .info-section td:first-child {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 28%;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 9pt;
        }
        .data-table th {
            background-color: #333;
            color: #fff;
            padding: 10px 6px;
            text-align: center;
            border: 1px solid #333;
        }
        .data-table td {
            padding: 8px 6px;
            border: 1px solid #ddd;
            text-align: center;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .data-table .num {
            white-space: nowrap;
        }
        .total-row td {
            background-color: #f0f0f0 !important;
            font-weight: bold;
        }
        .footer {
            margin-top: 24px;
            text-align: center;
            border-top: 2px solid #333;
            padding-top: 16px;
            font-size: 9pt;
            color: #555;
        }
        .print-toolbar {
            background: #17a2b8;
            color: #fff;
            padding: 12px 16px;
            margin: -10px -10px 20px -10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .print-toolbar button {
            background: #fff;
            color: #117a8b;
            border: none;
            padding: 8px 18px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @if(!empty($showPrintChrome))
    <div class="print-toolbar no-print">
        <span style="font-weight: bold;">بيان سداد — معاينة وطباعة</span>
        <div>
            <button type="button" onclick="window.print()">طباعة</button>
            <button type="button" onclick="window.close()">إغلاق النافذة</button>
        </div>
    </div>
    @endif

    <div class="header">
        <h1>بيان سداد</h1>
        <p><strong>الشركة:</strong> {{ $company->name }}</p>
        <p><strong>تاريخ الطباعة:</strong> {{ date('Y-m-d H:i') }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td>اسم الشركة</td>
                <td>{{ $company->name }}</td>
            </tr>
            <tr>
                <td>عدد بنود السداد</td>
                <td>{{ count($rows) }}</td>
            </tr>
            <tr>
                <td>إجمالي المبلغ</td>
                <td style="font-size: 16px; font-weight: bold; color: #d32f2f;">{{ number_format($totalAmount, 2) }} ج.م</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>تاريخ السداد</th>
                <th>رقم الفاتورة</th>
                <th>رقم الطلب</th>
                <th>المبلغ</th>
                <th>نوع السداد</th>
                <th>البنك</th>
                <th>ملاحظات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
                @php
                    $d = $row['date'] instanceof \Carbon\Carbon ? $row['date'] : \Carbon\Carbon::parse($row['date']);
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="num">{{ $d->format('Y-m-d H:i') }}</td>
                    <td>{{ $row['invoice_number'] ?: '-' }}</td>
                    <td>{{ $row['booking_number'] ?: '-' }}</td>
                    <td class="num" style="font-weight: bold; color: #2e7d32;">{{ number_format($row['value'], 2) }} ج.م</td>
                    <td>{{ ($row['payment_type'] ?? '') === 'check' ? 'شيك' : 'تحويل بنكي' }}</td>
                    <td>{{ $row['bank_name'] ?: '-' }}</td>
                    <td style="font-size: 8.5pt;">{{ $row['notes'] ?: '-' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="text-align: left;">الإجمالي:</td>
                <td class="num" style="color: #d32f2f; font-size: 11pt;">{{ number_format($totalAmount, 2) }} ج.م</td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>تم إنشاء هذا البيان تلقائياً من النظام</p>
        <p>تاريخ: {{ date('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
