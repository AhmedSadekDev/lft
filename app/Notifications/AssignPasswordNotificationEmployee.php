<?php

namespace App\Notifications;

use App\Notifications\Channels\PhpMailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignPasswordNotificationEmployee extends Notification
{
    use Queueable;

    protected $company;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($company)
    {
        $this->company = $company;
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

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->action('Assing Password', env('APP_URL') . '/set_password/employees?token='. $this->company->session_id);
    }

    public function toPhpMail($notifiable)
    {
        $url = env('APP_URL') . '/set_password/employees?token='. $this->company->session_id;
        $name = method_exists($notifiable, 'getAttribute') ? ($notifiable->getAttribute('name') ?? '') : '';
        $subject = 'تعيين كلمة المرور - Employee';
        $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $urlEsc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        $html = '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين كلمة المرور</title>
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
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: right;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 22px;
            color: #0d6efd;
            margin: 0;
        }

        .greeting {
            font-size: 18px;
            margin: 20px 0 10px;
        }

        .text {
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .button-wrapper {
            text-align: center;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            background-color: #0d6efd;
            color: #fff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 14px;
        }

        .btn:hover {
            background-color: #0b5ed7;
        }

        .footer {
            text-align: center;
            color: #888;
            font-size: 12px;
            margin-top: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://admin.leaderfortrans.com/assets/media/logo.png" alt="Leader" class="logo">
            <h1 class="title">تعيين كلمة المرور للموظف</h1>
        </div>

        <p class="greeting">مرحباً ' . $nameEsc . '</p>

        <p class="text">
            تم إنشاء حساب لك كموظف على نظام ليدر، برجاء الضغط على الزر التالي لتعيين كلمة المرور الخاصة بك وإكمال تفعيل الحساب.
        </p>

        <div class="button-wrapper">
            <a href="' . $urlEsc . '" class="btn">تعيين كلمة المرور</a>
        </div>

        <p class="footer">
            إذا لم تكن تتوقع هذا البريد، يمكنك تجاهله بأمان.<br>
            شكراً لاستخدامك أنظمتنا.
        </p>
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
            'company_id' => $this->id,

        ];
    }
}
