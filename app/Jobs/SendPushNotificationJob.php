<?php

namespace App\Jobs;

use App\Http\Controllers\FireBasePushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $token;
    public string $title;
    public string $text;
    public array $data;
    public ?string $eventId;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [5, 30, 120];

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     *
     * @param string $token Device token
     * @param string $title Notification title
     * @param string $text Notification body text
     * @param array $data Extra custom data payload
     * @param string|null $eventId Optional idempotency business key to prevent duplicate dispatch
     */
    public function __construct(string $token, string $title, string $text, array $data = [], ?string $eventId = null)
    {
        $this->token = trim($token);
        $this->title = $title;
        $this->text = $text;
        $this->data = $data;
        $this->eventId = $eventId;
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        // 1. Guard against empty token — permanent failure, do not retry
        if (empty($this->token)) {
            Log::warning('SendPushNotificationJob: Skipped due to empty device token', [
                'title' => $this->title,
                'eventId' => $this->eventId,
            ]);
            return false;
        }

        // 2. Idempotency guard — prevent duplicate dispatch for identical business event
        if ($this->eventId) {
            $lockKey = 'push_event_processed_' . md5($this->eventId . '_' . $this->token);
            if (Cache::has($lockKey)) {
                Log::info('SendPushNotificationJob: Duplicate dispatch suppressed by idempotency key', [
                    'eventId' => $this->eventId,
                ]);
                return true;
            }
        }

        try {
            $firebase = new FireBasePushNotification();
            $result = $firebase->to($this->token, $this->text, $this->title, $this->data);
            $decoded = json_decode((string) $result, true);

            if (is_array($decoded) && isset($decoded['error'])) {
                $errorCode = $decoded['error']['status'] ?? $decoded['error']['code'] ?? 'UNKNOWN_ERROR';
                $errorMessage = $decoded['error']['message'] ?? 'FCM request rejected';

                // Check for permanent client errors where retrying is futile
                $permanentErrors = ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND', 'PERMISSION_DENIED'];
                if (in_array($errorCode, $permanentErrors, true)) {
                    Log::warning('SendPushNotificationJob: Permanent FCM rejection, will not retry', [
                        'code' => $errorCode,
                        'message' => $errorMessage,
                        'eventId' => $this->eventId,
                    ]);
                    return false;
                }

                // Transient error: throw exception to trigger queue worker retry with backoff
                Log::error('SendPushNotificationJob: Transient FCM error encountered, queuing retry', [
                    'code' => $errorCode,
                    'message' => $errorMessage,
                    'attempt' => $this->attempts(),
                    'eventId' => $this->eventId,
                ]);

                throw new RuntimeException("FCM transient error [{$errorCode}]: {$errorMessage}");
            }

            // Mark idempotency key if successful (5 minutes TTL)
            if ($this->eventId) {
                Cache::put('push_event_processed_' . md5($this->eventId . '_' . $this->token), true, now()->addMinutes(5));
            }

            Log::info('SendPushNotificationJob: Notification sent successfully', [
                'title' => $this->title,
                'attempt' => $this->attempts(),
                'eventId' => $this->eventId,
            ]);

            return true;
        } catch (\Throwable $e) {
            if ($e instanceof RuntimeException && str_starts_with($e->getMessage(), 'FCM transient error')) {
                throw $e;
            }

            // Connection or cURL error — retry if within attempts limit
            if ($this->attempts() < $this->tries) {
                Log::error('SendPushNotificationJob: Network or unexpected exception, retrying', [
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                ]);
                throw $e;
            }

            Log::critical('SendPushNotificationJob: Exhausted all retries for notification', [
                'error' => $e->getMessage(),
                'eventId' => $this->eventId,
            ]);

            return false;
        }
    }
}
