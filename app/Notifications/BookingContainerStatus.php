<?php

namespace App\Notifications;

use App\Models\BookingContainer;
use App\Notifications\Channels\PhpMailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingContainerStatus extends Notification
{
    use Queueable;

    protected BookingContainer $bookingContainer;

    public function __construct($bookingContainer)
    {
        $this->bookingContainer = $bookingContainer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [PhpMailChannel::class, 'database'];
    }


    public function toMail($notifiable)
    {
        $status = $this->bookingContainer->status;

        $text = match ($status) {
            1 => "Booking No. " . optional($this->bookingContainer->booking)->booking_number . " was specified",
            2 => "Container No. " . $this->bookingContainer->container_no . " was loaded",
            3 => "Container No. " . $this->bookingContainer->container_no . " was unloaded",
            default => "Container No. " . $this->bookingContainer->container_no . " was changed"
        };


        return (new MailMessage);
    }

    public function toPhpMail($notifiable)
    {
        $status = $this->bookingContainer->status;
        $booking = $this->bookingContainer->booking;

        // رابط تتبع الحجز
        $trackId = $booking?->booking_number;
        $url = $trackId
            ? 'https://leaderfortrans.com/book/?track=' . urlencode($trackId)
            : null;

        $subject = 'تنبيه حالة الحجز';

        // نبني صفوف جدول الحاويات
        $rowsHtml = '';
        if ($booking && $booking->bookingContainers) {
            foreach ($booking->bookingContainers as $item) {
                $companyName = $item->booking?->company?->name ?? '-';
                $employeeName = $item->booking?->employee?->name ?? '-';
                $factoryName = $item->booking?->factory?->name ?? '-';
                $bookingNumber = $item->booking?->booking_number ?? '-';
                $containerNo = $item->container_no ?? '-';
                $sailNumber = $item->sail_of_number ?? '-';
                $containerType = $item->container?->full_name ?? '-';
                $departure = $item->departure?->title ?? '-';
                $loading = $item->loading?->title ?? '-';
                $aging = $item->aging?->title ?? '-';

                // بيانات السائقين والسيارات (مدمجة)
                $driversCarsHtml = '';
                if ($item->delivery_policies && $item->delivery_policies->count()) {
                    foreach ($item->delivery_policies as $policy) {
                        $driverName = $policy->driver?->name ?? '-';
                        $driverPhone = $policy->driver?->phone ?? '-';
                        $carPlate = $policy->car?->plate_number ?? '-';

                        $driversCarsHtml .= '
                                <div class="driver-info">
                                    <div class="driver-name">' . htmlspecialchars($driverName, ENT_QUOTES, 'UTF-8') . '</div>
                                    <div class="driver-phone">' . htmlspecialchars($driverPhone, ENT_QUOTES, 'UTF-8') . '</div>
                                    <div class="car-plate">رقم السيارة: ' . htmlspecialchars($carPlate, ENT_QUOTES, 'UTF-8') . '</div>
                                </div>';
                    }
                } else {
                    $driversCarsHtml = '-';
                }

                $rowsHtml .= '
                    <tr>
                        <td>' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars($factoryName, ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars($bookingNumber, ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars($containerNo, ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars($sailNumber, ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars($containerType, ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars($departure, ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars($loading, ENT_QUOTES, 'UTF-8') . '</td>
                        <td>' . htmlspecialchars($aging, ENT_QUOTES, 'UTF-8') . '</td>
                        <td colspan="2">' . $driversCarsHtml . '</td>
                    </tr>';
            }
        }

        $urlButtonHtml = '';
        if ($url && $trackId) {
            $urlEsc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $urlButtonHtml = '
            <div class="button-wrapper">
                <a href="' . $urlEsc . '" class="btn">تفاصيل الطلب</a>
            </div>';
        }

        $html = '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنبيه حالة الحجز</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
            direction: rtl;
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
            color: #007bff;
            font-weight: bold;
            margin-top: 5px;
            font-size: 13px;
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
                    <th colspan="2">بيانات السائق والسيارة</th>
                </tr>
            </thead>
            <tbody>' . $rowsHtml . '
            </tbody>
        </table>
        ' . $urlButtonHtml . '
        <p class="footer">شكراً لاستخدامك تطبيقنا!</p>
    </div>
</body>
</html>';
        return [
            'subject' => $subject,
            'html' => $html,
        ];
    }


    public function toArray($notifiable)
    {
        return [
            'company_id' => $this->bookingContainer?->booking->company_id,

        ];
    }
}
