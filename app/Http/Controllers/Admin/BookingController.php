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
use App\Models\Note;
use App\Models\Container;
use App\Models\DeliveryPolicy;
use App\Models\Employee;
use App\Models\Image;
use App\Models\Factory;
use App\Models\ServiceCategory;
use App\Models\shippingAgent;
use App\Models\MoneyTransfer;
use App\Models\Agent;
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

        // Per page filter
        $perPage = $request->get('per_page', 15);
        $perPage = min(max((int)$perPage, 15), 100); // Between 15 and 100

        $bookings = $bookings->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

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
    public function store(Request $request)
    {
        // dd($request->all());
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
            if ($request->hasFile('image')) {

                // Create new paper
                $bookingPaper = BookingPaper::create([
                    'booking_id' => $booking->id,
                    'type' => $request->type,
                    'booking_container_id' => $request->booking_container_id ?? null,
                ]);

                // Save image file
                $path = $request->file('image')->store('uploads', 'public');
                $image_data["image"] = $request->image;
                $image_data["imageable_id"] = $bookingPaper->id;
                $image_data["imageable_type"] = "App\Models\BookingPaper";
                Image::create($image_data);
            }

            $company = Company::find($request->company_id);
            $employee = Employee::find($request->employee_id);
            Notification::send($company, new NewBooking($booking));
            Notification::send($employee, new NewBooking($booking));

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
            'deliveryPolices'   => DeliveryPolicy::whereHas('booking_containers', function($container) use($booking) {
                $container->where('booking_id', $booking->id);
            })->get()
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
        // dd($request->all());
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
            if ($request->hasFile('image')) {

                // Create new paper
                $bookingPaper = BookingPaper::create([
                    'booking_id' => $booking->id,
                    'type' => $request->type,
                    'booking_container_id' => $request->booking_container_id ?? null,
                ]);

                // Save image file
                $path = $request->file('image')->store('uploads', 'public');
                $image_data["image"] = $request->image;
                $image_data["imageable_id"] = $bookingPaper->id;
                $image_data["imageable_type"] = "App\Models\BookingPaper";
                Image::create($image_data);
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
    public function booking_notes(Booking $booking)
    {
        $BookingContainer = BookingContainer::where('booking_id', $booking->id)->first();
        $notes = Note::where('attached_id', $BookingContainer->id)->get();
        $input = [
            'booking'      => $booking,
            'notes'   => $notes,
        ];

        return view('admin.bookings.notes', $input);
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

    public function delete_delivery_policy($id)
    {
        try {
            $delivery_policy = DeliveryPolicy::with([
                'money_transfer',
                'car_expenses',
                'extraExpenses',
                'payingCars',
                'booking_containers'
            ])->findOrFail($id);

            if ($delivery_policy->is_settled == 1) {
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('main.delivery_policy is settled')
                    ], 400);
                }
                return back()->with('error', __('main.delivery_policy is settled'));
            }

            DB::beginTransaction();

            // الحصول على المندوب من money_transfer
            $agent = null;
            if ($delivery_policy->money_transfer && $delivery_policy->money_transfer->transferer_type === 'App\Models\Agent') {
                $agent = \App\Models\Agent::find($delivery_policy->money_transfer->transferer_id);
            }

            // حساب المبلغ الذي يجب إرجاعه (القيمة - دخان المكتب)
            $valueToRefund = (float) ($delivery_policy->money_transfer?->value ?? 0);
            $officeCommission = (float) ($delivery_policy->office_commission ?? 0);
            $actualRefund = $valueToRefund - $officeCommission; // المبلغ الفعلي الذي تم خصمه

            // حذف جميع المصروفات المرتبطة بالبوليصة (car_expenses)
            foreach ($delivery_policy->car_expenses as $expense) {
                // حذف صورة المصروف إن وجدت
                if ($expense->image_agent_expenses) {
                    $path = public_path('Admin/images/expenses/' . $expense->image_agent_expenses);
                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }
                $expense->delete();
            }

            // حذف جميع المصروفات الإضافية المرتبطة بالبوليصة
            foreach ($delivery_policy->extraExpenses as $extraExpense) {
                $extraExpense->delete();
            }

            // حذف جميع سجلات السداد المرتبطة بالبوليصة
            foreach ($delivery_policy->payingCars as $payingCar) {
                // حذف صورة السداد إن وجدت
                if ($payingCar->image) {
                    $path = public_path($payingCar->image);
                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }
                // حذف money_transfer المرتبط بالسداد
                $payingCar->moneyTransfers()->delete();
                $payingCar->delete();
            }

            // إرجاع الفلوس للمندوب
            if ($actualRefund > 0 && $agent) {
                $agent->update(['wallet' => $agent->wallet + $actualRefund]);
            }

            // حذف الصورة المرتبطة بالبوليصة
            if ($delivery_policy->image) {
                $delivery_policy->image()->delete();
            }

            // فصل البوليصة عن الحاويات
            $delivery_policy->booking_containers()->detach();

            // حذف معاملة العهدة (type 3)
            if ($delivery_policy->money_transfer) {
                $delivery_policy->money_transfer()->delete();
            }

            // حذف معاملة دخان المكتب (type 5) إن وجدت
            MoneyTransfer::where('delivery_policy_id', $delivery_policy->id)
                ->where('type', 5)
                ->delete();

            // حذف البوليصة نفسها
            $delivery_policy->delete();

            DB::commit();

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('alerts.deleted_successfully')
                ], 200);
            }

            return back()->with('success', __('alerts.deleted_successfully'));
        } catch (\Exception $Exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $Exception->getMessage()
                ], 500);
            }

            return back()->with('error', $Exception->getMessage());
        }
    }
    public function deletePaper(Request $request, BookingPaper $booking)
    {
        $booking->delete();
        return back()->with('success', 'تم حذف الورقه بنجاح');
    }
    public function storePapers(Request $request)
    {
        try {
            $request->validate([
                'booking_id' => 'required|exists:bookings,id',
                'type' => 'required|integer',
                'image' => 'required|file', // allow images or PDFs
            ]);

            $booking = Booking::findOrFail($request->booking_id);

            // If yard_id is sent, update booking and all containers
            if ($request->filled('yard_id')) {
                $booking->update([
                    'yard_id' => $request->yard_id,
                ]);

                foreach ($booking->bookingContainers as $container) {
                    $container->update([
                        'yard_id' => $request->yard_id,
                    ]);
                }
            }

            // If image uploaded, handle file and paper record
            if ($request->hasFile('image')) {

                // Create new paper
                $bookingPaper = BookingPaper::create([
                    'booking_id' => $booking->id,
                    'type' => $request->type,
                    'booking_container_id' => $request->booking_container_id ?? null,
                ]);

                // Save image file
                $path = $request->file('image')->store('uploads', 'public');
                $image_data["image"] = $request->image;
                $image_data["imageable_id"] = $bookingPaper->id;
                $image_data["imageable_type"] = "App\Models\BookingPaper";
                Image::create($image_data);
            }

            return back()->with('success', 'تم اضافة الملف بنجاح');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

}
