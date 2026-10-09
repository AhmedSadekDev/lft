<?php

// Script to precisely update controllers for Phase 6 pagination

// 1. Agent DeliveryPolicyController
$file1 = __DIR__ . '/../app/Http/Controllers/Api/Agent/DeliveryPolicyController.php';
$content1 = file_get_contents($file1);
$target1 = '            $delivery_policies = DeliveryPolicy::whereDoesntHave(\'booking_containers.booking.invoice\')->with([
                \'car\',
                \'driver\',
                \'money_transfer\',
                \'image\',
                \'booking_containers\' => function ($query) {
                    $query->with([\'booking\', \'branch\']);
                }
            ])->whereHas("money_transfer", function ($q) use ($agent) {
                return $q->where("transferer_id", $agent->id);
            })->get();

            $data = DeliveryPolicyResource::collection($delivery_policies);


            return $this->returnAllData($data, __(\'alerts.success\'));';

$replace1 = '            $perPage = (int) request()->get(\'per_page\', 20);
            $page = (int) request()->get(\'page\', 1);

            $delivery_policies = DeliveryPolicy::whereDoesntHave(\'booking_containers.booking.invoice\')->with([
                \'car\',
                \'driver\',
                \'money_transfer\',
                \'image\',
                \'booking_containers\' => function ($query) {
                    $query->with([\'booking\', \'branch\']);
                }
            ])->whereHas("money_transfer", function ($q) use ($agent) {
                return $q->where("transferer_id", $agent->id);
            })
            ->orderBy(\'id\', \'desc\')
            ->paginate($perPage, [\'*\'], \'page\', $page);

            $data = DeliveryPolicyResource::collection($delivery_policies->items());
            $pagination = [
                \'total\' => $delivery_policies->total(),
                \'per_page\' => $delivery_policies->perPage(),
                \'current_page\' => $delivery_policies->currentPage(),
                \'total_pages\' => $delivery_policies->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);';

$content1 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target1), preg_replace('/\r\n|\r|\n/', "\n", $replace1), preg_replace('/\r\n|\r|\n/', "\n", $content1));
file_put_contents($file1, $content1);
echo "Updated DeliveryPolicyController\n";

// 2. Agent ExpenseController
$file2 = __DIR__ . '/../app/Http/Controllers/Api/Agent/ExpenseController.php';
$content2 = file_get_contents($file2);
$target2 = '            $merged = $financial_custodies->concat($expenses);

            $ordered = $merged->sortBy(\'created_at\')->values();

            $data = ExpenseResource::collection($ordered);

            return $this->returnAllData($data, __(\'alerts.success\'));';

$replace2 = '            $merged = $financial_custodies->concat($expenses);

            $ordered = $merged->sortBy(\'created_at\')->values();
            $total = $ordered->count();
            $perPage = (int) request()->get(\'per_page\', 20);
            $page = (int) request()->get(\'page\', 1);
            $slice = $ordered->slice(($page - 1) * $perPage, $perPage)->values();

            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $slice,
                $total,
                $perPage,
                $page
            );

            $data = ExpenseResource::collection($slice);
            $pagination = [
                \'total\' => $paginator->total(),
                \'per_page\' => $paginator->perPage(),
                \'current_page\' => $paginator->currentPage(),
                \'total_pages\' => $paginator->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);';

$content2 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target2), preg_replace('/\r\n|\r|\n/', "\n", $replace2), preg_replace('/\r\n|\r|\n/', "\n", $content2));
file_put_contents($file2, $content2);
echo "Updated ExpenseController\n";

// 3. Agent TransferAgentController
$file3 = __DIR__ . '/../app/Http/Controllers/Api/Agent/TransferAgentController.php';
$content3 = file_get_contents($file3);
$target3 = '            $agent = auth()->guard(\'agent\')->user();

            $agents = Agent::where("id", "!=", $agent->id)->ofFilter()->get();

            $data = NameResource::collection($agents);


            return $this->returnAllData($data, __(\'alerts.success\'));';

$replace3 = '            $agent = auth()->guard(\'agent\')->user();
            $perPage = (int) request()->get(\'per_page\', 20);
            $page = (int) request()->get(\'page\', 1);

            $agents = Agent::where("id", "!=", $agent->id)->ofFilter()->orderBy(\'id\', \'desc\')->paginate($perPage, [\'*\'], \'page\', $page);

            $data = NameResource::collection($agents->items());
            $pagination = [
                \'total\' => $agents->total(),
                \'per_page\' => $agents->perPage(),
                \'current_page\' => $agents->currentPage(),
                \'total_pages\' => $agents->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);';

$content3 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target3), preg_replace('/\r\n|\r|\n/', "\n", $replace3), preg_replace('/\r\n|\r|\n/', "\n", $content3));
file_put_contents($file3, $content3);
echo "Updated TransferAgentController\n";

// 4. Superagent AgentController
$file4 = __DIR__ . '/../app/Http/Controllers/Api/Superagent/AgentController.php';
$content4 = file_get_contents($file4);
$target4 = '            $agents = Agent::orderBy("id", "desc")->ofFilter()->get();

            $todayBookings = BookingContainerAgent::whereIn(\'agent_id\', $agents->modelKeys())
                ->whereDate(\'created_at\', now())
                ->groupBy(\'agent_id\')
                ->selectRaw(\'agent_id, count(distinct booking_container_id) as aggregate\')
                ->pluck(\'aggregate\', \'agent_id\');
            $agents->each(fn (Agent $agent) => $agent->setAttribute(\'number_of_bookings\', (int) ($todayBookings[$agent->id] ?? 0)));

            $data = AgentResource::collection($agents);

            //response

            return $this->returnAllData($data, __(\'alerts.success\'));';

$replace4 = '            $perPage = (int) request()->get(\'per_page\', 20);
            $page = (int) request()->get(\'page\', 1);

            $agents = Agent::orderBy("id", "desc")->ofFilter()->paginate($perPage, [\'*\'], \'page\', $page);

            $todayBookings = BookingContainerAgent::whereIn(\'agent_id\', $agents->getCollection()->modelKeys())
                ->whereDate(\'created_at\', now())
                ->groupBy(\'agent_id\')
                ->selectRaw(\'agent_id, count(distinct booking_container_id) as aggregate\')
                ->pluck(\'aggregate\', \'agent_id\');
            $agents->getCollection()->each(fn (Agent $agent) => $agent->setAttribute(\'number_of_bookings\', (int) ($todayBookings[$agent->id] ?? 0)));

            $data = AgentResource::collection($agents->items());
            $pagination = [
                \'total\' => $agents->total(),
                \'per_page\' => $agents->perPage(),
                \'current_page\' => $agents->currentPage(),
                \'total_pages\' => $agents->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);';

$content4 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target4), preg_replace('/\r\n|\r|\n/', "\n", $replace4), preg_replace('/\r\n|\r|\n/', "\n", $content4));
file_put_contents($file4, $content4);
echo "Updated Superagent AgentController\n";

// 5. Agent YardController
$file5 = __DIR__ . '/../app/Http/Controllers/Api/Agent/YardController.php';
$content5 = file_get_contents($file5);
$target5 = '            // Get bookings that belong to the specified yard and have containers assigned to this agent
            $bookings = Booking::where(\'yard_id\', $request->yard_id)
                ->whereDoesntHave(\'invoice\')
                ->whereHas(\'bookingContainers\', function ($query) use ($agent_booking_containers) {
                    $query->whereIn(\'booking_containers.id\', $agent_booking_containers->pluck(\'id\')->toArray());
                })
                ->with([\'bookingContainers\' => function ($query) use ($agent_booking_containers) {
                    $query->whereIn(\'booking_containers.id\', $agent_booking_containers->pluck(\'id\')->toArray());
                }])
                ->orderBy(\'id\', \'desc\')
                ->get();

            $data = BookingResource::collection($bookings);

            return $this->returnAllData($data, __(\'alerts.success\'));';

$replace5 = '            $perPage = (int) $request->get(\'per_page\', 20);
            $page = (int) $request->get(\'page\', 1);

            // Get bookings that belong to the specified yard and have containers assigned to this agent
            $bookings = Booking::where(\'yard_id\', $request->yard_id)
                ->whereDoesntHave(\'invoice\')
                ->whereHas(\'bookingContainers\', function ($query) use ($agent_booking_containers) {
                    $query->whereIn(\'booking_containers.id\', $agent_booking_containers->pluck(\'id\')->toArray());
                })
                ->with([\'bookingContainers\' => function ($query) use ($agent_booking_containers) {
                    $query->whereIn(\'booking_containers.id\', $agent_booking_containers->pluck(\'id\')->toArray());
                }])
                ->orderBy(\'id\', \'desc\')
                ->paginate($perPage, [\'*\'], \'page\', $page);

            $data = BookingResource::collection($bookings->items());
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);';

$content5 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target5), preg_replace('/\r\n|\r|\n/', "\n", $replace5), preg_replace('/\r\n|\r|\n/', "\n", $content5));
file_put_contents($file5, $content5);
echo "Updated Agent YardController\n";

// 6. Superagent YardController
$file6 = __DIR__ . '/../app/Http/Controllers/Api/Superagent/YardController.php';
$content6 = file_get_contents($file6);
$target6 = '            $bookings = Booking::where(\'yard_id\', $request->yard_id)
                ->withoutInvoice()
                ->whereHas(\'bookingContainers\', static function ($q) {
                    $q->whereIn(\'booking_containers.status\', [0, 1]);
                })
                ->with([
                    \'bookingContainers\' => fn ($q) => $q->withoutInvoicedBooking()->with([
                        \'branch.factory\',
                        \'container\',
                        \'booking.company\',
                        \'booking.yard\',
                    ]),
                    \'company\',
                    \'yard\',
                    \'factory\'
                ])
                ->orderBy(\'id\', \'desc\')
                ->get();

            $data = YardBookingResource::collection($bookings);

            return $this->returnAllData($data, __(\'alerts.success\'));';

$replace6 = '            $perPage = (int) $request->get(\'per_page\', 20);
            $page = (int) $request->get(\'page\', 1);

            $bookings = Booking::where(\'yard_id\', $request->yard_id)
                ->withoutInvoice()
                ->whereHas(\'bookingContainers\', static function ($q) {
                    $q->whereIn(\'booking_containers.status\', [0, 1]);
                })
                ->with([
                    \'bookingContainers\' => fn ($q) => $q->withoutInvoicedBooking()->with([
                        \'branch.factory\',
                        \'container\',
                        \'booking.company\',
                        \'booking.yard\',
                    ]),
                    \'company\',
                    \'yard\',
                    \'factory\'
                ])
                ->orderBy(\'id\', \'desc\')
                ->paginate($perPage, [\'*\'], \'page\', $page);

            $data = YardBookingResource::collection($bookings->items());
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);';

$content6 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target6), preg_replace('/\r\n|\r|\n/', "\n", $replace6), preg_replace('/\r\n|\r|\n/', "\n", $content6));
file_put_contents($file6, $content6);
echo "Updated Superagent YardController\n";

// 7. Agent BookingContainerAssignmentController
$file7 = __DIR__ . '/../app/Http/Controllers/Api/Agent/BookingContainerAssignmentController.php';
$content7 = file_get_contents($file7);
$target7a = '            // fetch bookings that don\'t have an invoice
            $bookings = Booking::whereIn("id", $booking_ids_without_invoices)
                ->when($word != null, function ($q) use ($word) {
                    $q->where(function ($search) use ($word) {
                        $search->where("booking_number", "LIKE", "%$word%")
                            ->orWhereHas("bookingContainers", fn ($containers) => $containers->where("container_no", "LIKE", "%$word%"));
                    });
                })
                ->orderBy("id", "desc")
                ->get();


            //return data
            $data = BookingResource::collection($bookings);


            return $this->returnAllData($data, __(\'alerts.success\'));';

$replace7a = '            $perPage = (int) $request->get(\'per_page\', 20);
            $page = (int) $request->get(\'page\', 1);

            // fetch bookings that don\'t have an invoice
            $bookings = Booking::whereIn("id", $booking_ids_without_invoices)
                ->when($word != null, function ($q) use ($word) {
                    $q->where(function ($search) use ($word) {
                        $search->where("booking_number", "LIKE", "%$word%")
                            ->orWhereHas("bookingContainers", fn ($containers) => $containers->where("container_no", "LIKE", "%$word%"));
                    });
                })
                ->orderBy("id", "desc")
                ->paginate($perPage, [\'*\'], \'page\', $page);


            //return data
            $data = BookingResource::collection($bookings->items());
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];


            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);';

$target7b = '            $agent_booking_containers = \App\Models\BookingContainer::whereIn(\'id\', $ids)
                ->whereDoesntHave(\'delivery_policies\')
                ->get();

            //return data
            $data = SimpleBookingContainer2Resource::collection($agent_booking_containers);


            return $this->returnAllData($data, __(\'alerts.success\'));';

$replace7b = '            $perPage = (int) request()->get(\'per_page\', 20);
            $page = (int) request()->get(\'page\', 1);

            $agent_booking_containers = \App\Models\BookingContainer::whereIn(\'id\', $ids)
                ->whereDoesntHave(\'delivery_policies\')
                ->orderBy(\'id\', \'desc\')
                ->paginate($perPage, [\'*\'], \'page\', $page);

            //return data
            $data = SimpleBookingContainer2Resource::collection($agent_booking_containers->items());
            $pagination = [
                \'total\' => $agent_booking_containers->total(),
                \'per_page\' => $agent_booking_containers->perPage(),
                \'current_page\' => $agent_booking_containers->currentPage(),
                \'total_pages\' => $agent_booking_containers->lastPage(),
            ];


            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);';

$content7 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target7a), preg_replace('/\r\n|\r|\n/', "\n", $replace7a), preg_replace('/\r\n|\r|\n/', "\n", $content7));
$content7 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target7b), preg_replace('/\r\n|\r|\n/', "\n", $replace7b), preg_replace('/\r\n|\r|\n/', "\n", $content7));
file_put_contents($file7, $content7);
echo "Updated BookingContainerAssignmentController\n";

// 8. Agent AgentPhotoController
$file8 = __DIR__ . '/../app/Http/Controllers/Api/Agent/AgentPhotoController.php';
$content8 = file_get_contents($file8);
$target8 = '    public function index(Request $request)
    {
        $data = $request->validate([\'per_page\' => \'sometimes|integer|min:1|max:100\']);
        $photos = AgentPhoto::where(\'agent_id\', auth(\'agent\')->id())
            ->latest(\'id\')->paginate($data[\'per_page\'] ?? 24);
        $photos->getCollection()->transform(fn ($photo) => $this->photoData($photo));

        return $this->returnAllData($photos, __(\'alerts.success\'));
    }';

$replace8 = '    public function index(Request $request)
    {
        $data = $request->validate([\'per_page\' => \'sometimes|integer|min:1\']);
        $photos = AgentPhoto::where(\'agent_id\', auth(\'agent\')->id())
            ->latest(\'id\')->paginate($data[\'per_page\'] ?? 24);
        $photos->getCollection()->transform(fn ($photo) => $this->photoData($photo));

        $pagination = [
            \'total\' => $photos->total(),
            \'per_page\' => $photos->perPage(),
            \'current_page\' => $photos->currentPage(),
            \'total_pages\' => $photos->lastPage(),
        ];

        return response()->json([
            \'status\' => true,
            \'errNum\' => "0000",
            \'message\' => __(\'alerts.success\'),
            \'data\' => $photos->items(),
            \'pagination\' => $pagination,
        ], 200);
    }';

$content8 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target8), preg_replace('/\r\n|\r|\n/', "\n", $replace8), preg_replace('/\r\n|\r|\n/', "\n", $content8));
file_put_contents($file8, $content8);
echo "Updated AgentPhotoController\n";

// 9. Superagent ContainerStageController
$file9 = __DIR__ . '/../app/Http/Controllers/Api/Superagent/ContainerStageController.php';
$content9 = file_get_contents($file9);
$target9 = '        $containers->getCollection()->transform(fn ($container) => [
            \'booking_container_id\' => $container->id,
            \'booking_id\' => $container->booking_id,
            \'container_number\' => $container->container_no,
            \'type_id\' => $type,
            \'agent_ids\' => $container->agents->pluck(\'id\')->unique()->values(),
            \'receipts_version\' => $container->stages->firstWhere(\'type_id\', $type)?->version ?? 1,
        ]);

        return $this->returnAllData($containers, __(\'alerts.success\'));';

$replace9 = '        $containers->getCollection()->transform(fn ($container) => [
            \'booking_container_id\' => $container->id,
            \'booking_id\' => $container->booking_id,
            \'container_number\' => $container->container_no,
            \'type_id\' => $type,
            \'agent_ids\' => $container->agents->pluck(\'id\')->unique()->values(),
            \'receipts_version\' => $container->stages->firstWhere(\'type_id\', $type)?->version ?? 1,
        ]);

        $pagination = [
            \'total\' => $containers->total(),
            \'per_page\' => $containers->perPage(),
            \'current_page\' => $containers->currentPage(),
            \'total_pages\' => $containers->lastPage(),
        ];

        return response()->json([
            \'status\' => true,
            \'errNum\' => "0000",
            \'message\' => __(\'alerts.success\'),
            \'data\' => $containers->items(),
            \'pagination\' => $pagination,
        ], 200);';

$content9 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target9), preg_replace('/\r\n|\r|\n/', "\n", $replace9), preg_replace('/\r\n|\r|\n/', "\n", $content9));
file_put_contents($file9, $content9);
echo "Updated ContainerStageController\n";

// 10. Superagent BookingContainerController
$file10 = __DIR__ . '/../app/Http/Controllers/Api/Superagent/BookingContainerController.php';
$content10 = file_get_contents($file10);

$target10a = '            $paginator = $query->paginate(
                $perPage,
                [\'*\'],
                \'page\',
                $page
            );

            $data = MissionBookingResource::collection($paginator)
                ->response()
                ->getData(true);

            return $this->returnAllData($data, __(\'alerts.success\'));';

$replace10a = '            $paginator = $query->paginate(
                $perPage,
                [\'*\'],
                \'page\',
                $page
            );

            $data = MissionBookingResource::collection($paginator->items());
            $pagination = [
                \'total\' => $paginator->total(),
                \'per_page\' => $paginator->perPage(),
                \'current_page\' => $paginator->currentPage(),
                \'total_pages\' => $paginator->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);';

$target10b = '    public function specification(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'specification\');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);

            return $this->returnAllData($data, __(\'alerts.success\'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }';

$replace10b = '    public function specification(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'specification\');
            $data = SpecificationBookingResource::collection($bookings->items());
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }';

$target10c = '    public function waiting(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'waiting\');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);

            return $this->returnAllData($data, __(\'alerts.success\'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }';

$replace10c = '    public function waiting(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'waiting\');
            $data = SpecificationBookingResource::collection($bookings->items());
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }';

$target10d = '    public function loading(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'loading\');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);

            return $this->returnAllData($data, __(\'alerts.success\'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }

    public function unloading(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'unloading\');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);

            return $this->returnAllData($data, __(\'alerts.success\'));
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }';

$replace10d = '    public function loading(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'loading\');
            $data = SpecificationBookingResource::collection($bookings->items());
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }

    public function unloading(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'unloading\');
            $data = SpecificationBookingResource::collection($bookings->items());
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);
        } catch (\Exception $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }';

$content10 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target10a), preg_replace('/\r\n|\r|\n/', "\n", $replace10a), preg_replace('/\r\n|\r|\n/', "\n", $content10));
$content10 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target10b), preg_replace('/\r\n|\r|\n/', "\n", $replace10b), preg_replace('/\r\n|\r|\n/', "\n", $content10));
$content10 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target10c), preg_replace('/\r\n|\r|\n/', "\n", $replace10c), preg_replace('/\r\n|\r|\n/', "\n", $content10));
$content10 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target10d), preg_replace('/\r\n|\r|\n/', "\n", $replace10d), preg_replace('/\r\n|\r|\n/', "\n", $content10));
file_put_contents($file10, $content10);
echo "Updated BookingContainerController\n";

// 11. Api BookingController (Profile bookings)
$file11 = __DIR__ . '/../app/Http/Controllers/Api/BookingController.php';
$content11 = file_get_contents($file11);
$target11 = '    public function getCompanyBookings()
    {
        // relations read by BookingResource / ContainerResource
        $relations = [\'bookingContainers.container\', \'bookingContainers.branch\', \'last_movements\', \'employee\', \'shippingAgent\'];

        if (auth(\'employees\')->check()) {
            $employeeId = auth(\'employees\')->id();
            $bookings = Booking::with($relations)->where(\'employee_id\', $employeeId)->get();
        } else {
            $company = auth()->user();
            $bookings = $company->bookings()->with($relations)->get(); // حسب العلاقة المعرفة في الموديل
        }
    
        return $this->returnAllData(BookingResource::collection($bookings));
    }';

$replace11 = '    public function getCompanyBookings()
    {
        // relations read by BookingResource / ContainerResource
        $relations = [\'bookingContainers.container\', \'bookingContainers.branch\', \'last_movements\', \'employee\', \'shippingAgent\'];
        $perPage = (int) request()->get(\'per_page\', 20);
        $page = (int) request()->get(\'page\', 1);

        if (auth(\'employees\')->check()) {
            $employeeId = auth(\'employees\')->id();
            $query = Booking::with($relations)->where(\'employee_id\', $employeeId);
        } else {
            $company = auth()->user();
            $query = $company->bookings()->with($relations);
        }

        $bookings = $query->orderBy(\'id\', \'desc\')->paginate($perPage, [\'*\'], \'page\', $page);
        $data = BookingResource::collection($bookings->items());
        $pagination = [
            \'total\' => $bookings->total(),
            \'per_page\' => $bookings->perPage(),
            \'current_page\' => $bookings->currentPage(),
            \'total_pages\' => $bookings->lastPage(),
        ];

        return response()->json([
            \'status\' => true,
            \'errNum\' => "0000",
            \'message\' => \'\',
            \'data\' => $data,
            \'pagination\' => $pagination,
        ], 200);
    }';

$content11 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target11), preg_replace('/\r\n|\r|\n/', "\n", $replace11), preg_replace('/\r\n|\r|\n/', "\n", $content11));
file_put_contents($file11, $content11);
echo "Updated Api BookingController\n";

echo "All controllers updated successfully!\n";
