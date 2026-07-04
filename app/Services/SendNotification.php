<?php

namespace App\Services;

use App\Http\Controllers\FireBasePushNotification;
use Illuminate\Support\Facades\Log;

class SendNotification
{
    public static function send($token, $title, $text, $data = []): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            $firebase = new FireBasePushNotification();
            $firebase->to($token, $text, $title, $data);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Push notification skipped', [
                'title' => $title,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
