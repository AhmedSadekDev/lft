<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Status Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        h1 {
            font-size: 24px;
            color: #007bff;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .footer {
            font-size: 14px;
            color: #888;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>تنبيه حالة الحجز</h1>
        <p>{{ $msg }}</p>

        <table>
            <thead>
                <tr>
                    <th>الشركة</th>
                    <th>الموظف</th>
                    <th>المصنع</th>
                    <th>رقم الحجز</th>
                    <th>رقم الحاوية</th>
                    <th>رقم السيل الملاحى</th>
                    <th>نوع الحاوية</th>
                    <th>خروج</th>
                    <th>تحميل</th>
                    <th>تعتيق</th>
                    <th>التكلفة</th>
                </tr>
            </thead>
            <tbody>

                <tr>
                    <td>{{ $container->booking->company->name ?? '' }}</td>
                    <td>{{ $container->booking->employee->name ?? '' }}</td>
                    <td>{{ $container->booking->booking_number ?? '' }}</td>
                    <td>{{ $container->sail_of_number ?? '' }}</td>
                    <td>{{ $container->container_no ?? '' }}</td>
                    <td>{{ $container->container?->full_name ?? '' }}</td>
                    <td>{{ $container->departure->title ?? '' }}</td>
                    <td>{{ $container->loading->title ?? '' }}</td>
                    <td>{{ $container->aging->title ?? '' }}</td>
                    <td>{{ $container->price ?? '' }}</td>
                </tr>

            </tbody>
        </table>



        <p class="footer">شكراً لاستخدامك تطبيقنا!</p>
    </div>
</body>

</html>
