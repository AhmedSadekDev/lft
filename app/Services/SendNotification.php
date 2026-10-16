<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use Illuminate\Support\Facades\Log;

class SendNotification
{
    /**
     * Dispatch notification asynchronously via background queue.
     */
    public static function sendAsync($token, $title, $text, $data = [], ?string $eventId = null): void
    {
        SendPushNotificationJob::dispatch((string) $token, (string) $title, (string) $text, (array) $data, $eventId);
    }

    /**
     * Send push notification.
     * If $async is true, dispatches to queue; otherwise executes synchronously.
     */
    public static function send($token, $title, $text, $data = [], ?string $eventId = null, bool $async = false): bool
    {
        if ($async) {
            static::sendAsync($token, $title, $text, $data, $eventId);
            return true;
        }

        try {
            $job = new SendPushNotificationJob((string) $token, (string) $title, (string) $text, (array) $data, $eventId);

            return $job->handle();
        } catch (\Throwable $e) {
            // Never let push failures break business APIs (approve/assign/etc.).
            Log::critical('SendNotification: push failed and was swallowed', [
                'error' => $e->getMessage(),
                'title' => $title,
                'eventId' => $eventId,
            ]);

            return false;
        }
    }
}
