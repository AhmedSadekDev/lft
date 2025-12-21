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

                // بيانات السائقين والسيارات (محسّنة)
                $driversCarsHtml = '';
                if ($item->delivery_policies && $item->delivery_policies->count()) {
                    foreach ($item->delivery_policies as $policy) {
                        $driverName = $policy->driver?->name ?? 'غير محدد';
                        $driverPhone = $policy->driver?->phone ?? 'غير محدد';
                        $carPlate = $policy->car?->plate_number ?? 'غير محدد';

                        $driversCarsHtml .= '
                                <div class="driver-card">
                                    <div class="driver-header">
                                        <span class="driver-icon">👤</span>
                                        <span class="driver-name">' . htmlspecialchars($driverName, ENT_QUOTES, 'UTF-8') . '</span>
                                    </div>
                                    <div class="driver-details">
                                        <div class="detail-item">
                                            <span class="detail-label">📞 رقم التليفون:</span>
                                            <span class="detail-value">' . htmlspecialchars($driverPhone, ENT_QUOTES, 'UTF-8') . '</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">🚗 رقم السيارة:</span>
                                            <span class="detail-value car-number">' . htmlspecialchars($carPlate, ENT_QUOTES, 'UTF-8') . '</span>
                                        </div>
                                    </div>
                                </div>';
                    }
                } else {
                    $driversCarsHtml = '<div class="no-driver">لا توجد بيانات متاحة</div>';
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

        $to = $this->bookingContainer->booking?->company?->email ?? $notifiable->email ?? null;
        $html = '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنبيه حالة الحجز</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 40px 20px;
            color: #333;
            direction: rtl;
            min-height: 100vh;
        }

        .email-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            padding: 30px;
            text-align: center;
            color: #fff;
        }

        .header h1 {
            font-size: 28px;
            margin: 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .container {
            padding: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            table-layout: fixed;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #dee2e6;
            word-wrap: break-word;
            font-size: 13px;
        }

        th {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e7f3ff;
            transition: background-color 0.3s ease;
        }

        td {
            vertical-align: top;
        }

        .footer {
            background: #f8f9fa;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }

        .footer-text {
            margin: 5px 0;
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 20px 10px;
            }

            .container {
                padding: 20px;
            }

            table {
                font-size: 11px;
            }

            th, td {
                padding: 8px 4px;
            }

            .driver-card {
                padding: 12px;
            }
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.5);
        }

        .button-wrapper {
            text-align: center;
            margin-top: 20px;
        }

        .driver-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #007bff;
            border-radius: 12px;
            padding: 15px;
            margin: 10px 0;
            text-align: right;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
        }

        .driver-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }

        .driver-icon {
            font-size: 24px;
        }

        .driver-name {
            font-weight: 700;
            color: #007bff;
            font-size: 16px;
        }

        .driver-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            background: #ffffff;
            border-radius: 8px;
            border-right: 3px solid #007bff;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            min-width: 120px;
        }

        .detail-value {
            color: #212529;
            font-size: 15px;
            font-weight: 500;
        }

        .car-number {
            color: #007bff;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 1px;
        }

        .no-driver {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>📦 تنبيه حالة الحجز</h1>
        </div>
        <div class="container">
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
        </div>
        <div class="footer">
            <p class="footer-text">
                <strong>Leader for Trans</strong><br>
                نظام إدارة الشحنات والنقل
            </p>
            <p class="footer-text" style="margin-top: 10px;">
                © ' . date('Y') . ' Leader for Trans. جميع الحقوق محفوظة.
            </p>
        </div>
    </div>
</body>
</html>';
        return [
            'to' => $to,
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
