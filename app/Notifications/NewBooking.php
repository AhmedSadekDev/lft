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
        return (new MailMessage)
            ->line('لديك حجز جديد برقم الحجز ' . $this->booking->booking_number . ' و ' . $this->booking->bookingContainers->count() . ' حاوية')
            ->line('شكراً لاستخدامك تطبيقنا!');
    }


    public function toArray($notifiable)
    {
        return [
            'company_id' => $this->booking->company_id,
        ];
    }
}
