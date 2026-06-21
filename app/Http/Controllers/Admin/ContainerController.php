<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContainerRequest;
use App\Models\Container;
use App\Models\Booking;
use App\Models\BookingContainer;
use Dotenv\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BookingContainerDetails;
use Illuminate\Http\Request;

class ContainerController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:containers.index')->only('index');
        $this->middleware('permission:containers.create')->only(['create', 'store']);
        $this->middleware('permission:containers.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:containers.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Container::query();

        // تطبيق البحث إذا كان موجود
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('type', 'like', '%' . $search . '%')
                    ->orWhere('size', 'like', '%' . $search . '%');
            });
        }

        $containers = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('admin.containers.index', compact('containers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input =[
            'method' => 'POST',
            'action' => route('containers.store'),
        ];

        return view('admin.containers.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ContainerRequest $request)
    {
        Container::create($request->validated());
        return redirect()->route('containers.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Container $container)
    {
        $input = [
            'method'    => 'PUT',
            'action'    => route('containers.update', $container->id),
            'container' => $container,
        ];

        return view('admin.containers.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Container $container)
    {
        $validated = $request->validate([
            'type'          => 'required|max:100',
            'size'          => 'required|max:100',
        ]);

        $container->update($validated);
        return redirect()->route('containers.index')->with('success', __('alerts.updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Container $container)
    {
        $container->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }

    public function export(Request $request)
    {
        $bookings = Booking::query()
            ->with(['company', 'factory', 'invoice']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $bookings->where(function($query) use ($search) {
                $query->where('booking_number', 'like', '%' . $search . '%')
                    ->orWhere('employee_name', 'like', '%' . $search . '%')
                    ->orWhereHas('bookingContainers', function($container) use($search){
                        $container->where('container_no', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('factory', function($factory) use($search){
                        $factory->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('invoice', function($invoice) use($search){
                        $invoice->where('invoice_number', 'like', '%' . $search . '%');
                    });
            });
        }

        // Date range filter
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $bookings->filterDateRange(request('date_from'), request('date_to'));
        }

        // Company filter
        if ($request->filled("company")) {
            $bookings->filterCompany(request('company'));
        }

        // Tax status filter
        if ($request->filled("tax_status")) {
            $bookings->filterTaxStatus(request('tax_status'));
        }

        // Invoice status filter
        if ($request->filled("invoice_status")) {
            if ($request->invoice_status == '1') {
                $bookings->whereHas('invoice');
            } else {
                $bookings->whereDoesntHave('invoice');
            }
        }

        // If ids are provided, filter by them (for backward compatibility)
        if ($request->filled('ids')) {
            $bookings->whereIn('id', explode(',', $request->ids));
        }

        $bookings = $bookings->get();
        
        $containerIds = [];
        foreach($bookings as $booking) {
            foreach($booking->bookingContainers as $container) {
                $containerIds[] = $container->id;
            }
        }
        
        return Excel::download(new BookingContainerDetails($containerIds), 'booking_containers.xlsx');
    }

}
