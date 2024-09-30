<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function getReviews(){
        $data = ReviewResource::collection(Review::all());
        return $this->returnAllData($data);
    }
}
