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
        $html = '<html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>تحديث الحالة</title></head><body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px"><div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden"><div style="background:#0dcaf0;color:#fff;padding:16px;text-align:center">Leader</div><div style="padding:24px"><p>'.htmlspecialchars($msg, ENT_QUOTES, 'UTF-8').'</p></div><div style="background:#f8f9fa;padding:12px;text-align:center;font-size:12px;color:#6c757d">'.date('Y-m-d H:i:s').'</div></div></body></html>';
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
