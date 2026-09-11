<?php
namespace App\Http\Controllers\Api\Desktop\Orders;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Desktop\OrderResource;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\CompanyFatoorah;
use App\Services\EInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    protected $eInvoiceService;

    public function __construct(EInvoiceService $eInvoiceService)
    {
        $this->eInvoiceService = $eInvoiceService;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logEta(string $step, array $context = [], string $level = 'info'): void
    {
        $payload = array_merge([
            'step' => $step,
            'controller' => self::class,
            'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ], $context);

        Log::channel('desktop_eta')->{$level}("[Desktop-ETA] {$step}", $payload);
    }

    public function all(Request $request)
    {
        $this->logEta('all.start', [
            'search' => $request->search,
            'limit' => $request->limit,
            'page' => $request->page,
            'query' => $request->query(),
        ]);

        try {
            $bookings = Booking::query();

            if ($request->filled('search')) {
                $bookings->whereHas('bookingContainers', function ($container) use ($request) {
                    $container->where('container_no', 'like', '%' . $request->search . '%');
                })
                    ->orWhere('employee_name', 'like', '%' . $request->search . '%')
                    ->orWhereHas('invoice', function ($invoice) use ($request) {
                        $invoice->where('invoice_number', 'like', '%' . $request->search . '%');
                    });
            }

            $bookings = OrderResource::collection(
                $bookings->orderBy('is_submitted', 'ASC')->paginate($request->limit)
            );

            $pagination = [
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'current_page' => $bookings->currentPage(),
                'total_pages' => $bookings->lastPage(),
            ];

            $this->logEta('all.success', [
                'total' => $pagination['total'],
                'current_page' => $pagination['current_page'],
                'per_page' => $pagination['per_page'],
            ]);

            return $this->returnData('data', ['orders' => $bookings, 'pagination' => $pagination], 'تم استرجاع الداتا');
        } catch (Throwable $e) {
            $this->logEta('all.error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 'error');

            throw $e;
        }
    }

    public function invoices(Request $request)
    {
        $this->logEta('invoices.start', [
            'company_id' => $request->company_id,
            'order_ids_raw' => $request->order_ids,
            'request' => $request->except(['password']),
        ]);

        try {
            $company = CompanyFatoorah::find($request->company_id);

            if (! $company) {
                $this->logEta('invoices.company_not_found', [
                    'company_id' => $request->company_id,
                ], 'warning');

                return $this->returnError(404, 'الشركة غير موجودة');
            }

            $this->logEta('invoices.company_loaded', [
                'company_id' => $company->id,
                'company_name' => $company->company_name,
                'issuer_id' => $company->issuer_id,
                'itemCode' => $company->itemCode,
                'taxpayerActivityCode' => $company->taxpayerActivityCode,
            ]);

            $ids = json_decode($request->order_ids);

            if (! is_array($ids) || empty($ids)) {
                $this->logEta('invoices.invalid_order_ids', [
                    'order_ids_raw' => $request->order_ids,
                    'decoded' => $ids,
                    'json_error' => json_last_error_msg(),
                ], 'warning');

                return $this->returnError(400, 'معرفات الطلبات غير صحيحة');
            }

            $this->logEta('invoices.order_ids_parsed', [
                'order_ids' => $ids,
                'count' => count($ids),
            ]);

            $dateTime = new \DateTime('now', new \DateTimeZone('Africa/Cairo'));
            $dateTime->setTimezone(new \DateTimeZone('UTC'));
            $formattedDateTime = $dateTime->format('Y-m-d\TH:i:s\Z');

            $invoiceOrders = [];

            foreach ($ids as $id) {
                $this->logEta('invoices.building_order', ['booking_id' => $id]);

                $book = Booking::with(['bookingContainers.branch.factory', 'company'])->findOrFail($id);
                $invoice = Invoice::where('booking_id', $id)->first();
                $invoiceLines = [];

                foreach ($book->bookingContainers as $container) {
                    $invoiceLines[] = [
                        'description' => $container->branch?->factory->name ?? 'N/A',
                        'itemType' => 'EGS',
                        'itemCode' => $company->itemCode,
                        'unitType' => 'KGM',
                        'quantity' => 1,
                        'internalCode' => 'IC0',
                        'salesTotal' => $container->price ?? 0,
                        'total' => $container->price ?? 0,
                        'valueDifference' => 0.0,
                        'totalTaxableFees' => 0,
                        'netTotal' => $container->price ?? 0,
                        'itemsDiscount' => 0,
                        'unitValue' => [
                            'currencySold' => 'EGP',
                            'amountEGP' => $container->price ?? 0,
                        ],
                        'discount' => [
                            'rate' => 0,
                            'amount' => 0,
                        ],
                        'taxableItems' => [
                            [
                                'taxType' => 'T4',
                                'amount' => 0,
                                'subType' => 'W014',
                                'rate' => 0,
                            ],
                        ],
                    ];
                }

                $totalAmount = $book->bookingContainers->sum('price');

                $invoiceData = [
                    'issuer' => [
                        'address' => [
                            'branchID' => '0',
                            'country' => 'EG',
                            'regionCity' => 'Cairo',
                            'postalCode' => '',
                            'buildingNumber' => '0',
                            'street' => '123rd Street',
                            'governate' => 'GOVERNATE',
                        ],
                        'type' => 'B',
                        'id' => $company->issuer_id,
                        'name' => $company->company_name,
                    ],
                    'receiver' => [
                        'address' => [
                            'country' => 'EG',
                            'regionCity' => 'CAIRO',
                            'postalCode' => '11435',
                            'buildingNumber' => '0',
                            'street' => 'Autostrad Road Abc',
                            'governate' => 'GOVERNATE',
                        ],
                        'type' => 'B',
                        'id' => (string) $book->company->tax_no,
                        'name' => $book->company->name,
                    ],
                    'documentType' => 'I',
                    'documentTypeVersion' => '1.0',
                    'dateTimeIssued' => $formattedDateTime,
                    'taxpayerActivityCode' => $company->taxpayerActivityCode,
                    'internalID' => $invoice?->invoice_number ?? $book->booking_number,
                    'purchaseOrderReference' => '',
                    'salesOrderReference' => '',
                    'payment' => [
                        'bankName' => '',
                        'bankAddress' => '',
                        'bankAccountNo' => '',
                        'bankAccountIBAN' => '',
                        'swiftCode' => '',
                        'terms' => '',
                    ],
                    'delivery' => [
                        'approach' => '',
                        'packaging' => '',
                        'dateValidity' => '',
                        'exportPort' => '',
                        'countryOfOrigin' => 'EG',
                        'grossWeight' => 0,
                        'netWeight' => 0,
                        'terms' => '',
                    ],
                    'invoiceLines' => $invoiceLines,
                    'totalSalesAmount' => $totalAmount,
                    'totalDiscountAmount' => 0,
                    'netAmount' => $totalAmount,
                    'taxTotals' => [
                        [
                            'taxType' => 'T1',
                            'amount' => 0,
                        ],
                    ],
                    'extraDiscountAmount' => 0,
                    'totalItemsDiscountAmount' => 0,
                    'totalAmount' => $totalAmount,
                ];

                $invoiceOrders[] = [
                    'invoiceData' => $invoiceData,
                    'reference_id' => $book->id,
                ];

                $this->logEta('invoices.order_built', [
                    'booking_id' => $book->id,
                    'booking_number' => $book->booking_number,
                    'invoice_number' => $invoice?->invoice_number,
                    'company_tax_no' => $book->company->tax_no,
                    'company_name' => $book->company->name,
                    'containers_count' => $book->bookingContainers->count(),
                    'total_amount' => $totalAmount,
                    'internalID' => $invoiceData['internalID'],
                    'dateTimeIssued' => $formattedDateTime,
                ]);
            }

            $this->logEta('invoices.success', [
                'company_id' => $request->company_id,
                'orders_count' => count($invoiceOrders),
                'reference_ids' => collect($invoiceOrders)->pluck('reference_id')->all(),
            ]);

            return $this->returnData('data', [
                'invoiceOrders' => $invoiceOrders,
                'company_id' => $request->company_id,
            ], 'تم استرجاع الداتا');
        } catch (Throwable $e) {
            $this->logEta('invoices.error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'company_id' => $request->company_id,
                'order_ids_raw' => $request->order_ids,
            ], 'error');

            throw $e;
        }
    }

    public function submitInvoices(Request $request)
    {
        $this->logEta('submit.start', [
            'company_id' => $request->company_id,
            'invoiceOrders_raw' => $request->invoiceOrders,
            'request_keys' => array_keys($request->all()),
        ]);

        try {
            $company = CompanyFatoorah::find($request->company_id);

            if (! $company) {
                $this->logEta('submit.company_not_found', [
                    'company_id' => $request->company_id,
                ], 'warning');

                return $this->returnError(404, 'الشركة غير موجودة');
            }

            $this->logEta('submit.company_loaded', [
                'company_id' => $company->id,
                'company_name' => $company->company_name,
            ]);

            $invoiceData = json_decode($request->invoiceOrders, true);

            $this->logEta('submit.invoice_data_decoded', [
                'json_error' => json_last_error_msg(),
                'is_array' => is_array($invoiceData),
                'top_level_keys' => is_array($invoiceData) ? array_keys($invoiceData) : null,
                'decoded_structure' => $invoiceData,
            ]);

            if (! $invoiceData || ! is_array($invoiceData)) {
                $this->logEta('submit.invalid_invoice_format', [
                    'invoiceOrders_raw' => $request->invoiceOrders,
                ], 'warning');

                return $this->returnError(400, 'صيغة بيانات الفواتير غير صحيحة');
            }

            // توضيح بنية البيانات — قد تكون مصفوفة من فواتير أو عنصر واحد
            $invoicesToProcess = $this->normalizeInvoicePayload($invoiceData);

            $this->logEta('submit.invoices_normalized', [
                'count' => count($invoicesToProcess),
                'reference_ids' => collect($invoicesToProcess)->pluck('reference_id')->filter()->values()->all(),
            ]);

            if (empty($invoicesToProcess)) {
                $this->logEta('submit.no_invoices_to_process', [
                    'invoiceData' => $invoiceData,
                ], 'warning');

                return $this->returnError(400, 'لا توجد فواتير للإرسال');
            }

            foreach ($invoicesToProcess as $index => $invoiceItem) {
                $referenceId = $invoiceItem['reference_id'] ?? null;
                $invoicePayload = $invoiceItem['invoiceData'] ?? $invoiceItem;

                $this->logEta('submit.processing_invoice', [
                    'index' => $index,
                    'reference_id' => $referenceId,
                    'internalID' => $invoicePayload['internalID'] ?? null,
                    'payload_keys' => is_array($invoicePayload) ? array_keys($invoicePayload) : null,
                ]);

                if (! $referenceId) {
                    $this->logEta('submit.missing_reference_id', [
                        'index' => $index,
                        'invoiceItem' => $invoiceItem,
                    ], 'warning');

                    continue;
                }

                $accessToken = $this->eInvoiceService->getAccessToken(
                    $company->ETA_CLIENT_ID,
                    $company->ETA_CLIENT_SECRET
                );

                if (! $accessToken) {
                    $this->logEta('submit.access_token_failed', [
                        'reference_id' => $referenceId,
                        'company_id' => $company->id,
                    ], 'error');

                    Booking::where('id', $referenceId)->update([
                        'invoice_status' => 'Failed',
                        'invoice_errors' => 'فشل في الحصول على Access Token',
                    ]);

                    return $this->returnError(500, 'فشل في الحصول على Access Token');
                }

                $this->logEta('submit.access_token_ok', [
                    'reference_id' => $referenceId,
                    'token_length' => strlen($accessToken),
                ]);

                $this->logEta('submit.calling_eta_submit', [
                    'reference_id' => $referenceId,
                    'internalID' => $invoicePayload['internalID'] ?? null,
                    'invoice_payload' => $invoicePayload,
                ]);

                $response = $this->eInvoiceService->submitInvoice($invoicePayload, $accessToken);

                $this->logEta('submit.eta_submit_response', [
                    'reference_id' => $referenceId,
                    'response' => $response,
                ]);

                if (! $response || ($response['status'] ?? false) === false) {
                    $errorMessage = $response['message'] ?? 'خطأ غير معروف';

                    $this->logEta('submit.eta_submit_failed', [
                        'reference_id' => $referenceId,
                        'message' => $errorMessage,
                        'response' => $response,
                    ], 'error');

                    Booking::where('id', $referenceId)->update([
                        'invoice_status' => 'Failed',
                        'invoice_errors' => $errorMessage,
                    ]);

                    return $this->returnError(400, $errorMessage);
                }

                if (isset($response['data']['submissionId'], $response['data']['acceptedDocuments'][0]['uuid'])) {
                    $submissionId = $response['data']['submissionId'];
                    $uuid = $response['data']['acceptedDocuments'][0]['uuid'];

                    $this->logEta('submit.eta_accepted', [
                        'reference_id' => $referenceId,
                        'submission_id' => $submissionId,
                        'uuid' => $uuid,
                    ]);

                    $this->logEta('submit.waiting_for_validation', [
                        'reference_id' => $referenceId,
                        'uuid' => $uuid,
                        'sleep_seconds' => 10,
                    ]);

                    sleep(10);

                    $invoiceDetails = $this->eInvoiceService->getInvoiceDetails($uuid, $accessToken);
                    $invoiceStatus = $invoiceDetails['data']['status'] ?? 'Unknown';

                    $this->logEta('submit.invoice_details_fetched', [
                        'reference_id' => $referenceId,
                        'uuid' => $uuid,
                        'status' => $invoiceStatus,
                        'details' => $invoiceDetails,
                    ]);

                    if ($invoiceStatus === 'Valid') {
                        Booking::where('id', $referenceId)->update([
                            'submission_id' => $submissionId,
                            'invoice_uuid' => $uuid,
                            'invoice_status' => 'Valid',
                            'signature_date' => now(),
                            'signature_company' => $company->name,
                            'signature_company_id' => $company->id,
                            'is_submitted' => 1,
                        ]);

                        $this->logEta('submit.invoice_valid', [
                            'reference_id' => $referenceId,
                            'uuid' => $uuid,
                            'submission_id' => $submissionId,
                        ]);

                        return response()->json([
                            'status' => true,
                            'message' => 'تم توقيع الفواتير بنجاح',
                        ]);
                    }

                    $reasons = isset($invoiceDetails['data']['validationResults'])
                        ? collect($invoiceDetails['data']['validationResults'])->pluck('message')->implode(', ')
                        : 'سبب غير معروف';

                    Booking::where('id', $referenceId)->update([
                        'submission_id' => $submissionId,
                        'invoice_uuid' => $uuid,
                        'invoice_status' => 'Invalid',
                        'invoice_errors' => $reasons,
                    ]);

                    $this->logEta('submit.invoice_invalid', [
                        'reference_id' => $referenceId,
                        'uuid' => $uuid,
                        'status' => $invoiceStatus,
                        'reasons' => $reasons,
                        'validationResults' => $invoiceDetails['data']['validationResults'] ?? null,
                    ], 'warning');

                    return $this->returnError(400, $reasons);
                }

                $unexpectedError = 'استجابة غير متوقعة من ETA: ' . json_encode($response);

                Booking::where('id', $referenceId)->update([
                    'invoice_status' => 'Failed',
                    'invoice_errors' => $unexpectedError,
                ]);

                $this->logEta('submit.unexpected_eta_response', [
                    'reference_id' => $referenceId,
                    'response' => $response,
                ], 'error');

                return $this->returnError(500, 'استجابة غير متوقعة من ETA');
            }

            $this->logEta('submit.completed_background', [
                'company_id' => $company->id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال الفواتير، وجاري معالجتها في الخلفية.',
            ]);
        } catch (Throwable $e) {
            $this->logEta('submit.error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'company_id' => $request->company_id,
                'invoiceOrders_raw' => $request->invoiceOrders,
            ], 'error');

            throw $e;
        }
    }

    public function updateStatus(Request $request)
    {
        $this->logEta('updateStatus.start', [
            'company_id' => $request->company_id,
            'invoice_uuid' => $request->invoice_uuid,
            'status' => $request->status,
            'reason' => $request->reason,
            'request' => $request->except(['password']),
        ]);

        try {
            $company = CompanyFatoorah::find($request->company_id);

            if (! $company) {
                $this->logEta('updateStatus.company_not_found', [
                    'company_id' => $request->company_id,
                ], 'warning');

                return $this->returnError(404, 'الشركة غير موجودة');
            }

            $accessToken = $this->eInvoiceService->getAccessToken(
                $company->ETA_CLIENT_ID,
                $company->ETA_CLIENT_SECRET
            );

            if (! $accessToken) {
                $this->logEta('updateStatus.access_token_failed', [
                    'company_id' => $company->id,
                    'invoice_uuid' => $request->invoice_uuid,
                ], 'error');

                return $this->returnError(500, 'فشل في الحصول على Access Token');
            }

            $this->logEta('updateStatus.calling_eta', [
                'invoice_uuid' => $request->invoice_uuid,
                'status' => $request->status,
                'reason' => $request->reason,
            ]);

            $response = $this->eInvoiceService->updateInvoiceState(
                $request->invoice_uuid,
                $request->status,
                $request->reason,
                $accessToken
            );

            $this->logEta('updateStatus.eta_response', [
                'invoice_uuid' => $request->invoice_uuid,
                'response' => $response,
            ]);

            if (isset($response['status']) && $response['status'] === false) {
                Booking::where('invoice_uuid', $request->invoice_uuid)->update([
                    'invoice_status' => 'Invalid',
                    'is_submitted' => 0,
                    'invoice_errors' => json_encode($response),
                ]);

                $this->logEta('updateStatus.failed', [
                    'invoice_uuid' => $request->invoice_uuid,
                    'message' => $response['message'] ?? null,
                    'response' => $response,
                ], 'error');

                return $this->returnError(400, $response['message']);
            }

            Booking::where('invoice_uuid', $request->invoice_uuid)->update([
                'invoice_status' => $request->status,
                'is_submitted' => 0,
            ]);

            $this->logEta('updateStatus.success', [
                'invoice_uuid' => $request->invoice_uuid,
                'new_status' => $request->status,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث حالة الفاتورة بنجاح',
                'data' => $response,
            ]);
        } catch (Throwable $e) {
            $this->logEta('updateStatus.error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'invoice_uuid' => $request->invoice_uuid,
            ], 'error');

            throw $e;
        }
    }

    /**
     * توحيد بنية invoiceOrders القادمة من Desktop (مصفوفة أو عنصر واحد).
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeInvoicePayload(array $invoiceData): array
    {
        // [{ invoiceData, reference_id }, ...]
        if (isset($invoiceData[0]) && is_array($invoiceData[0]) && isset($invoiceData[0]['reference_id'])) {
            return $invoiceData;
        }

        // { invoiceData, reference_id }
        if (isset($invoiceData['reference_id'])) {
            return [$invoiceData];
        }

        // invoiceData[0] = { invoiceData, reference_id } — الشكل القديم
        if (isset($invoiceData[0]) && is_array($invoiceData[0]) && isset($invoiceData[0]['invoiceData'])) {
            return [$invoiceData[0]];
        }

        $this->logEta('submit.unrecognized_payload_shape', [
            'invoiceData' => $invoiceData,
        ], 'warning');

        return [];
    }
}
