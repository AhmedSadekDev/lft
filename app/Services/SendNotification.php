<?php

namespace App\Services;

use App\Http\Controllers\FireBasePushNotification;
use Illuminate\Support\Facades\Log;

class SendNotification
{
    public static function send($token, $title, $text, $data = []): bool
    {
        Log::info('Attempting to send push notification', [
            'token' => $token,
            'title' => $title,
            'text' => $text,
            'data' => $data,
        ]);

        if (empty($token)) {
            Log::warning('Push notification skipped: Empty device token', [
                'title' => $title,
                'text' => $text,
                'data' => $data,
            ]);
            return false;
        }

        try {
            $firebase = new FireBasePushNotification();
            $result = $firebase->to($token, $text, $title, $data);

            Log::info('Push notification sent successfully', [
                'token' => $token,
                'title' => $title,
                'text' => $text,
                'result' => $result,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Push notification failed to send', [
                'token' => $token,
                'title' => $title,
                'text' => $text,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
