<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\CompanyServiceImport;
use App\Models\Company;
use App\Models\companyServices;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CompanyServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Company $company)
    {
        $input = [
            'companyServices'   => $company->services,
            'route_create'      => route('companyServices.create', $company),
            'import_route'      => route('companyServices.import', $company),
        ];

        return view('admin.companyServices.index', $input);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Company $company)
    {
        $input = [
            'serviceCategories' => ServiceCategory::pluck('title', 'id'),
            'action'            => route('companyServices.store', $company),
            'method'            => 'POST',
            'company'           => $company,
        ];

        return view('admin.companyServices.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Company $company)
    {
        //$companyServices = companyServices::create($request->validated());
        // dd($request);
        $company->services()->attach($request->service_id, ['cost' => $request->cost]);
        return redirect()->route('companyServices.index', $company)->with(['success' => __('alerts.added_successfully')]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\companyServices  $companyServices
     * @return \Illuminate\Http\Response
     */
    public function show(companyServices $companyServices, Company $company)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\companyServices  $companyServices
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company, companyServices $companyServices)
    {
        $input = [
            'serviceCategories' => ServiceCategory::pluck('title', 'id'),
            'companyServices'   => $companyServices,
            'action'            => route('companyServices.update', $companyServices->id),
            'method'            => 'PUT',
            'company'           => $company,
        ];

        return view('admin.companyServices.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\companyServices  $companyServices
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Company $company, companyServices $companyServices)
    {
        $company->syncWithoutDetach($request);
        // $companyServices->update($request->validated());
        return redirect()->route('companyServices.index', $company)->with(['success' => __('alerts.updated_successfully')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\companyServices  $companyServices
     * @return \Illuminate\Http\Response
     */
    public function destroy(companyServices $companyServices)
    {
        $companyServices->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }

    public function Import(Request $request, Company $company)
    {
        try {
            Excel::import(new CompanyServiceImport($company), $request->file);
            return redirect()->route('companyServices.index', $company)->with('success', __('alerts.added_successfully'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages['row']        = $failure->row(); // row that went wrong
                $messages['attribute']  = $failure->attribute(); // either heading key (if using heading row concern) or column index
                $messages['errors']     = $failure->errors(); // Actual error messages from Laravel validator
                $messages['values']     = $failure->values(); // The values of the row that has failed.
            }
            return redirect()->route('companyServices.index', $company)->with('error', $messages['errors'][0]);
        }
    }
}
