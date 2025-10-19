<?php

namespace App\Notifications;

use App\Notifications\Channels\PhpMailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignPasswordNotification extends Notification
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
                    ->action('Assing Password', env('APP_URL') . '/set_password/companies?token='. $this->company->session_id);
    }

    public function toPhpMail($notifiable)
    {
        $url = env('APP_URL') . '/set_password/companies?token='. $this->company->session_id;
        $name = method_exists($notifiable, 'getAttribute') ? ($notifiable->getAttribute('name') ?? '') : '';
        $subject = 'تعيين كلمة المرور - Company';
        $html = '<html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>تعيين كلمة المرور</title></head><body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px"><div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden"><div style="background:#0d6efd;color:#fff;padding:16px;text-align:center">Leader</div><div style="padding:24px"><h2 style="margin-top:0">مرحباً '.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</h2><p>برجاء الضغط على الزر التالي لتعيين كلمة المرور:</p><p style="text-align:center"><a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" style="display:inline-block;background:#0d6efd;color:#fff;padding:12px 20px;border-radius:6px;text-decoration:none">تعيين كلمة المرور</a></p></div><div style="background:#f8f9fa;padding:12px;text-align:center;font-size:12px;color:#6c757d">'.date('Y-m-d H:i:s').'</div></div></body></html>';
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
