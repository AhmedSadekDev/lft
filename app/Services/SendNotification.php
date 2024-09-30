<?php

namespace App\Services;


class SendNotification
{

    private static $URL = "https://fcm.googleapis.com/fcm/send";


    const NOTIFICATION_KEY = 'AAAA2eRJ5io:APA91bHhoNztjlvPFUhvtSfzZjUzyFIOEo_rFbtHJX62AgfLQ0rLYSYr34IxH5lCy0UoZQIvmycd61KcXUejmnZXU_tlXCAxaBeAQsfEY1R_5IwoNLnZFEfenBef2vF1Hm5t-S3Desn9';

    public static function send($token, $title,$text)
    {
        
        $firebase = new \App\Http\Controllers\FireBasePushNotification();
        $firebase->to($token, $text, $title);
    }
}
