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
            font-size: 10pt;
            padding: 8px;
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
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8.5pt;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 5px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.35;
        }
        td.text-wrap {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            font-size: 8pt;
        }
        td.num, th.num {
            white-space: nowrap;
            font-size: 8.5pt;
        }
        td.date-cell {
            white-space: nowrap;
            font-size: 8.5pt;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            font-size: 8pt;
            padding: 8px 5px;
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

    <table class="main-table">
        <thead>
            <tr>
                <th class="num" style="width: 7%;">التاريخ</th>
                <th class="num" style="width: 6%;">حساب سابق</th>
                <th style="width: 6%;">الخدمة</th>
                <th style="width: 8%;">الوصف</th>
                <th style="width: 8%;">رقم الحاوية</th>
                <th style="width: 6%;">خروج</th>
                <th style="width: 6%;">الوجهة</th>
                <th style="width: 6%;">تعتيق</th>
                <th class="num" style="width: 7%;">القيمة</th>
                <th class="num" style="width: 7%;">العهدة</th>
                <th class="num" style="width: 7%;">الإجمالي</th>
                <th class="num" style="width: 7%;">الإجمالي</th>
                <th style="width: 6%;">مدين أو دائن</th>
                <th class="num" style="width: 9%;">اجمالي النقلة + حساب سابق</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions ?? [] as $transaction)
                @php
                    $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);
                @endphp
                <tr>
                    <td class="date-cell">{{ $date->format('Y-m-d') }} {{ $date->format('H:i') }}</td>
                    <td class="num">{{ $transaction['previous_balance'] > 0 ? number_format($transaction['previous_balance'], 2) : '-' }}</td>
                    <td class="text-wrap">{{ $transaction['service'] ?: '-' }}</td>
                    <td class="text-wrap">{{ $transaction['description'] ?: '-' }}</td>
                    <td class="text-wrap">{{ $transaction['container_no'] ?: '-' }}</td>
                    <td class="text-wrap">{{ $transaction['departure'] ?: '-' }}</td>
                    <td class="text-wrap">{{ $transaction['destination'] ?: '-' }}</td>
                    <td class="text-wrap">{{ $transaction['aging'] ?: '-' }}</td>
                    <td class="num">{{ $transaction['value'] > 0 ? number_format($transaction['value'], 2) : '-' }}</td>
                    <td class="num">{{ $transaction['custody'] > 0 ? number_format($transaction['custody'], 2) : '-' }}</td>
                    <td class="num">{{ $transaction['total1'] > 0 ? number_format($transaction['total1'], 2) : '-' }}</td>
                    <td class="num">{{ $transaction['total2'] > 0 ? number_format($transaction['total2'], 2) : '-' }}</td>
                    <td class="num {{ $transaction['debit_credit'] == 'مدين' ? 'text-danger' : 'text-success' }}">
                        {{ $transaction['debit_credit'] }}
                    </td>
                    <td class="num {{ $transaction['running_balance'] >= 0 ? 'text-danger' : 'text-success' }}" style="font-weight: bold;">
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
                    <td colspan="2" style="text-align: right; padding-right: 10px; font-size: 9pt;">الحساب النهائي يوم {{ $toDate }}</td>
                    <td colspan="7"></td>
                    <td class="num" style="color: #1976d2; font-weight: bold;">{{ number_format($totalValue, 2) }}</td>
                    <td class="num" style="color: #1976d2; font-weight: bold;">{{ number_format($totalCustody, 2) }}</td>
                    <td class="num" style="color: #1976d2; font-weight: bold;">{{ number_format($total1, 2) }}</td>
                    <td class="num" style="color: #1976d2; font-weight: bold;">{{ number_format($total2, 2) }}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="final-balance-row">
                    <td colspan="13" style="text-align: right; padding-right: 10px; font-size: 9.5pt;">الرصيد النهائي المستحق</td>
                    <td class="final-balance-amount num" style="text-align: center; font-size: 10pt;">
                        {{ number_format(abs($finalRunningBalance), 2) }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
