<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\BookingPaper;
use App\Models\Branch;
use App\Models\CitiesAndRegions;
use App\Models\Company;
use App\Models\Container;
use App\Models\DeliveryPolicy;
use App\Models\Employee;
use App\Models\Factory;
use App\Models\ServiceCategory;
use App\Models\shippingAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\NewBooking;
use Illuminate\Support\Facades\Notification;

class BookingController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:bookings.index')->only('index');
        $this->middleware('permission:bookings.create')->only(['create', 'store']);
        $this->middleware('permission:bookings.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:bookings.delete')->only('destroy');
    }
        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $bookings  = Booking::query();


        if ($request->filled('search')) {
            $bookings->whereHas('bookingContainers', function($container) use($request){
                $container->where('container_no', 'like',  '%' . $request->search . '%');
            })
                ->orWhere('booking_number', 'like', '%' . $request->search . '%')
                ->orWhere('employee_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('arrival_date')) {
            $bookings->filterDate(request('arrival_date'));

        }

        if ($request->filled("company")) {
            $bookings->filterCompany(request('company'));

        }

        $bookings = $bookings->get();

        $companies = Company::query()->get();

        return view('admin.bookings.index', compact('bookings', 'companies'));
    }

    private function getCreateFormInputs()
    {
        $companies = Company::whereHas('employees')
            ->with('employees')
            ->get();

        $company_employees = [];
        foreach ($companies as $company)
            foreach ($company->employees as $employee)
                $company_employees[$company->id][$employee->id] = $employee->name;
        return [
            'companies'         => $companies,
            'company_employees' => $company_employees,
            'shipping_agents'   => shippingAgent::pluck('title', 'id'),
            'type_of_actions'   => bookingActions(),
            'containers_type'   => Container::all()->pluck('full_name', 'id'),
            'factories'         => Factory::pluck('name', 'id'),
            'branches'          => Branch::pluck('name', 'id'),
            'employees'        => Employee::pluck('name', 'id'),

        ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $groupedContainers = collect();
        $input = array_merge(
            $this->getCreateFormInputs(),
            [
                'method'            => 'POST',
                'groupedContainers' => $groupedContainers,
                'action'            => route('bookings.store'),
                'companies' => Company::all()
            ]
        );

        return view('admin.bookings.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BookingRequest $request)
    {
        DB::beginTransaction();
        try {
            $booking = Booking::create($request->only(
                'company_id',
                'employee_id',
                'shipping_agent_id',
                'booking_number',
                'certificate_number',
                'type_of_action',
                'discharge_date',
                'permit_end_date',
                'employee_name',
                'factory_id'
            ));

            $dataBookingContainers = [];

            foreach ($request->get('containers') as $container) {
                for ($i = 0; $i < $container['containers_count']; $i++) {
                    $dataBookingContainers[] = [
                        'booking_id'        => $booking->id,
                        'container_id'      => $container['container_id'],
                        'arrival_date'      => $container['arrival_date'],
                        'branch_id' => $container['branch_id']
                    ];
                }
            }
            BookingContainer::insert($dataBookingContainers);
            
            $company = Company::find($request->company_id);
            Notification::send($company, new NewBooking($booking));

            DB::commit();
            if ($booking) {
                return redirect()->route('bookings.index')->with(__('alerts.added_successfully'));
            }
            else {
                redirect()->back()->with('error', 'something went wrong');
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            // throw $th;
            if (!$th->getMessage()) {
                redirect()->route('bookings.index')->with('error', $th->getResponse()?->getData());
            } elseif ($th->getMessage()) {
                redirect()->route('bookings.index')->with('error', $th->getMessage());
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function show(Booking $booking)
    {
        $input = [
            'booking'      => $booking,
            'containers'   => $booking->bookingContainers->mapWithKeys(function ($container) {
                return [
                    $container->container?->id => $container->container?->type,
                ];
            }),
            'classifications'   => ServiceCategory::pluck('title', 'id'),
            'citiesAndRegions'  => CitiesAndRegions::pluck('title', 'id'),
            'deliveryPolices' => DeliveryPolicy::all()
        ];

        return view('admin.bookings.show', $input);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function edit(Booking $booking)
    {
        $groupedContainers = $booking->bookingContainers->groupBy(function ($container) use ($booking) {
            return $container->branch_id . '-' . $booking->bookingContainers->count();
        });

        $input = array_merge(
            $this->getCreateFormInputs(),
            [
                'employees' => $booking
                    ->company
                    ->employees
                    ->pluck('name', 'id'),
                'booking'   => $booking,
                'groupedContainers' => $groupedContainers,
                'method'    => 'PUT',
                'action'    => route('bookings.update', $booking),
            ]
        );

        return view('admin.bookings.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        DB::beginTransaction();

        try {
            // Update the booking details
            $booking->update($request->only(
                'company_id',
                'employee_id',
                'shipping_agent_id',
                'booking_number',
                'certificate_number',
                'type_of_action',
                'discharge_date',
                'permit_end_date',
                'factory_id',
                'employee_name'
            ));

            // Get the existing container IDs
            $bookingContainersID = $booking->bookingContainers->pluck('id')->toArray();

            // Delete existing containers
            BookingContainer::destroy($bookingContainersID);

            // Insert new containers
            foreach ($request->get('containers') as $container) {
                for ($i = 0; $i < $container['containers_count']; $i++) {
                    $dataBookingContainers = [
                        'booking_id'    => $booking->id,
                        'container_id'  => $container['container_id'],
                        'arrival_date'  => $container['arrival_date'],
                        'branch_id'     => $container['branch_id'],
                        'container_no'  => $container['container_no'] ?? null,
                        'sail_of_number'=> $container['sail_of_number'] ?? null,
                    ];
                    BookingContainer::create($dataBookingContainers);
                }
            }

            // Commit the transaction
            DB::commit();

            return redirect()->route('bookings.index')->with('success', __('alerts.updated_successfully'));
        } catch (\Throwable $th) {
            // Rollback the transaction on error
            DB::rollBack();

            // Handle the exception and redirect back with error message
            $errorMessage = $th->getMessage() ?: ($th->getResponse()?->getData() ?: 'An error occurred while updating the booking.');
            return redirect()->route('bookings.index')->with('error', $errorMessage);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->json(['status' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
    public function booking_papers(Booking $booking)
    {
        $booking_papers = BookingPaper::where('booking_id', $booking->id)->get();
        $input = [
            'booking'      => $booking,
            'booking_papers'   => $booking_papers,
        ];

        return view('admin.bookings.papers', $input);
    }

    public function booking_container_papers(BookingContainer $booking)
    {
        $booking_papers = BookingPaper::where('booking_container_id', $booking->id)->get();
        $input = [
            'booking'      => $booking,
            'booking_papers'   => $booking_papers
        ];

        return view('admin.bookings.container_papers', $input);
    }

    public function booking_container_policies(BookingContainer $booking)
    {

        $input = [
            'booking'      => $booking,
            'booking_policies'   => $booking->delivery_policies,
        ];

        return view('admin.bookings.container_policies', $input);
    }
}
