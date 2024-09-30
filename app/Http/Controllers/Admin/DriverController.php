<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Driver\StoreRequest;
use App\Http\Requests\Admin\Driver\UpdateRequest;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class DriverController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:drivers.index')->only('index');
        $this->middleware('permission:drivers.create')->only(['create', 'store']);
        $this->middleware('permission:drivers.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:drivers.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'drivers' => Driver::all(),
        ];
        return view('admin.drivers.index', $input);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'method'    => 'POST',
            'action'    => route('drivers.store') 
        ];

        return view('admin.drivers.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $driver = Driver::create($request->validated());

        return redirect()->route('drivers.index')
            ->with('success', __('alerts.added_successfully'));
    }


    public function edit(Driver $driver)
    {
        $input = [
            'method'    => 'PUT',
            'action'    => route('drivers.update', $driver->id),
            'driver'   => $driver,
        ];

        return view('admin.drivers.edit', $input);
    }


    public function update(UpdateRequest $request, Driver $driver)
    {
        $driver->update($request->validated());
        return redirect()->route('drivers.index')
            ->with('success', __('alerts.updated_successfully'));
    }


    public function destroy(Driver $driver)
    {
        $driver->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }

}
