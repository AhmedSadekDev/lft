<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 8px; }
        .meta { color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: right; }
        th { background: #f3f3f3; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
@foreach($rows as $index => $row)
    <div class="{{ $index < $rows->count() - 1 ? 'page-break' : '' }}">
        <h1>فاتورة {{ $row['internal_id'] }}</h1>
        <div class="meta">تاريخ الإنشاء: {{ $row['date_issued'] ?? '—' }}</div>
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
                <th>البائع</th>
                <td>{{ $row['seller_name'] ?? '—' }}</td>
                <th>ضريبة البائع</th>
                <td>{{ $row['seller_tax'] ?? '—' }}</td>
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
    </div>
@endforeach
</body>
</html>
