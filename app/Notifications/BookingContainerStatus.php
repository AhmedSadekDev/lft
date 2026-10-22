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
        $booking = $this->bookingContainer->booking;

        // رابط تتبع الحجز
        $trackId = $booking?->trackCode();
        $url = $trackId
            ? 'https://leaderfortrans.com/book/?track=' . urlencode($trackId)
            : null;

        $subject = 'تنبيه حالة الحجز';

        $summary = [
            'الشركة' => $booking?->company?->name ?? '-',
            'الموظف' => $booking?->employee?->name ?? '-',
            'المصنع' => $booking?->factory?->name ?? '-',
            'الرقم الداخلي' => $trackId ?? '-',
        ];

        $containers = [];
        if ($booking && $booking->bookingContainers) {
            foreach ($booking->bookingContainers as $item) {
                $drivers = [];
                if ($item->delivery_policies && $item->delivery_policies->count()) {
                    foreach ($item->delivery_policies as $policy) {
                        $drivers[] = [
                            'name' => $policy->driver?->name ?? 'غير محدد',
                            'phone' => $policy->driver?->phone ?? 'غير محدد',
                            'plate' => $policy->car?->plate_number ?? 'غير محدد',
                        ];
                    }
                }

                $containers[] = [
                    'details' => [
                        'رقم الحاوية' => $item->container_no ?? '-',
                        'رقم السيل الملاحي' => $item->sail_of_number ?? '-',
                        'نوع الحاوية' => $item->container?->full_name ?? '-',
                        'خروج' => $item->departure?->title ?? '-',
                        'تحميل' => $item->loading?->title ?? '-',
                        'تعتيق' => $item->aging?->title ?? '-',
                    ],
                    'drivers' => $drivers,
                ];
            }
        }

        $html = view('email.booking-container-status', [
            'summary' => $summary,
            'containers' => $containers,
            'url' => $url,
        ])->render();

        $to = $this->bookingContainer->booking?->company?->email ?? $notifiable->email ?? null;
        return [
            'to' => $to,
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
