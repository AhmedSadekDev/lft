<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الأرباح والخسائر</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 11px;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px 0;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }
        .header .period {
            font-size: 12px;
            color: #666;
        }
        .summary {
            background-color: #e3f2fd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .summary h5 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #1976d2;
        }
        .summary-row {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item strong {
            display: block;
            margin-bottom: 5px;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 4px;
            text-align: center;
            word-wrap: break-word;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            font-size: 9px;
            padding: 8px 4px;
        }
        tbody tr {
            background-color: #fff;
        }
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-danger {
            color: #d32f2f;
            font-weight: bold;
        }
        .text-success {
            color: #388e3c;
            font-weight: bold;
        }
        .text-primary {
            color: #1976d2;
            font-weight: bold;
        }
        .summary-row-final {
            background-color: #e3f2fd !important;
            font-weight: bold;
        }
        .expenses-list {
            font-size: 8px;
            text-align: right;
            padding-right: 5px;
        }
        .expenses-list ul {
            margin: 0;
            padding-right: 15px;
            list-style: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير الأرباح والخسائر</h1>
        <div class="period">الفترة من {{ $fromDate }} الى {{ $toDate }}</div>
    </div>

    <div class="summary">
        <h5>ملخص التقرير</h5>
        <div class="summary-row">
            <div class="summary-item">
                <strong>إجمالي التكلفة</strong>
                <span class="text-danger" style="font-size: 13px;">{{ number_format($totalCost, 2) }} ج.م</span>
            </div>
            <div class="summary-item">
                <strong>إجمالي الإيرادات</strong>
                <span class="text-primary" style="font-size: 13px;">{{ number_format($totalRevenue, 2) }} ج.م</span>
            </div>
            <div class="summary-item">
                <strong>صافي الربح/الخسارة</strong>
                <span class="{{ $totalProfitLoss >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 13px;">
                    {{ number_format($totalProfitLoss, 2) }} ج.م
                </span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 7%;">رقم الطلب</th>
                <th style="width: 9%;">رقم الفاتورة</th>
                <th style="width: 12%;">اسم الشركة</th>
                <th style="width: 8%;">تاريخ الفاتورة</th>
                <th style="width: 30%;">وصف المصروفات</th>
                <th style="width: 10%;">التكلفة الفعلية</th>
                <th style="width: 10%;">سعر الفاتورة</th>
                <th style="width: 10%;">الربح/الخسارة</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reportData ?? [] as $index => $row)
                @php
                    $date = $row['invoice_date'] instanceof \Carbon\Carbon ? $row['invoice_date'] : \Carbon\Carbon::parse($row['invoice_date']);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['booking_number'] }}</td>
                    <td>{{ $row['invoice_number'] }}</td>
                    <td style="font-size: 8px;">{{ $row['company_name'] }}</td>
                    <td style="font-size: 8px;">{{ $date->format('Y-m-d') }}</td>
                    <td class="expenses-list">
                        @if(isset($row['expenses_details']) && count($row['expenses_details']) > 0)
                            <ul>
                                @foreach($row['expenses_details'] as $expense)
                                    <li>• {{ $expense['description'] }}: {{ number_format($expense['value'], 2) }} ج.م</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="text-muted">لا توجد مصروفات</span>
                        @endif
                    </td>
                    <td class="text-danger">{{ number_format($row['total_cost'], 2) }}</td>
                    <td class="text-primary">{{ number_format($row['invoice_total'], 2) }}</td>
                    <td class="{{ $row['profit_loss'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($row['profit_loss'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">لا توجد بيانات في هذه الفترة</td>
                </tr>
            @endforelse

            @if(isset($reportData) && $reportData->count() > 0)
                <tr class="summary-row-final">
                    <td colspan="6" style="text-align: center; padding-right: 10px;">الإجمالي</td>
                    <td class="text-danger">{{ number_format($totalCost, 2) }}</td>
                    <td class="text-primary">{{ number_format($totalRevenue, 2) }}</td>
                    <td class="{{ $totalProfitLoss >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($totalProfitLoss, 2) }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
