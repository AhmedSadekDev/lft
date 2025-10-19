<?php

namespace App\Notifications;

use App\Notifications\Channels\PhpMailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeCompany extends Notification
{
    use Queueable;

    protected $company;

    /**
     * Create a new notification instance.
     *
     * @param  $company
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
        $name = $this->company->name ?? '';
        $subject = 'مرحباً بك معنا';
        $html = '<html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>مرحباً</title></head><body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px"><div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden"><div style="background:#6f42c1;color:#fff;padding:16px;text-align:center">Leader</div><div style="padding:24px"><h2 style="margin-top:0">مرحباً '.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</h2><p>شكراً لتسجيلك لدينا. يسعدنا انضمامك.</p></div><div style="background:#f8f9fa;padding:12px;text-align:center;font-size:12px;color:#6c757d">'.date('Y-m-d H:i:s').'</div></div></body></html>';
        return [
            'subject' => $subject,
            'html' => $html,
        ];
    }
}
