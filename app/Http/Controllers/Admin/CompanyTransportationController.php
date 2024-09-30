<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyTransportationRequest;
use App\Models\CitiesAndRegions;
use App\Models\Company;
use App\Models\CompanyTransportation;
use App\Models\Container;
use App\Models\Transportation;
use Illuminate\Http\Request;

use App\Imports\AdminsImport;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TransportationsImport;

class CompanyTransportationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        if(isset($request->company_id) && !is_null($request->company_id)){
            $input = [
                'transportations'   => CompanyTransportation::where('company_id', $request->company_id)->get(),
                'route_create'      => route('companyTransportations.create', ['company_id' => $request->company_id ]),
                'import_route'      => route('companyTransportations.import', ['company_id' => $request->company_id ]),
            ];
        }else{
            $input = [
                'transportations'   => CompanyTransportation::all(),
                'route_create'      => route('companyTransportations.create'),
                'import_route'      => route('companyTransportations.import'),
            ];
        }

        return view('admin.transportations.index', $input);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'citiesAndRegions'  => CitiesAndRegions::pluck('title', 'id'),
            'companies'         => Company::pluck('name', 'id'),
            'containers'        => Container::get()->pluck('full_name', 'id'),
            'method'            => 'POST',
        ];

        if(isset(request()->company_id) && !is_null(request()->company_id)){
            $input['action']    = route('companyTransportations.store', ['company' => request()->company_id ]);
            $input['company']   =  Company::find(request()->company_id);
        }else{
            $input['action']        = route('companyTransportations.store');
        }

        return view('admin.transportations.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CompanyTransportationRequest $request)
    {
        CompanyTransportation::create($request->validated());

        if(isset($request->company) && !is_null($request->company)){
            return redirect()->route('companyTransportations.index', ['company_id' => $request->company])->with(['success' => __('alerts.added_successfully')]);
        }else{
            return redirect()->route('companyTransportations.index')->with(['success' => __('alerts.added_successfully')]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Transportation  $transportation
     * @return \Illuminate\Http\Response
     */
    public function show(CompanyTransportation $companyTransportation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CompanyTransportation  $companyTransportation
     * @return \Illuminate\Http\Response
     */
    public function edit(companyTransportation $companyTransportation)
    {

        $input = [
            'companyTransportation' => $companyTransportation,
            'citiesAndRegions'      => CitiesAndRegions::pluck('title', 'id'),
            'companies'             => Company::pluck('name', 'id'),
            'containers'            => Container::pluck('type', 'id'),
            'method'                => 'PUT',
        ];

        if(isset(request()->company_id) && !is_null(request()->company_id)){
            $input['action']        = route('companyTransportations.update', [ 'companyTransportation' => $companyTransportation->id, 'company' => request()->company_id]);
            $input['company']       =  Company::find(request()->company_id);
        }else{
            $input['action']        = route('companyTransportations.update', $companyTransportation->id);
        }

        return view('admin.transportations.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CompanyTransportation  $companyTransportation
     * @return \Illuminate\Http\Response
     */
    public function update(CompanyTransportationRequest $request, CompanyTransportation $companyTransportation)
    {
        $companyTransportation->update($request->validated());

        if(isset($request->company) && !is_null($request->company)){
            return redirect()->route('companyTransportations.index', ['company_id' => $request->company])->with(['success' => __('alerts.updated_successfully')]);
        }else{
            return redirect()->route('companyTransportations.index')->with(['success' => __('alerts.updated_successfully')]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyTransportation  $companyTransportation
     * @return \Illuminate\Http\Response
     */
    public function destroy(CompanyTransportation $companyTransportation)
    {
        $companyTransportation->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(Request $request)
    {
        Excel::import(new TransportationsImport(), $request->file);
        return redirect()->back()->with(['success' => __('alerts.added_successfully')]);
    }
}
