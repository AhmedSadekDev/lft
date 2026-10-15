<?php

namespace App\Notifications\Api\Auth;

use App\Notifications\Channels\PhpMailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPassword extends Notification
{
    public $token;

    public static $createUrlCallback;

    public static $toMailCallback;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return [PhpMailChannel::class];
    }

    public function toMail($notifiable)
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        return $this->buildMailMessage($this->resetUrl($notifiable));
    }

    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject(Lang::get('Reset Password Notification'))
            ->view('email.reset-password', ['token' => $url]);
    }

    /**
     * Build payload for PhpMailChannel.
     */
    public function toPhpMail($notifiable)
    {
        $html = view('email.otp', ['otp' => (string) $this->token])->render();

        $to = $notifiable->email ?? null;
        return [
            'to' => $to,
            'subject' => 'كود إعادة تعيين كلمة المرور - Leader for Trans',
            'html' => $html,
            'from' => 'Leader for Trans <booking@leaderfortrans.com>',
            'reply_to' => 'booking@leaderfortrans.com',
        ];
    }

    protected function resetUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        return $this->token;
    }

    public static function createUrlUsing($callback)
    {
        static::$createUrlCallback = $callback;
    }

    public static function toMailUsing($callback)
    {
        static::$toMailCallback = $callback;
    }
}
