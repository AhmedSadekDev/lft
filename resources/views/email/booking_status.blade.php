<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنبيه حالة الحجز</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        h1 {
            font-size: 26px;
            color: #007bff;
            text-align: center;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            table-layout: fixed;
        }

        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
            word-wrap: break-word;
        }

        th {
            background-color: #007bff;
            color: #fff;
            font-size: 15px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1faff;
        }

        .footer {
            text-align: center;
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .button-wrapper {
            text-align: center;
            margin-top: 20px;
        }

        .driver-info {
            text-align: right;
            padding: 5px 0;
            border-bottom: 1px dashed #ccc;
        }

        .driver-info:last-child {
            border-bottom: none;
        }

        .driver-name {
            font-weight: bold;
            color: #333;
        }

        .driver-phone {
            color: #666;
            font-size: 13px;
        }

        .car-plate {
            color: #000;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>تنبيه حالة الحجز</h1>

        <table>
            <thead>
                <tr>
                    <th>الشركة</th>
                    <th>الموظف</th>
                    <th>المصنع</th>
                    <th>رقم الحجز</th>
                    <th>رقم الحاوية</th>
                    <th>رقم السيل الملاحي</th>
                    <th>نوع الحاوية</th>
                    <th>خروج</th>
                    <th>تحميل</th>
                    <th>تعتيق</th>
                    <th>بيانات السائقين</th>
                    <th>أرقام العربيات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($container->booking->bookingContainers as $item)
                    <tr>
                        <td>{{ $item->booking->company->name ?? '-' }}</td>
                        <td>{{ $item->booking->employee->name ?? '-' }}</td>
                        <td>{{ $item->booking->factory->name ?? '-' }}</td>
                        <td>{{ $item->booking->booking_number ?? '-' }}</td>
                        <td>{{ $item->container_no ?? '-' }}</td>
                        <td>{{ $item->sail_of_number ?? '-' }}</td>
                        <td>{{ $item->container?->full_name ?? '-' }}</td>
                        <td>{{ $item->departure->title ?? '-' }}</td>
                        <td>{{ $item->loading->title ?? '-' }}</td>
                        <td>{{ $item->aging->title ?? '-' }}</td>
                        <td>
                            @forelse($item->delivery_policies as $policy)
                                <div class="driver-info">
                                    <div class="driver-name">{{ $policy->driver?->name ?? '-' }}</div>
                                    <div class="driver-phone">{{ $policy->driver?->phone ?? '-' }}</div>
                                </div>
                            @empty
                                -
                            @endforelse
                        </td>
                        <td>
                            @forelse($item->delivery_policies as $policy)
                                <div class="driver-info car-plate">{{ $policy->car?->plate_number ?? '-' }}</div>
                            @empty
                                -
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="button-wrapper">
            <a href="https://leaderfortrans.com/book/?track={{ $container->booking->booking_number }}" class="btn">تفاصيل الطلب</a>
        </div>

        <p class="footer">شكراً لاستخدامك تطبيقنا!</p>
    </div>
</body>

</html>
