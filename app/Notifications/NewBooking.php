<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Channels\PhpMailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBooking extends Notification
{
    use Queueable;

    protected Booking $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [PhpMailChannel::class, 'database'];
    }


    public function toMail($notifiable)
    {
        $track_id = $this->booking->booking_number;
        return (new MailMessage)
            ->subject('إشعار حجز جديد')
            ->action('عرض تفاصيل الحجز', "https://leaderfortrans.com/book/?track=$track_id");
    }

    public function toPhpMail($notifiable)
    {
        $trackId = $this->booking->booking_number;
        $url = 'https://leaderfortrans.com/book/?track=' . urlencode($trackId);
        $subject = 'إشعار حجز جديد';
        $count = (string) $this->booking->bookingContainers->count();

        // Prepare main booking data
        $factoryName = $this->booking->factory_name ?? 'غير محدد';
        $employeeName = $this->booking->employee_name ?? 'غير محدد';
        $shippingAgentTitle = $this->booking->shippingAgent ? $this->booking->shippingAgent->title : 'غير محدد';
        $certificateNumber = $this->booking->certificate_number ?? 'غير محدد';
        $typeOfAction = function_exists('TypeOfAction')
            ? __('actions.' . TypeOfAction($this->booking->type_of_action))
            : 'غير محدد';

        // Escape values
        $trackIdEsc = htmlspecialchars($trackId, ENT_QUOTES, 'UTF-8');
        $countEsc = htmlspecialchars($count, ENT_QUOTES, 'UTF-8');
        $urlEsc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $factoryNameEsc = htmlspecialchars($factoryName, ENT_QUOTES, 'UTF-8');
        $employeeNameEsc = htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8');
        $shippingAgentTitleEsc = htmlspecialchars($shippingAgentTitle, ENT_QUOTES, 'UTF-8');
        $certificateNumberEsc = htmlspecialchars($certificateNumber, ENT_QUOTES, 'UTF-8');
        $typeOfActionEsc = htmlspecialchars($typeOfAction, ENT_QUOTES, 'UTF-8');

        // Build containers rows
        $containersRows = '';
        foreach ($this->booking->bookingContainers as $container) {
            $containerNumber = $container->container_number ?? '-';
            $containerSize = $container->container ? $container->container->type : '-';
            $arrivalDate = $container->arrival_date ?? '-';

            $containerNumberEsc = htmlspecialchars($containerNumber, ENT_QUOTES, 'UTF-8');
            $containerSizeEsc = htmlspecialchars($containerSize, ENT_QUOTES, 'UTF-8');
            $arrivalDateEsc = htmlspecialchars($arrivalDate, ENT_QUOTES, 'UTF-8');

            $containersRows .= '
                        <tr>
                            <td>' . $containerNumberEsc . '</td>
                            <td>' . $containerSizeEsc . '</td>
                            <td>' . $arrivalDateEsc . '</td>
                        </tr>';
        }

        $html = '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إشعار حجز جديد</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            background-color: #f4f6f8;
            padding: 0;
            margin: 0;
            color: #333;
        }

        .email-wrapper {
            max-width: 650px;
            margin: 30px auto;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.05);
        }

        .email-header {
            background-color: #007bff;
            padding: 20px;
            text-align: center;
        }

        .email-header img {
            max-height: 60px;
        }

        .email-body {
            padding: 30px;
        }

        .email-body h2 {
            margin-top: 0;
            color: #007bff;
        }

        .email-body p {
            margin: 10px 0;
            line-height: 1.6;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .details-table th,
        .details-table td {
            text-align: right;
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .details-table th {
            width: 30%;
            background-color: #f9fafb;
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }

        .email-footer {
            text-align: center;
            font-size: 14px;
            color: #777;
            padding: 20px;
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <img src="https://cloudymenue.cloudy-digital.com/assets/media/logo.png" alt="Logo">
        </div>
        <div class="email-body">
            <h2>مرحباً!</h2>
            <p>تم إضافة حجز جديد بالمعلومات التالية:</p>
            <table class="details-table">
                <tr>
                    <th>رقم الحجز</th>
                    <td>' . $trackIdEsc . '</td>
                </tr>
                <tr>
                    <th>اسم المصنع</th>
                    <td>' . $factoryNameEsc . '</td>
                </tr>
                <tr>
                    <th>اسم الموظف المسؤول</th>
                    <td>' . $employeeNameEsc . '</td>
                </tr>
                <tr>
                    <th>وكيل الشحن</th>
                    <td>' . $shippingAgentTitleEsc . '</td>
                </tr>
                <tr>
                    <th>رقم الشهادة</th>
                    <td>' . $certificateNumberEsc . '</td>
                </tr>
                <tr>
                    <th>نوع العملية</th>
                    <td>' . $typeOfActionEsc . '</td>
                </tr>
                <tr>
                    <th>عدد الحاويات</th>
                    <td>' . $countEsc . '</td>
                </tr>
            </table>';

        if ($this->booking->bookingContainers->count()) {
            $html .= '
            <p style="margin-top:20px;"><strong>تفاصيل الحاويات:</strong></p>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>رقم الحاوية</th>
                        <th>مقاس الحاوية</th>
                        <th>الوصول</th>
                    </tr>
                </thead>
                <tbody>' . $containersRows . '
                </tbody>
            </table>';
        }

        $html .= '
            <p>
                <a class="btn" href="' . $urlEsc . '">عرض تفاصيل الحجز</a>
            </p>
        </div>
        <div class="email-footer">
            شكراً لاستخدامك تطبيقنا!
        </div>
    </div>
</body>
</html>';
        $to = $notifiable->email ?? null;
        return [
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'company_id' => $this->booking->company_id,
        ];
    }
}
