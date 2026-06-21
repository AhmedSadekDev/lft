<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use App\Http\Traits\ImagesTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Exports\CompaniesListExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AssignPasswordNotification;
use App\Notifications\WelcomeCompany;
use Maatwebsite\Excel\Facades\Excel;

class CompanyController extends Controller
{
    use ImagesTrait;

    public function __construct()
    {
        $this->middleware('permission:companies.index')->only('index');
        $this->middleware('permission:companies.create')->only(['create', 'store']);
        $this->middleware('permission:companies.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:companies.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Company::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('tax_no', 'like', "%{$search}%");
            });
        }

        // Sort by
        $sortBy = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $companies = $query->with(['bookings.invoice.invoicePayments'])->paginate(20);

        $input = [
            'companies' => $companies,
        ];
        return view('admin.companies.index', $input);
    }

    /**
     * Export companies list to Excel (server-side).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $search = $request->get('search');

        return Excel::download(new CompaniesListExport($search), 'companies.xlsx');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $privateCompanies = \App\Models\PrivateCompany::orderBy('name')->pluck('name', 'id');

        $input = [
            'method'    => 'POST',
            'action'    => route('companies.store'),
            'privateCompanies' => $privateCompanies,
        ];

        return view('admin.companies.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CompanyRequest $request)
    {
        $data = $request->all();

        $data = array_filter($data, function ($value) {
            return $value !== null;
        });


        $company = Company::create($data);
        $token = JWTAuth::fromUser($company);
        $company->update(array_merge(['session_id' => $token]));
        Notification::send($company, new AssignPasswordNotification($company));
        Notification::send($company, new WelcomeCompany($company));


        return to_route('companies.index')->with('success', __('alerts.added_successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        $input = [
            'company'           => $company,
            'transportations'   => $company->transportations,
        ];
        return view('admin.companies.show', $input);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        $privateCompanies = \App\Models\PrivateCompany::orderBy('name')->pluck('name', 'id');

        $input = [
            'method'    => 'PUT',
            'action'    => route('companies.update', $company->id),
            'company'   => $company,
            'privateCompanies' => $privateCompanies,
        ];

        return view('admin.companies.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(CompanyRequest $request, Company $company)
    {
        $company->update($request->all());
        return redirect()->route('companies.index')
            ->with('success', __('alerts.updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }


    public function getEmployees(Company $company)
    {
        return !is_null($company->employees) ? $company->employees->pluck('name', 'id') : null;
    }
}
