<?php

namespace App\Notifications;

use App\Models\BookingContainer;
use App\Notifications\Channels\PhpMailChannel;
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
        return [PhpMailChannel::class, 'database'];
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


        return (new MailMessage);
    }

    public function toPhpMail($notifiable)
    {
        $status = $this->bookingContainer->status;
        $text = match ($status) {
            1 => 'Booking No. ' . optional($this->bookingContainer->booking)->booking_number . ' was specified',
            2 => 'Container No. ' . $this->bookingContainer->container_no . ' was loaded',
            3 => 'Container No. ' . $this->bookingContainer->container_no . ' was unloaded',
            default => 'Container No. ' . $this->bookingContainer->container_no . ' was changed',
        };
        $subject = 'تحديث حالة الحاوية';
        $html = '<html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>حالة الحاوية</title></head><body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px"><div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden"><div style="background:#20c997;color:#fff;padding:16px;text-align:center">Leader</div><div style="padding:24px"><p>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</p></div><div style="background:#f8f9fa;padding:12px;text-align:center;font-size:12px;color:#6c757d">'.date('Y-m-d H:i:s').'</div></div></body></html>';
        return [
            'subject' => $subject,
            'html' => $html,
        ];
    }


    public function toArray($notifiable)
    {
        return [
            'company_id' => $this->bookingContainer?->booking->company_id,

        ];
    }
}
