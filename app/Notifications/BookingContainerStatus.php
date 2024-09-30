<?php

namespace App\Notifications;

use App\Models\BookingContainer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingContainerStatus extends Notification
{
    use Queueable;

    protected BookingContainer $bookingContainer;

    public function __construct($bookingContainer)
    {
        $this->bookingContainer = $bookingContainer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }


    public function toMail($notifiable)
    {
        $status = $this->bookingContainer->status;

        $text = match ($status) {
            1 => "Booking No. " . optional($this->bookingContainer->booking)->booking_number . " was specified",
            2 => "Container No. " . $this->bookingContainer->container_no . " was loaded",
            3 => "Container No. " . $this->bookingContainer->container_no . " was unloaded",
            default => "Container No. " . $this->bookingContainer->container_no . " was changed"
        };


        return (new MailMessage)
            ->line($text)
            ->line('Thank you for using our application!');
    }


    public function toArray($notifiable)
    {
        return [
            'company_id' => $this->bookingContainer?->booking->company_id,

        ];
    }
}
