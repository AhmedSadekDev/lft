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
        $to = $this->company->email ?? $notifiable->email ?? null;
        $html = view('email.welcome', ['name' => $name])->render();
        return [
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
        ];
    }
}
