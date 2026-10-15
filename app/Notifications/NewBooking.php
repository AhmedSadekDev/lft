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

        // Prepare main booking data (factory and employee from relations; fallback to columns)
        $factoryName = $this->booking->factory?->name ?? $this->booking->factory_name ?? 'غير محدد';
        $employeeName = $this->booking->employee?->name ?? $this->booking->employee_name ?? 'غير محدد';
        $shippingAgentTitle = $this->booking->shippingAgent ? $this->booking->shippingAgent->title : 'غير محدد';
        $certificateNumber = $this->booking->certificate_number ?? 'غير محدد';
        $typeOfAction = function_exists('TypeOfAction')
            ? __('actions.' . TypeOfAction($this->booking->type_of_action))
            : 'غير محدد';

        $containers = [];
        foreach ($this->booking->bookingContainers as $container) {
            $containers[] = [
                'number' => $container->container_no ?? $container->container_number ?? '-',
                'size' => $container->container ? ($container->container->full_name ?? $container->container->size ?? $container->container->type) : '-',
                'arrival' => $container->arrival_date ?? '-',
            ];
        }

        $html = view('email.new-booking', [
            'bookingNumber' => $trackId,
            'url' => $url,
            'details' => [
                'رقم الحجز' => $trackId,
                'اسم المصنع' => $factoryName,
                'اسم الموظف المسؤول' => $employeeName,
                'وكيل الشحن' => $shippingAgentTitle,
                'رقم الشهادة' => $certificateNumber,
                'نوع العملية' => $typeOfAction,
                'عدد الحاويات' => $count,
            ],
            'containers' => $containers,
        ])->render();

        $to = $notifiable->email ?? null;
        return [
            'to' => $to,
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
