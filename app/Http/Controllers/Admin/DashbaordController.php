<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashbaordController extends Controller
{
    public function __invoke()
    {
        return view('admin.index');
    }
}
