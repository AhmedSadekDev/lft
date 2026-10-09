<?php

// Fix ContainerStageController & BookingContainerController to preserve response envelope

// 1. ContainerStageController
$file1 = __DIR__ . '/../app/Http/Controllers/Api/Superagent/ContainerStageController.php';
$content1 = file_get_contents($file1);
$target1 = '        $pagination = [
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

$replace1 = '        $pagination = [
            \'total\' => $containers->total(),
            \'per_page\' => $containers->perPage(),
            \'current_page\' => $containers->currentPage(),
            \'total_pages\' => $containers->lastPage(),
        ];

        return response()->json([
            \'status\' => true,
            \'errNum\' => "0000",
            \'message\' => __(\'alerts.success\'),
            \'data\' => [
                \'data\' => $containers->items(),
                \'current_page\' => $containers->currentPage(),
                \'per_page\' => $containers->perPage(),
                \'total\' => $containers->total(),
                \'last_page\' => $containers->lastPage(),
                \'pagination\' => $pagination,
            ],
            \'pagination\' => $pagination,
        ], 200);';

$content1 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target1), preg_replace('/\r\n|\r|\n/', "\n", $replace1), preg_replace('/\r\n|\r|\n/', "\n", $content1));
file_put_contents($file1, $content1);
echo "Updated ContainerStageController\n";

// 2. BookingContainerController
$file2 = __DIR__ . '/../app/Http/Controllers/Api/Superagent/BookingContainerController.php';
$content2 = file_get_contents($file2);

// Replace paginateBookingsForStage / methods in BookingContainerController
$target2 = '    public function all(Request $request)
    {
        try {
            $perPage = (int) $request->get(\'per_page\', default: 100);
            $page    = (int) $request->get(\'page\', 1);
            $stageType = $request->get(\'stage_type\');

            $condition = $this->allStageContainerConstraints($stageType);

            $query = Booking::query()
                ->withoutInvoice()
                ->whereHas(\'bookingContainers\', $condition)
                ->with([
                    \'bookingContainers\' => function ($cQuery) use ($condition) {
                        $condition($cQuery);
                        $cQuery->select(\'*\')
                            ->selectRaw("
                                CASE
                                    WHEN superagent_specification_approved = 1 AND superagent_loading_approved = 1 AND superagent_unloading_approved = 0 THEN \'unloading\'
                                    WHEN superagent_specification_approved = 1 AND is_in_loading = 1 AND superagent_loading_approved = 0 AND superagent_unloading_approved = 0 THEN \'loading\'
                                    WHEN superagent_specification_approved = 1 AND is_in_loading = 0 AND superagent_loading_approved = 0 AND superagent_unloading_approved = 0 THEN \'waiting\'
                                    WHEN status = 0 OR (status = 1 AND superagent_specification_approved = 0) THEN \'specification\'
                                    ELSE NULL
                                END AS stage_type
                            ")
                            ->with([
                                \'booking.company\',
                                \'booking.factory\',
                                \'booking.yard\',
                                \'branch.factory\',
                                \'container\',
                                \'notes\',
                                \'agents\',
                            ])
                            ->orderByDesc(\'id\');
                    }
                ])
                ->orderByDesc(\'id\');

            $paginator = $query->paginate(
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
            ], 200);

        } catch (\Throwable $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }';

$replace2 = '    public function all(Request $request)
    {
        try {
            $perPage = (int) $request->get(\'per_page\', default: 100);
            $page    = (int) $request->get(\'page\', 1);
            $stageType = $request->get(\'stage_type\');

            $condition = $this->allStageContainerConstraints($stageType);

            $query = Booking::query()
                ->withoutInvoice()
                ->whereHas(\'bookingContainers\', $condition)
                ->with([
                    \'bookingContainers\' => function ($cQuery) use ($condition) {
                        $condition($cQuery);
                        $cQuery->select(\'*\')
                            ->selectRaw("
                                CASE
                                    WHEN superagent_specification_approved = 1 AND superagent_loading_approved = 1 AND superagent_unloading_approved = 0 THEN \'unloading\'
                                    WHEN superagent_specification_approved = 1 AND is_in_loading = 1 AND superagent_loading_approved = 0 AND superagent_unloading_approved = 0 THEN \'loading\'
                                    WHEN superagent_specification_approved = 1 AND is_in_loading = 0 AND superagent_loading_approved = 0 AND superagent_unloading_approved = 0 THEN \'waiting\'
                                    WHEN status = 0 OR (status = 1 AND superagent_specification_approved = 0) THEN \'specification\'
                                    ELSE NULL
                                END AS stage_type
                            ")
                            ->with([
                                \'booking.company\',
                                \'booking.factory\',
                                \'booking.yard\',
                                \'branch.factory\',
                                \'container\',
                                \'notes\',
                                \'agents\',
                            ])
                            ->orderByDesc(\'id\');
                    }
                ])
                ->orderByDesc(\'id\');

            $paginator = $query->paginate(
                $perPage,
                [\'*\'],
                \'page\',
                $page
            );

            $data = MissionBookingResource::collection($paginator)->response()->getData(true);
            $pagination = [
                \'total\' => $paginator->total(),
                \'per_page\' => $paginator->perPage(),
                \'current_page\' => $paginator->currentPage(),
                \'total_pages\' => $paginator->lastPage(),
            ];
            $data[\'pagination\'] = $pagination;

            return response()->json([
                \'status\' => true,
                \'errNum\' => "0000",
                \'message\' => __(\'alerts.success\'),
                \'data\' => $data,
                \'pagination\' => $pagination,
            ], 200);

        } catch (\Throwable $ex) {
            return $this->returnError($ex instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $ex->getStatusCode() : 500, $ex->getMessage());
        }
    }';

$content2 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target2), preg_replace('/\r\n|\r|\n/', "\n", $replace2), preg_replace('/\r\n|\r|\n/', "\n", $content2));

$target2b = '    public function specification(Request $request)
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

$replace2b = '    public function specification(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'specification\');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];
            $data[\'pagination\'] = $pagination;

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

$content2 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target2b), preg_replace('/\r\n|\r|\n/', "\n", $replace2b), preg_replace('/\r\n|\r|\n/', "\n", $content2));

$target2c = '    public function waiting(Request $request)
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

$replace2c = '    public function waiting(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'waiting\');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];
            $data[\'pagination\'] = $pagination;

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

$content2 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target2c), preg_replace('/\r\n|\r|\n/', "\n", $replace2c), preg_replace('/\r\n|\r|\n/', "\n", $content2));

$target2d = '    public function loading(Request $request)
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

$replace2d = '    public function loading(Request $request)
    {
        try {
            $bookings = $this->paginateBookingsForStage($request, \'loading\');
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];
            $data[\'pagination\'] = $pagination;

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
            $data = SpecificationBookingResource::collection($bookings)->response()->getData(true);
            $pagination = [
                \'total\' => $bookings->total(),
                \'per_page\' => $bookings->perPage(),
                \'current_page\' => $bookings->currentPage(),
                \'total_pages\' => $bookings->lastPage(),
            ];
            $data[\'pagination\'] = $pagination;

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

$content2 = str_replace(preg_replace('/\r\n|\r|\n/', "\n", $target2d), preg_replace('/\r\n|\r|\n/', "\n", $replace2d), preg_replace('/\r\n|\r|\n/', "\n", $content2));
file_put_contents($file2, $content2);
echo "Updated BookingContainerController\n";
