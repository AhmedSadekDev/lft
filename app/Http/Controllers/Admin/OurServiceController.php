<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OurServiceRequest;
use App\Models\OurService;

class OurServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ourServices.index')->only('index');
        $this->middleware('permission:ourServices.create')->only(['create', 'store']);
        $this->middleware('permission:ourServices.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:ourServices.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'services' => OurService::all(),
        ];

        return view('admin.ourServices.index', $input);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'method' => 'POST',
            'action' => route('ourServices.store'),
        ];

        return view('admin.ourServices.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(OurServiceRequest $request)
    {
        if(!is_null(OurService::count()) && OurService::count() > 3){
            return redirect()->route('ourServices.index')->with(['error' => __('alerts.maximum_services')]);
        }

        OurService::create($request->validated());
        return redirect()->route('ourServices.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OurService  $ourService
     * @return \Illuminate\Http\Response
     */
    public function show(OurService $ourService)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\OurService  $ourService
     * @return \Illuminate\Http\Response
     */
    public function edit(OurService $ourService)
    {
        $input = [
            'service' => $ourService,
            'method' => 'PUT',
            'action' => route('ourServices.update', $ourService),
        ];

        return view('admin.ourServices.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\OurService  $ourService
     * @return \Illuminate\Http\Response
     */
    public function update(OurServiceRequest $request, OurService $ourService)
    {
        $ourService->update($request->all());
        return redirect()->route('ourServices.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OurService  $ourService
     * @return \Illuminate\Http\Response
     */
    public function destroy(OurService $ourService)
    {
        $ourService->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
