<?php
namespace App\Http\Controllers;


use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class FireBasePushNotification
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
    
    public function to($device, $body, $title = "My favorite App")
    {
        $data = [
            'token' => $device,
            'title' => $title,
            'body' => $body
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
}