<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Http\Requests\Admin\ServiceRequest;
use App\Imports\ServiceImport;
use App\Models\Company;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:services.index')->only('index');
        $this->middleware('permission:services.create')->only(['create', 'store']);
        $this->middleware('permission:services.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:services.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $input = [
            'services'          => Service::all(),
            'route_create'      => route('services.create'),
            'import_route'      => route('services.import'),
        ];

        return view('admin.services.index', $input);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'companies'         => Company::pluck('name', 'id'),
            'method'            => 'POST',
            'serviceCategories' => ServiceCategory::all()->pluck('title', 'id'),
            'action'            => route('services.store'),
        ];


        return view('admin.services.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ServiceRequest $request)
    {
        Service::create($request->validated());
        return redirect()->route('services.index')->with(['success' => __('alerts.added_successfully')]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function show(Service $service)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function edit(Service $service)
    {
        $input = [
            'service'           => $service,
            'companies'         => Company::pluck('name', 'id'),
            'serviceCategories' => ServiceCategory::all()->pluck('title', 'id'),
            'method'            => 'PUT',
            'action'            => route('services.update', $service),
        ];

        return view('admin.services.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function update(ServiceRequest $request, Service $service)
    {
        $service->update($request->all());
        return redirect()->route('services.index')->with(['success' => __('alerts.updated_successfully')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }

    public function Import(Request $request)
    {
        try {
            Excel::import(new ServiceImport(), $request->file);
            return redirect()->route('services.index')->with('success', __('alerts.added_successfully'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages['row']        = $failure->row(); // row that went wrong
                $messages['attribute']  = $failure->attribute(); // either heading key (if using heading row concern) or column index
                $messages['errors']     = $failure->errors(); // Actual error messages from Laravel validator
                $messages['values']     = $failure->values(); // The values of the row that has failed.
            }
            return redirect()->route('services.index')->with('error', $messages['errors'][0]);
        }
    }

    public function getServices(ServiceCategory $serviceCategories, Company $company){
        return $serviceCategories->services?->where('company_id', $company->id)->pluck('name', 'id');
    }

}
