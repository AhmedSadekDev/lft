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
        $html = '<html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>إشعار حجز جديد</title></head><body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px"><div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden"><div style="background:#198754;color:#fff;padding:16px;text-align:center">Leader</div><div style="padding:24px"><h2 style="margin-top:0">مرحباً!</h2><p>تم إضافة حجز جديد بالمعلومات التالية:</p><ul><li>رقم الحجز: '.htmlspecialchars($trackId, ENT_QUOTES, 'UTF-8').'</li><li>عدد الحاويات: '.htmlspecialchars($count, ENT_QUOTES, 'UTF-8').'</li></ul><p style="text-align:center"><a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" style="display:inline-block;background:#198754;color:#fff;padding:12px 20px;border-radius:6px;text-decoration:none">عرض تفاصيل الحجز</a></p></div><div style="background:#f8f9fa;padding:12px;text-align:center;font-size:12px;color:#6c757d">'.date('Y-m-d H:i:s').'</div></div></body></html>';
        return [
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
