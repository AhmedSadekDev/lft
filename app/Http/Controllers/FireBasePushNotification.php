<?php

namespace App\Http\Controllers;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FireBasePushNotification extends Controller
{
    private string $url;
    private string $scope = 'https://www.googleapis.com/auth/firebase.messaging';
    private ?array $token = null;

    public function __construct()
    {
        $projectId = config('services.firebase.project_id', 'amani-32c87');
        $this->url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    private function getAccessToken(): string
    {
        if ($this->token !== null) {
            return $this->token['access_token'];
        }

        $credentials = $this->loadServiceAccountCredentials();
        $auth = new ServiceAccountCredentials($this->scope, $credentials);

        try {
            $this->token = $auth->fetchAuthToken(HttpHandlerFactory::build());
        } catch (\Throwable $e) {
            Log::error('Firebase auth failed', [
                'message' => $e->getMessage(),
                'credentials_path' => config('services.firebase.credentials'),
                'client_email' => $credentials['client_email'] ?? null,
            ]);

            throw new RuntimeException(
                'Firebase authentication failed. Verify storage/app/firebase.json on the server (valid service account key, unmodified private_key).',
                0,
                $e
            );
        }

        if (empty($this->token['access_token'])) {
            throw new RuntimeException('Firebase authentication returned no access token.');
        }

        return $this->token['access_token'];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadServiceAccountCredentials(): array
    {
        $path = config('services.firebase.credentials', storage_path('app/firebase.json'));

        if (! is_readable($path)) {
            throw new RuntimeException("Firebase credentials file not found or not readable: {$path}");
        }

        $json = json_decode((string) file_get_contents($path), true);

        if (! is_array($json)) {
            throw new RuntimeException("Firebase credentials file is not valid JSON: {$path}");
        }

        if (! empty($json['private_key'])) {
            // cPanel/FTP uploads often store literal "\n" instead of real newlines.
            $json['private_key'] = str_replace('\\n', "\n", $json['private_key']);
        }

        return $json;
    }

    public function to($device, $body, $title = 'My favorite App', $extraData = [])
    {
        $data = [
            'token' => $device,
            'title' => $title,
            'body' => $body,
            'data' => $extraData,
        ];

        return $this->send($data);
    }

    public function send($data)
    {
        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
            'Content-Type: application/json',
        ];

        $fields = [
            'message' => [
                'token' => $data['token'],
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                ],
            ],
        ];

        if (! empty($data['data'])) {
            $fields['message']['data'] = array_map('strval', $data['data']);
        }

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function index()
    {
        $input = [
            'method' => 'POST',
            'action' => route('fcm.send'),
        ];

        return view('admin.fcm.index', $input);
    }

    public function sendNotification(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        try {
            $result = $this->to($request->token, $request->body, $request->title);
            $response = json_decode($result, true);

            if (isset($response['name'])) {
                return redirect()->back()->with('success', __('alerts.success') . ' - ' . __('Notification sent successfully'));
            }

            return redirect()->back()->with('error', __('Notification failed: ') . ($response['error']['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Notification failed: ') . $e->getMessage());
        }
    }
}
