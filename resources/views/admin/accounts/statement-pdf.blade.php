<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب - {{ $company->name }}</title>
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
        .header .company-name {
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
            font-size: 10px;
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
            font-size: 10px;
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
        <h1>كشف حساب</h1>
        <div class="company-name">الاسم: {{ $company->name }}</div>
        <div class="period">الحساب في الفترة من {{ $fromDate }} الى {{ $toDate }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="vertical-align: middle; width: 7%;">التاريخ</th>
                <th colspan="2" style="border-bottom: 2px solid #fff; width: 10%;">حساب سابق</th>
                <th rowspan="2" style="vertical-align: middle; width: 7%;">رقم الطلب</th>
                <th rowspan="2" style="vertical-align: middle; width: 9%;">نوع العملية</th>
                <th rowspan="2" style="vertical-align: middle; width: 6%;">خصم على الفاتورة</th>
                <th rowspan="2" style="vertical-align: middle; width: 6%;">الضريبة</th>
                <th rowspan="2" style="vertical-align: middle; width: 6%;">بيان ملحق</th>
                <th rowspan="2" style="vertical-align: middle; width: 7%;">فاتورة النقل</th>
                <th rowspan="2" style="vertical-align: middle; width: 7%;">القيمة الاجمالية</th>
                <th rowspan="2" style="vertical-align: middle; width: 6%;">تم دفع</th>
                <th rowspan="2" style="vertical-align: middle; width: 10%;">ملاحظات</th>
                <th colspan="2" style="border-bottom: 2px solid #fff; width: 12%;">الحساب الحالي</th>
            </tr>
            <tr>
                <th style="padding: 5px 4px;">مدين</th>
                <th style="padding: 5px 4px;">دائن</th>
                <th style="padding: 5px 4px;">مدين</th>
                <th style="padding: 5px 4px;">دائن</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions ?? [] as $transaction)
                @php
                    $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);
                @endphp
                <tr>
                    <td style="font-size: 9px; line-height: 1.4;">{{ $date->format('Y-m-d') }}<br>{{ $date->format('H:i') }}</td>
                    <td>{{ $transaction['previous_debit'] > 0 ? number_format($transaction['previous_debit'], 2) : '-' }}</td>
                    <td>{{ $transaction['previous_credit'] > 0 ? number_format($transaction['previous_credit'], 2) : '-' }}</td>
                    <td>{{ $transaction['booking_number'] ?: '-' }}</td>
                    <td style="font-size: 9px;">{{ $transaction['type_label'] }}</td>
                    <td>{{ $transaction['discount'] > 0 ? number_format($transaction['discount'], 2) : '-' }}</td>
                    <td>{{ $transaction['tax'] > 0 ? number_format($transaction['tax'], 2) : '-' }}</td>
                    <td>{{ $transaction['attachment_statement'] ?: '-' }}</td>
                    <td>{{ $transaction['transportation'] > 0 ? number_format($transaction['transportation'], 2) : '-' }}</td>
                    <td>{{ $transaction['total'] > 0 ? number_format($transaction['total'], 2) : '-' }}</td>
                    <td>{{ $transaction['paid'] > 0 ? number_format($transaction['paid'], 2) : '-' }}</td>
                    <td style="font-size: 9px;">{{ $transaction['notes'] ?: '-' }}</td>
                    <td class="{{ $transaction['current_debit'] > 0 ? 'text-danger' : '' }}">
                        {{ $transaction['current_debit'] > 0 ? number_format($transaction['current_debit'], 2) : '-' }}
                    </td>
                    <td class="{{ $transaction['current_credit'] > 0 ? 'text-success' : '' }}">
                        {{ $transaction['current_credit'] > 0 ? number_format($transaction['current_credit'], 2) : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" style="text-align: center; padding: 20px;">لا توجد حركات في هذه الفترة</td>
                </tr>
            @endforelse

            @if(isset($transactions) && $transactions->count() > 0)
                @php
                    $totalPreviousDebit = $transactions->sum('previous_debit');
                    $totalPreviousCredit = $transactions->sum('previous_credit');
                    $totalCurrentDebit = $transactions->sum('current_debit');
                    $totalCurrentCredit = $transactions->sum('current_credit');
                    $finalRunningBalance = $transactions->last()['running_balance'] ?? $finalBalance;
                @endphp
                <tr class="summary-row">
                    <td colspan="2" style="text-align: right; padding-right: 10px; font-size: 10px;">الحساب النهائي يوم {{ $toDate }}</td>
                    <td style="color: #1976d2; font-weight: bold;">{{ number_format($totalPreviousDebit, 2) }}</td>
                    <td style="color: #1976d2; font-weight: bold;">{{ number_format($totalPreviousCredit, 2) }}</td>
                    <td colspan="8"></td>
                    <td style="color: #1976d2; font-weight: bold;">{{ number_format($totalCurrentDebit, 2) }}</td>
                    <td style="color: #1976d2; font-weight: bold;">{{ number_format($totalCurrentCredit, 2) }}</td>
                </tr>
                <tr class="final-balance-row">
                    <td colspan="12" style="text-align: right; padding-right: 10px; font-size: 11px;">الرصيد النهائي المستحق</td>
                    <td class="final-balance-amount" style="text-align: center; font-size: 11px;">
                        {{ number_format(abs($finalRunningBalance), 2) }}
                    </td>
                    <td style="text-align: center; color: #d32f2f; font-weight: bold; font-size: 10px;">{{ $finalRunningBalance >= 0 ? 'مدين' : 'دائن' }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
