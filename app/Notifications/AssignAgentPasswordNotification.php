<?php

namespace App\Notifications;

use App\Notifications\Channels\PhpMailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignAgentPasswordNotification extends Notification
{
    use Queueable;

    protected $agent;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($agent)
    {
        $this->agent = $agent;
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

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->action('Assing Password', env('APP_URL') .  '/set_password/agents/?token='. $this->agent->session_id);
    }

    public function toPhpMail($notifiable)
    {
        $url = rtrim(env('APP_URL'), '/') .  '/set_password/agents/?token='. $this->agent->session_id;
        $name = method_exists($notifiable, 'getAttribute') ? ($notifiable->getAttribute('name') ?? '') : '';
        $to = $this->agent->email ?? $notifiable->email ?? null;

        $html = view('email.set-password', [
            'name' => $name,
            'url' => $url,
            'accountLabel' => 'حساب المندوب',
            'intro' => 'تم إنشاء حساب لك كمندوب على منظومة Leader for Trans. اضغط على الزر أدناه لتعيين كلمة المرور وإكمال تفعيل الحساب.',
        ])->render();

        return [
            'to' => $to,
            'subject' => 'تعيين كلمة المرور لحساب المندوب - Leader for Trans',
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
            'agent_id' => $this->agent->id,

        ];
    }
}
