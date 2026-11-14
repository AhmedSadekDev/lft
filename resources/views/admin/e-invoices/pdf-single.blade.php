<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: right; }
        th { background: #f3f3f3; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h1>فاتورة {{ $invoice->invoice_number }}</h1>
    <p class="muted">تاريخ الإنشاء: {{ optional($invoice->created_at)->format('Y-m-d H:i') }}</p>

    <table>
        <tr>
            <th>رقم الفاتورة</th>
            <td>{{ $row['internal_id'] }}</td>
            <th>رقم الحجز</th>
            <td>{{ $row['booking_number'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>الشركة</th>
            <td>{{ $row['company_name'] ?? '—' }}</td>
            <th>الرقم الضريبي</th>
            <td>{{ $row['company_tax'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>قبل الضريبة</th>
            <td>{{ number_format((float) $row['net_amount'], 2) }}</td>
            <th>ضريبة القيمة المضافة</th>
            <td>{{ number_format((float) $row['vat_amount'], 2) }}</td>
        </tr>
        <tr>
            <th>ضريبة المبيعات</th>
            <td>{{ number_format((float) $row['sales_tax_amount'], 2) }}</td>
            <th>الخصم</th>
            <td>{{ number_format((float) $row['discount_total'], 2) }}</td>
        </tr>
        <tr>
            <th>الإجمالي</th>
            <td>{{ number_format((float) $row['total_amount'], 2) }}</td>
            <th>الحالة</th>
            <td>{{ $row['status'] }}</td>
        </tr>
        <tr>
            <th>المدفوع</th>
            <td>{{ number_format((float) $row['paid_amount'], 2) }}</td>
            <th>المتبقي</th>
            <td>{{ number_format((float) $row['remaining_amount'], 2) }}</td>
        </tr>
    </table>
</body>
</html>
