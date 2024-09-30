<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PageResource;
use App\Models\StaticPage;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    public function page($key){
        $page = StaticPage::where('key', trim_key($key))->first();
        return $this->returnAllData(new PageResource($page));
    }
}
