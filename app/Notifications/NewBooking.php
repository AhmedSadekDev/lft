<?php

namespace App\Notifications;

use App\Models\Booking;
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
        return ['mail', 'database'];
    }


    public function toMail($notifiable)
    {
        $track_id = $this->booking->booking_number;
        return (new MailMessage)
            ->subject('إشعار حجز جديد')
            ->greeting('مرحباً!')
            ->line('تم إضافة حجز جديد بالمعلومات التالية:')
            ->line('رقم الحجز: ' . $this->booking->booking_number)
            ->line('عدد الحاويات: ' . $this->booking->bookingContainers->count())
            ->action('عرض تفاصيل الحجز', "https://leaderfortrans.com/book/?track=$track_id")
            ->line('شكراً لاستخدامك تطبيقنا!');
    }

    public function toArray($notifiable)
    {
        return [
            'company_id' => $this->booking->company_id,
        ];
    }
}
