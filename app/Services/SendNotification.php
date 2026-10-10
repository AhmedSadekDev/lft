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

        $job = new SendPushNotificationJob((string) $token, (string) $title, (string) $text, (array) $data, $eventId);
        return $job->handle();
    }
}
