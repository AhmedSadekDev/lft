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
use App\Models\Car;
use App\Models\Driver;
use App\Services\CompanyTransportationPriceResolver;
use App\Services\ReeferTripTrackingService;
use App\Services\ShipmentWorkflow;
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
        $this->middleware('permission:bookings.update')->only(['edit', 'update']);
        $this->middleware('permission:bookings.delete')->only('destroy');
    }
        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Booking::query()
            ->with(['company', 'factory', 'invoice', 'bookingContainers']);

        $query->filterListing($request);

        // Calculate counts for each stage tab via single conditional aggregation query (Phase 4A optimization)
        // Waiting/second/third stage tabs belong to a shipment path: same container condition, filtered by booking type.
        $slotSql = [
            'waiting' => '`superagent_specification_approved` = 1 AND `is_in_loading` = 0 AND `superagent_loading_approved` = 0',
            1 => '`superagent_specification_approved` = 1 AND `is_in_loading` = 1 AND `superagent_loading_approved` = 0',
            2 => '`superagent_loading_approved` = 1 AND `superagent_unloading_approved` = 0',
        ];
        $stageSelects = [];
        $stageBindings = [];
        foreach (ShipmentWorkflow::tabs() as $tab => [$workflows, $slot]) {
            if ($slot === 0) {
                continue;
            }
            [$workflowSql, $workflowBindings] = ShipmentWorkflow::bookingSql($workflows);
            $stageSelects[] = "COUNT(CASE WHEN {$workflowSql} AND EXISTS (
                    SELECT 1 FROM `booking_containers`
                    WHERE `booking_containers`.`booking_id` = `bookings`.`id` AND {$slotSql[$slot]}
                ) THEN 1 END) as count_{$tab}";
            array_push($stageBindings, ...$workflowBindings);
        }
        $stageRow = (clone $query)->setEagerLoads([])
            ->selectRaw("
                COUNT(*) as count_all,
                COUNT(CASE WHEN EXISTS (
                    SELECT 1 FROM `booking_containers` 
                    WHERE `booking_containers`.`booking_id` = `bookings`.`id` 
                      AND (`status` = 0 OR (`status` = 1 AND `superagent_specification_approved` = 0))
                ) THEN 1 END) as count_assigned,
                ".implode(",\n", $stageSelects).",
                COUNT(CASE WHEN EXISTS (
                    SELECT 1 FROM `invoices` 
                    WHERE `invoices`.`booking_id` = `bookings`.`id`
                ) OR EXISTS (
                    SELECT 1 FROM `booking_containers` 
                    WHERE `booking_containers`.`booking_id` = `bookings`.`id` 
                      AND `superagent_unloading_approved` = 1
                ) THEN 1 END) as count_invoiced
            ", $stageBindings)
            ->first();

        $stageCounts = [
            'all'       => (int) ($stageRow->count_all ?? 0),
            'assigned'  => (int) ($stageRow->count_assigned ?? 0),
            'invoiced'  => (int) ($stageRow->count_invoiced ?? 0),
        ];
        foreach (array_keys(ShipmentWorkflow::tabs()) as $tab) {
            if ($tab !== 'specification') {
                $stageCounts[$tab] = (int) ($stageRow->{'count_'.$tab} ?? 0);
            }
        }

        // Stage filter
        $currentStage = $request->get('stage', $request->get('status', 'all'));
        if ($currentStage && $currentStage !== 'all') {
            $query->filterStage($currentStage);
        }

        // Per page filter
        $perPage = $request->get('per_page', 15);
        $perPage = min(max((int)$perPage, 15), 100); // Between 15 and 100

        $bookings = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        $companies = Company::query()->get();

        return view('admin.bookings.index', compact('bookings', 'companies', 'stageCounts', 'currentStage'));
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
            'shipping_agents'   => \App\Services\ReferenceDataService::getShippingAgentsPluck(),
            'type_of_actions'   => bookingActions(),
            'containers_type'   => Container::all()->pluck('full_name', 'id'),
            'factories'         => Factory::pluck('name', 'id'),
            'branches'          => \App\Services\ReferenceDataService::getBranchesPluck(),
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
                $bookingPaper = BookingPaper::create([
                    'booking_id' => $booking->id,
                    'type' => $request->type,
                    'booking_container_id' => $request->booking_container_id ?? null,
                ]);

                // Store relative path (booking_papers/...) like agent uploads — filename-only breaks dashboard URLs.
                $path = $request->file('image')->store('booking_papers', 'public');
                Image::create([
                    'image' => $path,
                    'imageable_id' => $bookingPaper->id,
                    'imageable_type' => BookingPaper::class,
                ]);
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
        $booking->loadCount(['bookingServices', 'receipts']);
        $booking->load([
            'bookingServices.service.serviceCategory',
            'bookingServices.supplier',
            'bookingServices.agent',
            'bookingServices.creator',
            'receipts.supplier',
            'receipts.bookingService.agent',
            'receipts.bookingService.creator',
        ]);

        $booking->loadExpensesForDisplay();
        $booking->expenses_count = $booking->expenses->count();

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
        // نجمّع الحاويات حسب (الفرع + نوع الحاوية + تاريخ الوصول) عشان كل مجموعة تتعدّل لوحدها
        // وما تتدمجش حاويات مختلفة في مجموعة واحدة (كان ده بيخلّي الحفظ يمسح حاويات).
        $groupedContainers = $booking->bookingContainers
            ->sortBy('id')
            ->groupBy(fn ($container) => implode('|', [
                $container->branch_id ?? 0,
                $container->container_id ?? 0,
                $container->arrival_date ?? '',
            ]))
            ->values();

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

            // Handle containers update - preserve existing containers and their data (expenses, container_no, etc.)
            // IMPORTANT: Do NOT delete existing containers to preserve expenses and container_no
            $existingContainers = $booking->bookingContainers->sortBy('id')->values();
            $requestContainers = $request->get('containers', []);

            // Check if request has container arrays (from thirdStep views) or nested arrays (from form view)
            $hasContainerArrays = $request->has('container_no') || $request->has('sail_of_numbers');

            // Only update containers if containers data is provided in request
            if (!empty($requestContainers) || $hasContainerArrays) {
            if ($hasContainerArrays) {
                // Handle arrays format (from thirdStep/outbound.blade.php or thirdStep/inbound.blade.php)
                $containerNos = $request->get('container_no', []);
                $sailOfNumbers = $request->get('sail_of_numbers', []);
                $arrivalDates = $request->get('arrival_dates', []);
                $branches = $request->get('branches', []);
                $containerIds = $request->get('containers', []);

                // Update existing containers instead of deleting them to preserve expenses and container_no
                $containerIndex = 0;
                foreach ($existingContainers as $existingContainer) {
                    if ($containerIndex < count($containerNos)) {
                        // Update existing container with new data, but preserve container_no if not provided
                        $updateData = [];

                        if (isset($containerIds[$containerIndex])) {
                            $updateData['container_id'] = $containerIds[$containerIndex];
                        }

                        // Only update container_no if provided and not empty
                        if (isset($containerNos[$containerIndex]) && !empty($containerNos[$containerIndex])) {
                            $updateData['container_no'] = $containerNos[$containerIndex];
                        }

                        // Only update sail_of_number if provided and not empty
                        if (isset($sailOfNumbers[$containerIndex]) && !empty($sailOfNumbers[$containerIndex])) {
                            $updateData['sail_of_number'] = $sailOfNumbers[$containerIndex];
                        }

                        if (isset($arrivalDates[$containerIndex])) {
                            $updateData['arrival_date'] = $arrivalDates[$containerIndex];
                        }

                        if (isset($branches[$containerIndex])) {
                            $updateData['branch_id'] = $branches[$containerIndex];
                        }

                        if (!empty($updateData)) {
                            $existingContainer->update($updateData);
                        }
                        $containerIndex++;
                    }
                }

                // If there are more containers in request than existing, create new ones
                while ($containerIndex < count($containerNos)) {
                    BookingContainer::create([
                        'booking_id'    => $booking->id,
                        'container_id'  => $containerIds[$containerIndex] ?? null,
                        'arrival_date'  => $arrivalDates[$containerIndex] ?? null,
                        'branch_id'     => $branches[$containerIndex] ?? null,
                        'container_no'  => $containerNos[$containerIndex] ?? null,
                        'sail_of_number'=> $sailOfNumbers[$containerIndex] ?? null,
                    ]);
                    $containerIndex++;
                }

                // If there are fewer containers in request, delete the extra ones (but only if they have no expenses or delivery policies)
                if ($containerIndex < $existingContainers->count()) {
                    $containersToDelete = $existingContainers->slice($containerIndex);
                    foreach ($containersToDelete as $containerToDelete) {
                        // Only delete if container has no expenses, delivery policies, or papers
                        $hasExpenses = $containerToDelete->expenses->count() > 0;
                        $hasDeliveryPolicies = $containerToDelete->delivery_policies->count() > 0;
                        $hasPapers = $containerToDelete->bookingPapers->count() > 0;

                        if (!$hasExpenses && !$hasDeliveryPolicies && !$hasPapers) {
                            $containerToDelete->delete();
                        }
                    }
                }
            } else {
                // Nested groups format (form.blade.php): containers[i][branch_id|container_id|arrival_date|containers_count|booking_container_ids]
                // الحفظ هنا بيعدّل الحاويات الموجودة نفسها (بدون مسح) ويحافظ على أرقامها ومصروفاتها.
                $protectedKept = $this->syncBookingContainerGroups($booking, $requestContainers);
            }
            }
            // If no containers data in request, keep existing containers unchanged (only update booking details)

            if ($request->hasFile('image')) {
                $bookingPaper = BookingPaper::create([
                    'booking_id' => $booking->id,
                    'type' => $request->type,
                    'booking_container_id' => $request->booking_container_id ?? null,
                ]);

                $path = $request->file('image')->store('booking_papers', 'public');
                Image::create([
                    'image' => $path,
                    'imageable_id' => $bookingPaper->id,
                    'imageable_type' => BookingPaper::class,
                ]);
            }

            // Commit the transaction
            DB::commit();

            $message = __('alerts.updated_successfully');
            if (($protectedKept ?? 0) > 0) {
                $message .= ' — لم يتم حذف ' . $protectedKept . ' حاوية لأن عليها بيانات (رقم حاوية/مصروفات/بوليصة/ملفات). احذفها يدويًا لو مطلوب.';
            }

            return redirect()->route('bookings.index')->with('success', $message);
        } catch (\Throwable $th) {
            // Rollback the transaction on error
            DB::rollBack();
            // Handle the exception and redirect back with error message
            $errorMessage = $th->getMessage() ?: ($th->getResponse()?->getData() ?: 'An error occurred while updating the booking.');
            return redirect()->route('bookings.index')->with('error', $errorMessage);
        }
    }


    /**
     * مزامنة مجموعات الحاويات من فورم التعديل بدون مسح بيانات.
     *
     * - المجموعة بتحمل booking_container_ids → نعدّل نفس الحاويات (فرع/نوع/تاريخ) ونحافظ على رقم الحاوية والمصروفات.
     * - مجموعة جديدة (بدون ids) → نضيف حاويات جديدة بعددها.
     * - مجموعة مش موجودة في الطلب → ما نلمسها (غياب مجموعة ≠ حذف).
     * - لو العدد اتقلّل → نحذف الزيادة بس لو فاضية تمامًا (بدون رقم/مصروفات/بوليصة/ملفات).
     *
     * @return int عدد الحاويات اللي كان لازم تتحذف بس اتحمت لأن عليها بيانات
     */
    private function syncBookingContainerGroups(Booking $booking, array $groups): int
    {
        $protectedKept = 0;
        $bookingContainers = $booking->bookingContainers()->orderBy('id')->get();

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $containerTypeId = isset($group['container_id']) && is_numeric($group['container_id'])
                ? (int) $group['container_id'] : null;
            $branchProvided = array_key_exists('branch_id', $group);
            $branchId = isset($group['branch_id']) && is_numeric($group['branch_id'])
                ? (int) $group['branch_id'] : null;
            $arrivalDate = ! empty($group['arrival_date']) ? $group['arrival_date'] : null;
            $count = max(1, (int) ($group['containers_count'] ?? 1));

            $hasIdsKey = array_key_exists('booking_container_ids', $group);
            if ($hasIdsKey) {
                $ids = array_values(array_filter(array_map('intval', explode(',', (string) $group['booking_container_ids']))));
                $members = $bookingContainers->whereIn('id', $ids)->values();
            } else {
                // فورم قديم مفتوح قبل التحديث: نطابق على النوع + الفرع ونمنع التكرار.
                $members = $bookingContainers->filter(
                    fn ($c) => (int) $c->container_id === (int) $containerTypeId
                        && (int) $c->branch_id === (int) $branchId
                )->values();
            }

            $attributes = [];
            if ($containerTypeId !== null) {
                $attributes['container_id'] = $containerTypeId;
            }
            if ($branchProvided) {
                $attributes['branch_id'] = $branchId;
            }
            if ($arrivalDate !== null) {
                $attributes['arrival_date'] = $arrivalDate;
            }

            // 1) عدّل الحاويات الحالية في المجموعة
            if ($attributes !== []) {
                foreach ($members as $member) {
                    $member->update($attributes);
                }
            }

            // 2) زوّد لو العدد أكبر
            $missing = $count - $members->count();
            if ($missing > 0 && $containerTypeId !== null) {
                for ($i = 0; $i < $missing; $i++) {
                    BookingContainer::create($attributes + ['booking_id' => $booking->id]);
                }
            }

            // 3) قلّل لو العدد أصغر (فقط الحاويات الفاضية تمامًا، من الآخر)
            if ($hasIdsKey && $missing < 0) {
                $excess = -$missing;
                foreach ($members->reverse() as $member) {
                    if ($excess <= 0) {
                        break;
                    }
                    $inUse = filled($member->container_no)
                        || filled($member->sail_of_number)
                        || $member->expenses()->exists()
                        || $member->delivery_policies()->exists()
                        || $member->bookingPapers()->exists();

                    if ($inUse) {
                        $protectedKept++;
                        continue;
                    }

                    $member->delete();
                    $excess--;
                }
            }
        }

        return $protectedKept;
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

    public function print_land_bol(DeliveryPolicy $deliveryPolicy)
    {
        $deliveryPolicy->load([
            'driver',
            'car',
            'money_transfer',
            'reeferTripLegs.images',
            'booking_containers.booking.company',
            'booking_containers.departure',
            'booking_containers.loading',
            'booking_containers.aging',
            'booking_containers.container',
        ]);

        $isReefer = app(ReeferTripTrackingService::class)->policyIsReefer($deliveryPolicy);

        return view('admin.bookings.land_bol_print', [
            'policy' => $deliveryPolicy,
            'isReefer' => $isReefer,
            'timeline' => $isReefer
                ? app(ReeferTripTrackingService::class)->buildTimeline($deliveryPolicy)
                : [],
        ]);
    }

    public function edit_delivery_policy(DeliveryPolicy $deliveryPolicy)
    {
        $deliveryPolicy->load([
            'driver',
            'car',
            'money_transfer',
            'booking_containers.booking',
            'booking_containers.departure',
            'booking_containers.loading',
            'booking_containers.aging',
        ]);

        if ((int) $deliveryPolicy->is_settled === 1) {
            return back()->with('error', __('main.delivery_policy is settled'));
        }

        $container = $deliveryPolicy->booking_containers->first();
        $booking = $container?->booking;
        $returnTo = request()->query('return') === 'booking' ? 'booking' : 'container';

        return view('admin.bookings.delivery_policy_edit', [
            'policy' => $deliveryPolicy,
            'booking' => $booking,
            'container' => $container,
            'returnTo' => $returnTo,
            'drivers' => Driver::query()->orderBy('name')->get(['id', 'name']),
            'cars' => Car::query()->orderBy('car_number')->get(['id', 'car_number']),
            'cities' => CitiesAndRegions::pluck('title', 'id'),
            'isReefer' => app(ReeferTripTrackingService::class)->policyIsReefer($deliveryPolicy),
        ]);
    }

    public function update_delivery_policy(Request $request, DeliveryPolicy $deliveryPolicy)
    {
        $deliveryPolicy->load(['money_transfer', 'booking_containers.booking']);

        if ((int) $deliveryPolicy->is_settled === 1) {
            return back()->with('error', __('main.delivery_policy is settled'));
        }

        $isReefer = app(ReeferTripTrackingService::class)->policyIsReefer($deliveryPolicy);

        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'car_id' => 'required|exists:cars,id',
            'value' => 'required|numeric|min:0',
            'office_commission' => 'nullable|numeric|min:0',
            'custody_usd' => 'nullable|numeric|min:0',
            'date' => 'nullable|date',
            'departure_id' => 'nullable|exists:cities_and_regions,id',
            'loading_id' => 'nullable|exists:cities_and_regions,id',
            'aging_id' => 'nullable|exists:cities_and_regions,id',
        ]);

        if ($request->filled('custody_usd') && ! $isReefer) {
            return back()->withInput()->with('error', __('main.custody_usd_reefer_only'));
        }

        try {
            DB::transaction(function () use ($request, $deliveryPolicy, $isReefer) {
                $agent = null;
                if ($deliveryPolicy->money_transfer
                    && $deliveryPolicy->money_transfer->transferer_type === Agent::class) {
                    $agent = Agent::lockForUpdate()->find($deliveryPolicy->money_transfer->transferer_id);
                }

                $oldValue = (float) ($deliveryPolicy->money_transfer?->value ?? 0);
                $oldCommission = (float) ($deliveryPolicy->office_commission ?? 0);
                $oldActualDeduction = $oldValue - $oldCommission;

                $newValue = (float) $request->input('value');
                $newCommission = (float) ($request->input('office_commission') ?? 0);
                $newActualDeduction = $newValue - $newCommission;
                $actualDiff = round($newActualDeduction - $oldActualDeduction, 2);

                if ($agent && $actualDiff > 0 && (float) $agent->wallet < $actualDiff) {
                    abort(422, __('main.you dont have enougth money'));
                }

                if ($request->filled('loading_id')) {
                    $city = CitiesAndRegions::find($request->input('loading_id'));
                    if ($city) {
                        $deliveryPolicy->address = (string) $city->title;
                    }
                }

                $deliveryPolicy->driver_id = (int) $request->input('driver_id');
                $deliveryPolicy->car_id = (int) $request->input('car_id');
                $deliveryPolicy->office_commission = $newCommission;
                if ($request->filled('date')) {
                    $deliveryPolicy->date = $request->input('date');
                }
                if ($isReefer && $request->has('custody_usd')) {
                    $oldCustodyUsd = (float) ($deliveryPolicy->custody_usd ?? 0);
                    $newCustodyUsd = (float) $request->input('custody_usd');
                    $usdDiff = round($newCustodyUsd - $oldCustodyUsd, 2);
                    if ($agent && $usdDiff != 0.0) {
                        if ($usdDiff > 0 && (float) ($agent->wallet_usd ?? 0) < $usdDiff) {
                            abort(422, 'رصيد دولار المندوب غير كافٍ للعهدة');
                        }
                        $agent->wallet_usd = round((float) ($agent->wallet_usd ?? 0) - $usdDiff, 2);
                        $agent->save();
                    }
                    $deliveryPolicy->custody_usd = $newCustodyUsd;
                }
                $deliveryPolicy->save();

                if ($deliveryPolicy->money_transfer) {
                    $deliveryPolicy->money_transfer->update([
                        'value' => $newValue,
                        'transfered_type' => Driver::class,
                        'transfered_id' => $deliveryPolicy->driver_id,
                        'date' => $deliveryPolicy->date ?? $deliveryPolicy->money_transfer->date,
                        'address' => $deliveryPolicy->address,
                    ]);
                }

                $officeCommissionTransfer = MoneyTransfer::where('delivery_policy_id', $deliveryPolicy->id)
                    ->where('type', MoneyTransfer::officeCommission)
                    ->first();

                if ($newCommission > 0) {
                    $commissionPayload = [
                        'value' => $newCommission,
                        'type' => MoneyTransfer::officeCommission,
                        'transferer_type' => Driver::class,
                        'transferer_id' => $deliveryPolicy->driver_id,
                        'transfered_type' => Agent::class,
                        'transfered_id' => $agent?->id,
                        'delivery_policy_id' => $deliveryPolicy->id,
                        'date' => $deliveryPolicy->date ?? now(),
                        'address' => $deliveryPolicy->address,
                    ];
                    if ($officeCommissionTransfer) {
                        $officeCommissionTransfer->update($commissionPayload);
                    } elseif ($agent) {
                        MoneyTransfer::create($commissionPayload);
                    }
                } elseif ($officeCommissionTransfer) {
                    $officeCommissionTransfer->delete();
                }

                if ($agent && $actualDiff != 0.0) {
                    $agent->update(['wallet' => round((float) $agent->wallet - $actualDiff, 2)]);
                }

                $routeContainer = $deliveryPolicy->booking_containers()->with('booking')->first();
                if ($routeContainer && ($request->filled('departure_id') || $request->filled('loading_id') || $request->filled('aging_id'))) {
                    $routePatch = array_filter([
                        'departure_id' => $request->input('departure_id'),
                        'loading_id' => $request->input('loading_id'),
                        'aging_id' => $request->input('aging_id'),
                    ], fn ($v) => $v !== null && $v !== '');
                    if ($routePatch !== []) {
                        $routeContainer->update($routePatch);
                        $routeContainer->refresh();
                        app(CompanyTransportationPriceResolver::class)->applyToContainer($routeContainer);
                    }
                }
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $deliveryPolicy->loadMissing('booking_containers.booking');
        $container = $deliveryPolicy->booking_containers->first();
        $booking = $container?->booking;
        $returnTo = $request->input('return') === 'booking' ? 'booking' : 'container';

        if ($returnTo === 'booking' && $booking) {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('success', __('alerts.updated_successfully'));
        }

        if ($container) {
            return redirect()
                ->route('bookings.booking_container_policies', $container->id)
                ->with('success', __('alerts.updated_successfully'));
        }

        if ($booking) {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('success', __('alerts.updated_successfully'));
        }

        return redirect()
            ->route('bookings.index')
            ->with('success', __('alerts.updated_successfully'));
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
                $bookingPaper = BookingPaper::create([
                    'booking_id' => $booking->id,
                    'type' => $request->type,
                    'booking_container_id' => $request->booking_container_id ?? null,
                ]);

                // Same path shape as agent/API uploads so Image accessor resolves /storage/booking_papers/...
                $path = $request->file('image')->store('booking_papers', 'public');
                Image::create([
                    'image' => $path,
                    'imageable_id' => $bookingPaper->id,
                    'imageable_type' => BookingPaper::class,
                ]);
            }

            return back()->with('success', 'تم اضافة الملف بنجاح');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

}
