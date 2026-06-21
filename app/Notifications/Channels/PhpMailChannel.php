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

        // Extract email and name from "Name <email@domain>" format
        $fromEmail = $this->extractEmailAddress($from) ?: 'booking@leaderfortrans.com';
        $fromName = $this->extractNameFromAddress($from) ?: 'Leader for Trans';

        try {
            // Headers للبريد
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . $fromName . " <" . $fromEmail . ">\r\n";
            $headers .= "Reply-To: " . $replyTo . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $headers .= "X-Priority: 1 (Highest)\r\n";
            $headers .= "Importance: High\r\n";

            // إضافة headers إضافية إذا كانت موجودة
            if (!empty($extraHeaders) && is_array($extraHeaders)) {
                foreach ($extraHeaders as $key => $value) {
                    $headers .= $key . ": " . $value . "\r\n";
                }
            }

            Log::info('PhpMailChannel: Attempting to send email using PHP mail()', [
                'to' => $to,
                'subject' => $subject,
                'from' => $fromEmail,
                'from_name' => $fromName,
                'notification' => get_class($notification),
                'method' => 'PHP mail()',
            ]);

            // إرسال البريد باستخدام PHP mail()
            $result = mail($to, $subject, $html, $headers);

            if ($result) {
                Log::info('PhpMailChannel: Email sent successfully using PHP mail()', [
                    'to' => $to,
                    'subject' => $subject,
                    'notification' => get_class($notification),
                    'time' => now()->toDateTimeString(),
                    'method' => 'PHP mail()',
                ]);
            } else {
                throw new \Exception('PHP mail() returned FALSE');
            }
        } catch (\Exception $e) {
            Log::error('PhpMailChannel: Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'method' => 'PHP mail()',
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
