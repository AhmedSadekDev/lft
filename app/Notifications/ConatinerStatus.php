<?php

namespace App\Notifications;

use App\Models\Employee;
use App\Notifications\Channels\PhpMailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConatinerStatus extends Notification implements ShouldQueue
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

        // بيانات مرتبطة بالحاوية والحجز (إن وجدت)
        $booking = $this->container->booking ?? null;

        // رابط تتبع الحجز إن وجد
        $trackId = $booking?->booking_number;
        $url = $trackId ? 'https://leaderfortrans.com/book/?track=' . urlencode($trackId) : null;

        // الحصول على البريد من notifiable أولاً (employee أو company)
        // إذا كان notifiable هو employee وليس لديه email، نستخدم email الشركة كبديل
        $to = $notifiable->email ?? null;

        // إذا كان notifiable هو employee وليس لديه email، نستخدم email الشركة
        if (empty($to) && $notifiable instanceof Employee) {
            $to = $this->container->booking?->company?->email ?? null;
        }

        $html = view('email.container-status', [
            'statusMessage' => (string) $this->msg,
            'url' => $url,
            'details' => [
                'الشركة' => $booking?->company?->name ?? '-',
                'رقم الحجز' => $booking?->booking_number ?? '-',
                'رقم الحاوية' => $this->container->container_no ?? '-',
                'رقم السيل الملاحي' => $this->container->sail_of_number ?? '-',
                'نوع الحاوية' => $this->container->container?->full_name ?? '-',
                'خروج' => $this->container->departure?->title ?? '-',
                'تحميل' => $this->container->loading?->title ?? '-',
                'تعتيق' => $this->container->aging?->title ?? '-',
            ],
        ])->render();

        return [
            'to' => $to,
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
