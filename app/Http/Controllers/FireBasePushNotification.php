<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class FireBasePushNotification extends Controller
{

    private $url = 'https://fcm.googleapis.com/v1/projects/amani-32c87/messages:send';
    private $scope = "https://www.googleapis.com/auth/firebase.messaging";
    private $token;

    public function __construct()
    {
        // Provide the path where you stored the json token, in my case, I stored it in database
        $creadentials = new ServiceAccountCredentials($this->scope, storage_path('app/firebase.json'));
        $this->token = $creadentials->fetchAuthToken(HttpHandlerFactory::build());
    }

    public function to($device, $body, $title = "My favorite App", $extraData = [])
    {
        $data = [
            'token' => $device,
            'title' => $title,
            'body' => $body,
            'data' => $extraData
        ];

        return $this->send($data);
    }

    public function send($data)
    {
        $headers = [
            'Authorization: Bearer ' . $this->token['access_token'],
            'Content-Type: application/json'
        ];

        $fields = [
            'message' => [
                'token' => $data['token'],
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body']
                ]
            ]
        ];

        // Add data payload if provided
        if (!empty($data['data'])) {
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
            } else {
                return redirect()->back()->with('error', __('Notification failed: ') . ($response['error']['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Notification failed: ') . $e->getMessage());
        }
    }
}
