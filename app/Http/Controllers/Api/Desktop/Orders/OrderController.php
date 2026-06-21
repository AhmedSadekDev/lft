<?php
namespace App\Http\Controllers\Api\Desktop\Orders;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Desktop\OrderResource;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\CompanyFatoorah;
use App\Services\EInvoiceService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $eInvoiceService;

    public function __construct(EInvoiceService $eInvoiceService)
    {
        $this->eInvoiceService = $eInvoiceService;
    }
    public function all(Request $request)
    {
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

        $bookings   = OrderResource::collection($bookings->orderBy('is_submitted', 'ASC')->paginate($request->limit));
        $pagination = [
            'total'        => $bookings->total(),
            'per_page'     => $bookings->perPage(),
            'current_page' => $bookings->currentPage(),
            'total_pages'  => $bookings->lastPage(),
        ];
        return $this->returnData("data", ["orders" => $bookings, 'pagination' => $pagination], 'تم استرجاع الداتا');
    }
    public function invoices(Request $request)
    {
        $company  = CompanyFatoorah::find($request->company_id);
        $dateTime = new \DateTime('now', new \DateTimeZone('Africa/Cairo')); // Cairo timezone
        $dateTime->setTimezone(new \DateTimeZone('UTC'));                    // Convert to UTC
        $formattedDateTime = $dateTime->format('Y-m-d\TH:i:s\Z');            // Format as YYYY-MM-DDTHH:MM:SSZ
                                                                             // Step 4: Prepare Invoice Lines
        $ids           = json_decode($request->order_ids);
        $incoiceOrders = [];
        foreach ($ids as $id) {
            $book         = Booking::findOrFail($id);
            $invoiceLines = [];
            $invoice = Invoice::where('booking_id', $id)->first();
            foreach ($book->bookingContainers as $container) {
                $invoiceLines[] = [
                    "description"      => $container->branch?->factory->name ?? 'N/A',
                    "itemType"         => "EGS",
                    "itemCode"         => $company->itemCode,
                    "unitType"         => "KGM",
                    "quantity"         => 1,
                    "internalCode"     => "IC0",
                    "salesTotal"       => $container->price ?? 0,
                    "total"            => $container->price ?? 0,
                    "valueDifference"  => 0.0,
                    "totalTaxableFees" => 0,
                    "netTotal"         => $container->price ?? 0,
                    "itemsDiscount"    => 0,
                    "unitValue"        => [
                        "currencySold" => "EGP",
                        "amountEGP"    => $container->price ?? 0,
                    ],
                    "discount"         => [
                        "rate"   => 0,
                        "amount" => 0,
                    ],
                    "taxableItems"     => [
                        [
                            "taxType" => "T4",
                            "amount"  => 0,
                            "subType" => "W014",
                            "rate"    => 0,
                        ],
                    ],
                ];
            }
            $invoiceData = [
                "issuer"                   => [
                    "address" => [
                        "branchID"       => "0",
                        "country"        => "EG",
                        "regionCity"     => "Cairo",
                        "postalCode"     => "",
                        "buildingNumber" => "0",
                        "street"         => "123rd Street",
                        "governate"      => "GOVERNATE",
                    ],
                    "type"    => "B",
                    "id"      => $company->issuer_id,
                    "name"    => $company->company_name
                ],
                "receiver"                 => [
                    "address" => [
                        "country"        => "EG",
                        "regionCity"     => "CAIRO",
                        "postalCode"     => "11435",
                        "buildingNumber" => "0",
                        "street"         => "Autostrad Road Abc",
                        "governate"      => "GOVERNATE",
                    ],
                    "type"    => "B",
                    "id"      => (string) $book->company->tax_no, // get from company $book->company->tax_no
                    "name"    => $book->company->name, // get from company $book->company->name
                ],
                "documentType"             => "I",
                "documentTypeVersion"      => "1.0",
                "dateTimeIssued"           => $formattedDateTime,
                "taxpayerActivityCode"     => $company->taxpayerActivityCode,
                "internalID"               => $invoice?->invoice_number ?? $book->booking_number,
                "purchaseOrderReference"   => "",
                "salesOrderReference"      => "",
                "payment"                  => [
                    "bankName"        => "",
                    "bankAddress"     => "",
                    "bankAccountNo"   => "",
                    "bankAccountIBAN" => "",
                    "swiftCode"       => "",
                    "terms"           => "",
                ],
                "delivery"                 => [
                    "approach"        => "",
                    "packaging"       => "",
                    "dateValidity"    => "",
                    "exportPort"      => "",
                    "countryOfOrigin" => "EG",
                    "grossWeight"     => 0,
                    "netWeight"       => 0,
                    "terms"           => "",
                ],
                "invoiceLines"             => $invoiceLines,
                "totalSalesAmount"         => $book->bookingContainers->sum('price'),
                "totalDiscountAmount"      => 0,
                "netAmount"                => $book->bookingContainers->sum('price'),
                "taxTotals"                => [
                    [
                        "taxType" => "T1",
                        "amount"  => 0,
                    ],
                ],
                "extraDiscountAmount"      => 0,
                "totalItemsDiscountAmount" => 0,
                "totalAmount"              => $book->bookingContainers->sum('price'),
            ];
            // هنا التعديل المهم
            $invoiceOrders[] = [
                "invoiceData"  => $invoiceData,
                'reference_id' => $book->id,
            ];
        }

        return $this->returnData("data", ["invoiceOrders" => $invoiceOrders, 'company_id' => $request->company_id], 'تم استرجاع الداتا');

    }
    public function submitInvoices(Request $request)
    {
        INFO($request->all());
        $company = CompanyFatoorah::find($request->company_id);

        // Check if company exists
        if (! $company) {
            return $this->returnError(404, "الشركة غير موجودة");
        }
        // Decode invoice data
        $invoiceData = json_decode($request->invoiceOrders, true);
        // dd($invoiceData);

        // Validate invoice data format
        if (! $invoiceData || ! is_array($invoiceData)) {
            return $this->returnError(400, "صيغة بيانات الفواتير غير صحيحة");
        }

        // Loop through each invoice
        foreach ($invoiceData[0] as $invoice) {
            $referenceId = $invoiceData[0]['reference_id'];

            // Get access token
            $accessToken = $this->eInvoiceService->getAccessToken(
                $company->ETA_CLIENT_ID,
                $company->ETA_CLIENT_SECRET
            );
            if (! $accessToken) {
                // Update invoice status in case of failure to get access token
                Booking::where('id', $referenceId)->update([
                    'invoice_status' => 'Failed',
                    'invoice_errors' => 'فشل في الحصول على Access Token',
                ]);
                return $this->returnError(500, 'فشل في الحصول على Access Token');
            }

            // Submit invoice to ETA
            $response = $this->eInvoiceService->submitInvoice($invoice, $accessToken);
            // Handle response failure
            if (! $response || $response['status'] === false) {
                Booking::where('id', $referenceId)->update([
                    'invoice_status' => 'Failed',
                    'invoice_errors' => $response['message'] ?? 'خطأ غير معروف',
                ]);
                return $this->returnError(400, $response['message'] ?? 'خطأ غير معروف');
            }
            INFO($response);
            // Handle successful submission
            if (isset($response['data']['submissionId'], $response['data']['acceptedDocuments'][0]['uuid'])) {
                $submissionId = $response['data']['submissionId'];
                $uuid         = $response['data']['acceptedDocuments'][0]['uuid'];

                           // Wait for the status to update
                sleep(10); // Reduced wait time to 15 seconds

                // Get invoice details after submission
                $invoiceDetails = $this->eInvoiceService->getInvoiceDetails($uuid, $accessToken);
                // Check invoice status
                $invoiceStatus = $invoiceDetails['data']['status'] ?? 'Unknown';

                // If invoice is valid, update status to Valid, otherwise to Invalid
                if ($invoiceStatus === 'Valid') {
                    // $shareResponse = $this->eInvoiceService->getDocumentShareToken($uuid, $accessToken);
                    // INFO($shareResponse);
                    // $invoiceLink = $shareResponse['status'] ? $shareResponse['data']['share_url'] : null;
                    Booking::where('id', $referenceId)->update([
                        'submission_id'  => $submissionId,
                        'invoice_uuid'   => $uuid,
                        'invoice_status' => 'Valid',
                        'signature_date' => now(),
                        'signature_company' => $company->name,
                        'signature_company_id' => $company->id,
                        'is_submitted'   => 1, // Mark as submitted successfully
                        // 'share_url'      => "https://admin.leaderfortrans.com/previewInvoicePDF/$company->id/$uuid",
                    ]);

                    return response()->json([
                        'status'  => true,
                        'message' => 'تم توقيع الفواتير بنجاح',
                    ]);
                } else {
                    $reasons = isset($invoiceDetails['data']['validationResults'])
                    ? collect($invoiceDetails['data']['validationResults'])->pluck('message')->implode(', ')
                    : "سبب غير معروف";

                    Booking::where('id', $referenceId)->update([
                        'submission_id'  => $submissionId,
                        'invoice_uuid'   => $uuid,
                        'invoice_status' => 'Invalid',
                        'invoice_errors' => $reasons,
                    ]);
                }
            } else {
                // Unexpected response from ETA API
                Booking::where('id', $referenceId)->update([
                    'invoice_status' => 'Failed',
                    'invoice_errors' => 'استجابة غير متوقعة من ETA: ' . json_encode($response),
                ]);
                return $this->returnError(500, 'استجابة غير متوقعة من ETA');
            }
        }

        // Return success response
        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال الفواتير، وجاري معالجتها في الخلفية.',
        ]);

    }
    public function updateStatus(Request $request)
    {

        // احضر بيانات الشركة
        $company = CompanyFatoorah::find($request->company_id);

        if (! $company) {
            return $this->returnError(404, 'الشركة غير موجودة');
        }

        // الحصول على التوكن
        $accessToken = $this->eInvoiceService->getAccessToken(
            $company->ETA_CLIENT_ID,
            $company->ETA_CLIENT_SECRET
        );

        if (! $accessToken) {
            return $this->returnError(500, 'فشل في الحصول على Access Token');
        }

        // استدعاء دالة تغيير الحالة
        $response = $this->eInvoiceService->updateInvoiceState(
            $request->invoice_uuid,
            $request->status,
            $request->reason,
            $accessToken
        );

        if (isset($response['status']) && $response['status'] === false) {
            // return $response;
            Booking::where('invoice_uuid', $request->invoice_uuid)->update([
                'invoice_status' => "Invalid",
                'is_submitted'   => 0, // Mark as submitted successfully
                'invoice_errors' => json_encode($response)
            ]);
            return $this->returnError(400, $response['message']);
        }
        Booking::where('invoice_uuid', $request->invoice_uuid)->update([
            'invoice_status' => $request->status,
            'is_submitted'   => 0, // Mark as submitted successfully
        ]);
        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث حالة الفاتورة بنجاح',
            'data'    => $response,
        ]);
    }

}
