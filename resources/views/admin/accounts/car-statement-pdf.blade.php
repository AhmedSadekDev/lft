<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب سيارة - {{ $car->car_number }}</title>
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
        .header .car-name {
            font-size: 14px;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        .header .period {
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px 3px;
            text-align: center;
            word-wrap: break-word;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            font-size: 9px;
            padding: 7px 3px;
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
        .summary-row {
            background-color: #e3f2fd !important;
            font-weight: bold;
            color: #1976d2;
        }
        .final-balance-row {
            background-color: #fff3cd !important;
            font-weight: bold;
        }
        .final-balance-amount {
            color: #d32f2f;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>كشف حساب سيارة</h1>
        <div class="car-name">الاسم: {{ $car->car_number }}</div>
        <div class="period">الحساب في الفترة من {{ $fromDate }} الى {{ $toDate }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">التاريخ</th>
                <th style="width: 5%;">حساب سابق</th>
                <th style="width: 6%;">الخدمة</th>
                <th style="width: 8%;">الوصف</th>
                <th style="width: 7%;">رقم الحاوية</th>
                <th style="width: 6%;">خروج</th>
                <th style="width: 7%;">الوجهة</th>
                <th style="width: 6%;">تعتيق</th>
                <th style="width: 6%;">القيمة</th>
                <th style="width: 6%;">العهدة</th>
                <th style="width: 6%;">الإجمالي</th>
                <th style="width: 6%;">الإجمالي</th>
                <th style="width: 7%;">مدين أو دائن</th>
                <th style="width: 9%;">اجمالي النقلة + حساب سابق</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions ?? [] as $transaction)
                @php
                    $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);
                @endphp
                <tr>
                    <td style="font-size: 8px; line-height: 1.3;">{{ $date->format('Y-m-d') }}<br>{{ $date->format('H:i') }}</td>
                    <td>{{ $transaction['previous_balance'] > 0 ? number_format($transaction['previous_balance'], 2) : '-' }}</td>
                    <td style="font-size: 8px;">{{ $transaction['service'] ?: '-' }}</td>
                    <td style="font-size: 8px;">{{ $transaction['description'] ?: '-' }}</td>
                    <td style="font-size: 8px;">{{ $transaction['container_no'] ?: '-' }}</td>
                    <td style="font-size: 8px;">{{ $transaction['departure'] ?: '-' }}</td>
                    <td style="font-size: 8px;">{{ $transaction['destination'] ?: '-' }}</td>
                    <td style="font-size: 8px;">{{ $transaction['aging'] ?: '-' }}</td>
                    <td>{{ $transaction['value'] > 0 ? number_format($transaction['value'], 2) : '-' }}</td>
                    <td>{{ $transaction['custody'] > 0 ? number_format($transaction['custody'], 2) : '-' }}</td>
                    <td>{{ $transaction['total1'] > 0 ? number_format($transaction['total1'], 2) : '-' }}</td>
                    <td>{{ $transaction['total2'] > 0 ? number_format($transaction['total2'], 2) : '-' }}</td>
                    <td class="{{ $transaction['debit_credit'] == 'مدين' ? 'text-danger' : 'text-success' }}">
                        {{ $transaction['debit_credit'] }}
                    </td>
                    <td class="{{ $transaction['running_balance'] >= 0 ? 'text-danger' : 'text-success' }}" style="font-weight: bold;">
                        {{ number_format($transaction['running_balance'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" style="text-align: center; padding: 20px;">لا توجد حركات في هذه الفترة</td>
                </tr>
            @endforelse

            @if(isset($transactions) && $transactions->count() > 0)
                @php
                    $totalPreviousBalance = $transactions->sum('previous_balance');
                    $totalValue = $transactions->sum('value');
                    $totalCustody = $transactions->sum('custody');
                    $total1 = $transactions->sum('total1');
                    $total2 = $transactions->sum('total2');
                    $finalRunningBalance = $transactions->last()['running_balance'] ?? $finalBalance;
                @endphp
                <tr class="summary-row">
                    <td colspan="2" style="text-align: right; padding-right: 10px; font-size: 9px;">الحساب النهائي يوم {{ $toDate }}</td>
                    <td colspan="7"></td>
                    <td style="color: #1976d2; font-weight: bold;">{{ number_format($totalValue, 2) }}</td>
                    <td style="color: #1976d2; font-weight: bold;">{{ number_format($totalCustody, 2) }}</td>
                    <td style="color: #1976d2; font-weight: bold;">{{ number_format($total1, 2) }}</td>
                    <td style="color: #1976d2; font-weight: bold;">{{ number_format($total2, 2) }}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="final-balance-row">
                    <td colspan="13" style="text-align: right; padding-right: 10px; font-size: 10px;">الرصيد النهائي المستحق</td>
                    <td class="final-balance-amount" style="text-align: center; font-size: 10px;">
                        {{ number_format(abs($finalRunningBalance), 2) }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
