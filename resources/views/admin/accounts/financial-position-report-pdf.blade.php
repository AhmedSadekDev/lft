<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الموقف المالي - الشركات المدينة</title>
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
            margin-bottom: 20px;
            padding: 15px 0;
            border-bottom: 3px solid #4CAF50;
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
        .summary-item {
            display: inline-block;
            margin-left: 30px;
        }
        .summary-label {
            font-weight: bold;
            color: #555;
        }
        .summary-value {
            font-size: 16px;
            color: #d32f2f;
            font-weight: bold;
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
        }
        .footer-row {
            background-color: #e3f2fd !important;
            font-weight: bold;
            color: #1976d2;
        }
        .footer-total {
            color: #d32f2f;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير الموقف المالي - الشركات المدينة</h1>
        <div class="report-date">تاريخ التقرير: {{ $reportDate }}</div>
        <div class="report-date">عدد الشركات المدينة: {{ $companiesWithDebts->count() }}</div>
    </div>

    <div class="summary">
        <div class="summary-item">
            <span class="summary-label">إجمالي المبالغ المستحقة:</span>
            <span class="summary-value">{{ number_format($totalDebts, 2) }} جنيه</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">اسم الشركة</th>
                <th style="width: 20%;">البريد الإلكتروني</th>
                <th style="width: 15%;">الهاتف</th>
                <th style="width: 15%;">إجمالي الفواتير</th>
                <th style="width: 15%;">إجمالي المدفوعات</th>
                <th style="width: 15%;">الرصيد المستحق</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($companiesWithDebts as $index => $company)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-weight: bold; text-align: right; padding-right: 10px;">{{ $company['name'] }}</td>
                    <td style="font-size: 8px;">{{ $company['email'] }}</td>
                    <td>{{ $company['phone'] }}</td>
                    <td>{{ number_format($company['total_invoices'], 2) }}</td>
                    <td class="text-success">{{ number_format($company['total_payments'], 2) }}</td>
                    <td class="text-danger" style="font-weight: bold; font-size: 10px;">
                        {{ number_format($company['balance'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">
                        لا توجد شركات مدينة في هذا التاريخ
                    </td>
                </tr>
            @endforelse

            @if($companiesWithDebts->count() > 0)
                <tr class="footer-row">
                    <td colspan="4" style="text-align: right; padding-right: 15px; font-size: 10px;">الإجمالي:</td>
                    <td>{{ number_format($companiesWithDebts->sum('total_invoices'), 2) }}</td>
                    <td>{{ number_format($companiesWithDebts->sum('total_payments'), 2) }}</td>
                    <td class="footer-total">{{ number_format($totalDebts, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: center; font-size: 9px; color: #666;">
        <p>تم إنشاء التقرير في: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
