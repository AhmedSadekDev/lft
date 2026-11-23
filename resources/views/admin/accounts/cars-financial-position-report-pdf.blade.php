<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الموقف المالي - السيارات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 11px;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px 0;
            border-bottom: 3px solid #DC143C;
        }
        .header h1 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #333;
        }
        .header .report-date {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .summary {
            background-color: #e3f2fd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .summary-label { font-weight: bold; color: #555; }
        .summary-value { font-size: 16px; color: #d32f2f; font-weight: bold; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 6px;
            text-align: center;
            word-wrap: break-word;
        }
        th {
            background-color: #DC143C;
            color: white;
            font-weight: bold;
        }
        tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .text-danger { color: #d32f2f; font-weight: bold; }
        .text-info { color: #0288d1; font-weight: bold; }
        .footer-row {
            background-color: #e3f2fd !important;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>الموقف المالي - السيارات</h1>
        <div class="report-date">تاريخ التقرير: {{ $reportDate }}</div>
        <div class="report-date">عدد السيارات (رصيد غير صفر): {{ $carsWithDebts->count() }}</div>
    </div>

    <div class="summary">
        <span class="summary-label">صافي إجمالي الرصيد:</span>
        <span class="summary-value">{{ number_format((float) $totalDebts, 2) }} جنيه</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">#</th>
                <th style="width: 55%;">رقم السيارة</th>
                <th style="width: 35%;">الرصيد المستحق</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($carsWithDebts as $index => $car)
                @php $b = (float) $car['balance']; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $car['car_number'] }}</td>
                    <td class="{{ $b >= 0 ? 'text-danger' : 'text-info' }}">
                        {{ number_format($b, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">لا توجد سيارات برصيد غير صفر في هذا التاريخ</td>
                </tr>
            @endforelse
        </tbody>
        @if($carsWithDebts->count() > 0)
            <tfoot>
                <tr class="footer-row">
                    <td colspan="2">صافي الإجمالي</td>
                    <td class="{{ (float) $totalDebts >= 0 ? 'text-danger' : 'text-info' }}">
                        {{ number_format((float) $totalDebts, 2) }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
