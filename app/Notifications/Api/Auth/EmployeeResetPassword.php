<?php

namespace App\Notifications\Api\Auth;

use App\Notifications\Channels\PhpMailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class EmployeeResetPassword extends Notification
{
    public $token;

    public static $createUrlCallback;

    public static $toMailCallback;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return [PhpMailChannel::class];
    }

    public function toMail($notifiable)
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        return $this->buildMailMessage($this->resetUrl($notifiable));
    }

    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject(Lang::get('Reset Password Notification'))
            ->view('email.reset-password', ['token' => $url]);
    }

    public function toPhpMail($notifiable)
    {
        $otp = (string) $this->token;

        $subject = 'كود إعادة تعيين كلمة المرور - Leader for Trans';

        $html = '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كود التحقق</title>
    <style>body{margin:0;padding:0;font-family:Arial,sans-serif;background-color:#f4f4f4}</style>
    </head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4;padding:20px">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.1)">
                    <tr>
                        <td style="background:linear-gradient(135deg,#4CAF50 0%,#45a049 100%);padding:30px;text-align:center">
                            <h1 style="color:#ffffff;margin:0;font-size:28px">Leader for Trans</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 30px">
                            <h2 style="color:#333;text-align:center;margin:0 0 20px 0">كود التحقق الخاص بك</h2>
                            <p style="color:#666;font-size:16px;line-height:1.6;text-align:center">السلام عليكم ورحمة الله وبركاته</p>
                            <p style="color:#666;font-size:16px;line-height:1.6;text-align:center">استخدم الكود التالي لإعادة تعيين كلمة المرور الخاصة بك:</p>
                            <div style="background:#f9f9f9;border:2px dashed #4CAF50;border-radius:8px;padding:25px;margin:25px 0;text-align:center">
                                <div style="font-size:42px;font-weight:bold;color:#4CAF50;letter-spacing:15px;font-family:monospace">' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '</div>
                            </div>
                            <p style="color:#999;font-size:14px;text-align:center;margin:20px 0">⏱️ هذا الكود صالح لمدة ساعة واحدة فقط</p>
                            <div style="background:#fff3cd;border-right:4px solid #ffc107;padding:15px;margin:20px 0;border-radius:4px">
                                <p style="color:#856404;margin:0;font-size:14px">⚠️ إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد الإلكتروني.</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8f8f8;padding:20px;text-align:center;border-top:1px solid #e0e0e0">
                            <p style="color:#999;font-size:12px;margin:5px 0">© ' . date('Y') . ' Leader for Trans. جميع الحقوق محفوظة.</p>
                            <p style="color:#999;font-size:12px;margin:5px 0">' . date('Y-m-d H:i:s') . '</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        return [
            'subject' => $subject,
            'html' => $html,
            'from' => 'Leader for Trans <booking@leaderfortrans.com>',
            'reply_to' => 'booking@leaderfortrans.com',
        ];
    }

    protected function resetUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        return $this->token;
    }

    public static function createUrlUsing($callback)
    {
        static::$createUrlCallback = $callback;
    }

    public static function toMailUsing($callback)
    {
        static::$toMailCallback = $callback;
    }
}
