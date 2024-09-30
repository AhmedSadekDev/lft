<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:contactUs.index')->only('index');
        $this->middleware('permission:contactUs.create')->only(['create', 'store']);
        $this->middleware('permission:contactUs.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:contactUs.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'contacts' => ContactUs::all(),
        ];

        return view('admin.contactUs.index', $input);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ContactUs  $contactUs
     * @return \Illuminate\Http\Response
     */
    public function destroy(ContactUs $contactU)
    {
        $contactU->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
