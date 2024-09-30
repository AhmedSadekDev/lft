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
    public function index()
    {
        $input = [
            'containers' => Container::all(),
        ];

        return view('admin.containers.index', $input);
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
        $bookings = Booking::whereIn('id', explode(',', $request->ids))->get();
        $containerIds = [];
        foreach($bookings as $booking) {
            foreach($booking->bookingContainers as $container) {
                $containerIds[] = $container->id;
            }
        }
        return Excel::download(new BookingContainerDetails($containerIds), 'booking_containers.xlsx');
    }
        
}
