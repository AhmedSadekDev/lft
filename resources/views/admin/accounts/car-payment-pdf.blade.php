<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>بيان سداد نقلات - {{ $car->car_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: rtl;
            text-align: right;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-section td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .info-section td:first-child {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 30%;
        }
        .shipments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .shipments-table th {
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: center;
            border: 1px solid #333;
        }
        .shipments-table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .shipments-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .total-row {
            background-color: #f0f0f0 !important;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            border-top: 2px solid #333;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>بيان سداد نقلات</h1>
        <p>رقم السيارة: {{ $car->car_number }}</p>
        <p>تاريخ الطباعة: {{ date('Y-m-d H:i') }}</p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td>رقم السيارة:</td>
                <td>{{ $car->car_number }}</td>
            </tr>
            <tr>
                <td>عدد النقلات:</td>
                <td>{{ count($shipmentsData) }}</td>
            </tr>
            <tr>
                <td>إجمالي المبلغ:</td>
                <td style="font-size: 18px; font-weight: bold; color: #d32f2f;">
                    {{ number_format($totalAmount, 2) }} ج.م
                </td>
            </tr>
        </table>
    </div>

    <table class="shipments-table">
        <thead>
            <tr>
                <th>#</th>
                <th>رقم الحاوية</th>
                <th>تاريخ النقلة</th>
                <th>التكلفة</th>
                <th>العهدة</th>
                <th>المصروفات الإضافية</th>
                <th>المسدد</th>
                <th>المتبقي (المدفوع)</th>
                <th>خروج</th>
                <th>تحميل</th>
                <th>تسليم</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shipmentsData as $index => $shipment)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $shipment['container_numbers'] ?: '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($shipment['date'])->format('Y-m-d') }}</td>
                    <td>{{ number_format($shipment['cost'], 2) }}</td>
                    <td>{{ number_format($shipment['financial_custody'], 2) }}</td>
                    <td>{{ number_format($shipment['extra_expenses'], 2) }}</td>
                    <td>{{ number_format($shipment['paid'], 2) }}</td>
                    <td style="font-weight: bold; color: #d32f2f;">{{ number_format($shipment['remaining'], 2) }}</td>
                    <td>{{ $shipment['departure'] ?: '-' }}</td>
                    <td>{{ $shipment['loading'] ?: '-' }}</td>
                    <td>{{ $shipment['aging'] ?: '-' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="7" style="text-align: left;">الإجمالي:</td>
                <td style="font-size: 16px; color: #d32f2f;">{{ number_format($totalAmount, 2) }} ج.م</td>
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
