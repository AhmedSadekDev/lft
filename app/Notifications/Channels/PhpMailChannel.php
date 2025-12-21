<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PhpMailChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toPhpMail')) {
            return;
        }

        $payload = call_user_func([$notification, 'toPhpMail'], $notifiable);

        $to = $payload['to'] ?? $this->resolveEmailAddress($notifiable);
        $subject = $payload['subject'] ?? '';
        $html = $payload['html'] ?? '';
        $from = $payload['from'] ?? 'Leader for Trans <booking@leaderfortrans.com>';
        $replyTo = $payload['reply_to'] ?? 'booking@leaderfortrans.com';
        $extraHeaders = $payload['headers'] ?? [];

        if (empty($to) || empty($subject) || empty($html)) {
            Log::warning('PhpMailChannel: missing required fields', [
                'to' => $to,
                'has_subject' => !empty($subject),
                'has_html' => !empty($html),
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Extract email and name from "Name <email@domain>" format
        $fromEmail = $this->extractEmailAddress($from) ?: 'booking@leaderfortrans.com';
        $fromName = $this->extractNameFromAddress($from) ?: 'Leader for Trans';

        try {
            // Log email configuration for debugging
            Log::info('PhpMailChannel: Attempting to send email', [
                'to' => $to,
                'subject' => $subject,
                'from' => $fromEmail,
                'notification' => get_class($notification),
                'mailer_config' => [
                    'driver' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'username' => config('mail.mailers.smtp.username') ? '***' : 'not set',
                ]
            ]);

            // Enable SMTP debug logging
            $originalDebug = config('mail.mailers.smtp.stream.verify_peer', true);

            // Use Laravel Mail with SMTP instead of mail() function
            $sent = Mail::html($html, function ($message) use ($to, $subject, $fromEmail, $fromName, $replyTo) {
                $message->to($to)
                    ->subject($subject)
                    ->from($fromEmail, $fromName)
                    ->replyTo($replyTo);
            });

            Log::info('PhpMailChannel: Email command sent to SMTP server', [
                'to' => $to,
                'subject' => $subject,
                'notification' => get_class($notification),
                'sent_response' => $sent,
                'warning' => 'Email was sent to SMTP server but actual delivery depends on the mail server configuration',
            ]);
        } catch (\Swift_TransportException $e) {
            Log::error('PhpMailChannel: SMTP Transport error', [
                'to' => $to,
                'subject' => $subject,
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port'),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('PhpMailChannel: Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception so the notification system knows it failed
            throw $e;
        }
    }

    /**
     * Try to resolve an email address from the notifiable.
     *
     * @param  mixed  $notifiable
     * @return string|null
     */
    protected function resolveEmailAddress($notifiable)
    {
        if (method_exists($notifiable, 'routeNotificationForMail')) {
            $address = $notifiable->routeNotificationForMail(null);
            if (is_array($address)) {
                $address = reset($address) ?: null;
            }
            if (is_string($address) && $address !== '') {
                return $address;
            }
        }

        if (isset($notifiable->email) && is_string($notifiable->email)) {
            return $notifiable->email;
        }

        return null;
    }

    /**
     * Extract email address from a "Name <email@domain>" or plain email string.
     */
    protected function extractEmailAddress(?string $from): ?string
    {
        if (!$from) {
            return null;
        }
        if (preg_match('/<([^>]+)>/', $from, $m)) {
            return trim($m[1]);
        }
        if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return $from;
        }
        // Try to split by space and take last token if it looks like an email
        $parts = preg_split('/\s+/', $from);
        $candidate = $parts ? end($parts) : null;
        return filter_var($candidate, FILTER_VALIDATE_EMAIL) ? $candidate : null;
    }

    /**
     * Extract name from "Name <email@domain>" format.
     */
    protected function extractNameFromAddress(?string $from): ?string
    {
        if (!$from) {
            return null;
        }
        if (preg_match('/^(.+?)\s*<[^>]+>$/', $from, $m)) {
            return trim($m[1], '"\'');
        }
        return null;
    }
}
