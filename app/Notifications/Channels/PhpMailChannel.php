<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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

        if (is_array($to)) {
            $to = implode(', ', array_filter($to));
        }

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $from;
        $headers[] = 'Reply-To: ' . $replyTo;
        $headers[] = 'Return-Path: booking@leaderfortrans.com';
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headers[] = 'X-Priority: 1 (Highest)';
        $headers[] = 'Importance: High';

        foreach ($extraHeaders as $headerLine) {
            if (is_string($headerLine) && trim($headerLine) !== '') {
                $headers[] = $headerLine;
            }
        }

        $headersString = implode("\r\n", $headers);

        $envelopeSender = $this->extractEmailAddress($from) ?: 'booking@leaderfortrans.com';
        $additionalParams = $envelopeSender ? '-f ' . $envelopeSender : '';

        // Use envelope sender to avoid showing default server sender
        $result = @mail($to, $subject, $html, $headersString, $additionalParams);

        if ($result) {
            Log::info('PhpMailChannel: Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
                'notification' => get_class($notification),
            ]);
        } else {
            Log::error('PhpMailChannel: Failed to send email (mail() returned FALSE)', [
                'to' => $to,
                'subject' => $subject,
                'notification' => get_class($notification),
            ]);
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
}
