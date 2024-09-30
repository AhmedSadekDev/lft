<?php

namespace App\Services;

use App\Models\AppNotification;

class SaveNotification
{

    public static function create($title, $text,$notificationable_id = null, $notificationable_type = null,$type = AppNotification::all)
    {
        
        $data["title"] = $title;
        $data["text"] = $text;
        $data["notificationable_id"] = $notificationable_id;
        $data["notificationable_type"] = $notificationable_type;
        $data["type"] = $type;
        AppNotification::create($data);

        return true;
    }
}