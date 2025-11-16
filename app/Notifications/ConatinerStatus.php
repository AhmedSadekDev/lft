<?php

namespace App\Notifications;

use App\Notifications\Channels\PhpMailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConatinerStatus extends Notification
{
    use Queueable;

    protected $container;
    protected $msg;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($container, $msg)
    {
        $this->container = $container;
        $this->msg = $msg;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [PhpMailChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage);
    }

    public function toPhpMail($notifiable)
    {
        $subject = 'تحديث حالة الشحنة';

        $msg = (string) $this->msg;
        $msgEsc = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');

        // بيانات مرتبطة بالحاوية والحجز (إن وجدت)
        $booking = $this->container->booking ?? null;
        $companyName = $booking?->company?->name ?? '-';
        $bookingNumber = $booking?->booking_number ?? '-';
        $containerNo = $this->container->container_no ?? '-';
        $sailNumber = $this->container->sail_of_number ?? '-';
        $containerType = $this->container->container?->full_name ?? '-';
        $departure = $this->container->departure?->title ?? '-';
        $loading = $this->container->loading?->title ?? '-';
        $aging = $this->container->aging?->title ?? '-';

        // رابط تتبع الحجز إن وجد
        $trackId = $booking?->booking_number;
        $urlButtonHtml = '';
        if ($trackId) {
            $url = 'https://leaderfortrans.com/book/?track=' . urlencode($trackId);
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
    <title>تحديث حالة الشحنة</title>
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
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        h1 {
            font-size: 24px;
            color: #0dcaf0;
            text-align: center;
            margin-bottom: 20px;
        }

        .message {
            background-color: #e8f9ff;
            border-right: 4px solid #0dcaf0;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }

        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
            word-wrap: break-word;
            font-size: 14px;
        }

        th {
            background-color: #0dcaf0;
            color: #fff;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .button-wrapper {
            text-align: center;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            background-color: #0dcaf0;
            color: #fff;
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            color: #888;
            font-size: 13px;
            margin-top: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>تحديث حالة الشحنة</h1>

        <div class="message">' . $msgEsc . '</div>

        <table>
            <thead>
                <tr>
                    <th>الشركة</th>
                    <th>رقم الحجز</th>
                    <th>رقم الحاوية</th>
                    <th>رقم السيل الملاحي</th>
                    <th>نوع الحاوية</th>
                    <th>خروج</th>
                    <th>تحميل</th>
                    <th>تعتيق</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($bookingNumber, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($containerNo, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($sailNumber, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($containerType, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($departure, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($loading, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($aging, ENT_QUOTES, 'UTF-8') . '</td>
                </tr>
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

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
