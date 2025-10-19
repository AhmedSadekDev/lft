<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use Mpdf\Mpdf;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Booking;
use App\Services\EInvoiceService;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\RequestException;
use App\Models\CompanyFatoorah;

class InvoicesController extends Controller
{
    use ResponseTrait;
    protected $eInvoiceService;
    
    public function __construct(EInvoiceService $eInvoiceService)
    {
        $this->eInvoiceService = $eInvoiceService;
    }
        
    public function index(Request $request, $id = null)
    {
        $company = null;
        if ($id || $request->filled('id')) {
            $company = Company::findOrFail($request->id ?? $id);
        }

        $bookings = Booking::query();

        if ($request->filled('date_from')) {
            $bookings->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $bookings->whereDate('created_at', '<=', $request->date_to);
        }

        if ($id || $request->filled('id')) {
            $bookings->where('company_id', $request->id ?? $id);
        }
        
        // Initialize an empty collection to hold all payments
        $allPayments = collect();
        
        // Loop through each booking for the company
        foreach ($company->bookings as $booking) {
            // Retrieve the invoice associated with the booking
            $invoice = $booking->invoice;
        
            // Retrieve and merge the invoice payments into the collection
            if ($invoice) {
                $payments = $invoice->invoicePayments;
                $allPayments = $allPayments->merge($payments);
            }
        }
        
        // $allPayments now contains all invoice payments for the company's bookings


        $bookings = $bookings->whereHas('invoice')->get();
        $companies = Company::where('taxed', 1)->get();
        $banks = Bank::all();
        
        

        return view('admin.invoices.index', compact('bookings', 'companies', 'banks', 'allPayments'));
    }



    public function export($id = null)
    {
        $company = Company::findOrFail($id);

        $bookings = Booking::all();

        return view('admin.invoices.pdf', compact('bookings'));
    }


    public function downloadPDF(Request $request, $id = null)
    {
        $ids = explode(',', $request->ids);
        $bookings = Booking::whereIn('id', $ids)->get();
        $html = view('admin.invoices.pdf', compact('bookings'))->render();

        // Configure Mpdf for RTL support and include necessary fonts
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 6,
            'margin_footer' => 6
        ]);


        $mpdf->WriteHTML($html);

        return $mpdf->Output('invoice.pdf', 'D');
    }
    
    public function submitInvoice(Request $request, $id)
    {
        try{
        // Step 1: Find the booking
        $book = Booking::findOrFail($id);

        // Step 2: Get Access Token
        $accessToken = $this->eInvoiceService->getAccessToken();
        // dd($accessToken);

        if (!$accessToken) {
            return back()->with('error', 'Failed to get access token');
        }

        // Step 3: Prepare Invoice Data
        $dateTime = new \DateTime('now', new \DateTimeZone('Africa/Cairo')); // Cairo timezone
        $dateTime->setTimezone(new \DateTimeZone('UTC')); // Convert to UTC
        $formattedDateTime = $dateTime->format('Y-m-d\TH:i:s\Z'); // Format as YYYY-MM-DDTHH:MM:SSZ

        // Build invoice lines dynamically
        $invoiceLines = [];
        foreach ($book->bookingContainers as $container) {
        //dd($container->price);
        $invoiceLines[] = [
            "description" => $container->container?->full_name ?? 'N/A',
            "itemType" => "EGS", // تم تصحيح هذا السطر
            "itemCode" => "EG-765717972-101",
            "unitType" => "KGM",
            "quantity" => 1,
            "internalCode" => "IC0",
            "salesTotal" => $container->price ?? 0,
            "total" => $container->price ?? 0,
            "valueDifference" => 0.0,
            "totalTaxableFees" => 0,
            "netTotal" => $container->price ?? 0,
            "itemsDiscount" => 0,
            "unitValue" => [
            "currencySold" => "EGP",
            "amountEGP" => $container->price ?? 0
        ],
            "discount" => [
            "rate" => 0,
            "amount" => 0
        ],
            "taxableItems" => [
            [
                "taxType" => "T4",
                "amount" => 0,
                "subType" => "W014",
                "rate" => 0
            ]
        ]
        ];
    }
    // dd($invoiceLines);
    

        // Prepare the invoice data
        $invoiceData = [
        [
            "issuer" => [
                "address" => [
                    "branchID" => "0",
                    "country" => "EG",
                    "governate" => "Domyat",
                    "regionCity" => "Salt Shore",
                    "street" => "Port Said Road",
                    "buildingNumber" => "1",
                    "postalCode" => "1115",
                    "floor" => "3",
                    "room" => "2",
                    "landmark" => "",
                    "additionalInformation" => "Next to Al-Hanawi Exhibition"
                ],
                "type" => "B",
                "id" => "765717972",
                "name" => "منتصر فاروق عوض عمر و شريكه"
            ],
            "receiver" => [
                "address" => [
                    "country" => "EG",
                    "governate" => "Cairo",
                    "regionCity" => "Nasr City",
                    "street" => "123 Main St",
                    "buildingNumber" => "Bldg. 1",
                    "postalCode" => "12345",
                    "floor" => "1",
                    "room" => "101",
                    "landmark" => "Near City Mall",
                    "additionalInformation" => "Office 101"
                ],
                "type" => "B",
                "id" => "288048687" ,
                "name" => "name"
            ],
            "signatures" => [
                [
                    "signatureType" => "I",
                    "value" => "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"
                ]            
            ], 

            "documentType" => "I",
            "documentTypeVersion" => "1.0",
            "dateTimeIssued" => $formattedDateTime,
            "taxpayerActivityCode" => "5229",
            "internalID" => "INV-".$book->booking_number,
            "purchaseOrderReference" => "D220",
            "purchaseOrderDescription" => "order description",
            "salesOrderReference" => "D220",
            "salesOrderDescription" => "Sales Order description",
            "proformaInvoiceNumber" => "",
            "payment" => (object)[], // Empty object
            "delivery" => (object)[], // Empty object
            "invoiceLines" => $invoiceLines, // Use dynamically built invoice lines
            "totalDiscountAmount" => 0,
            "totalSalesAmount" => $book->bookingContainers->sum('price'),
            "netAmount" => $book->bookingContainers->sum('price'),
            "taxTotals" => [
                [
                    "taxType" => "T4",
                    "amount" => 0
                ]
            ],
            "totalAmount" => $book->bookingContainers->sum('price'),
            "extraDiscountAmount" => 0,
            "totalItemsDiscountAmount" => 0,
        ]
    ];
        // dd($invoiceData);
        // Step 4: Submit Invoice
        $response = $this->eInvoiceService->submitInvoice($invoiceData, $accessToken);
        
    
        if (!$response) {
            return back()->with('error', 'Failed to submit invoice');
    }
    $responseData = $response->getData(true);
    dd($responseData);// Convert JsonResponse to an array
    
    // Ensure the response has the expected structure
    if ($responseData['errNum'] == 500 || $responseData['status'] == false) {
        $errorMessage = $responseData['message'];

        // استخدام Regular Expression لاستخراج عدد الثواني
        preg_match('/Try to submit payload after (\d+) se/', $errorMessage, $matches);
    
        // إذا تم العثور على الرقم، ضعه في الرسالة
        if (!empty($matches[1])) {
            $seconds = (int) $matches[1]; // تحويل النص إلى رقم صحيح
            $minutes = floor($seconds / 60); // حساب الدقائق
            $remainingSeconds = $seconds % 60; // حساب الثواني المتبقية

             if ($minutes > 0) {
                $errorMessage = "يجب الانتظار {$minutes} دقيقة و {$remainingSeconds} ثانية قبل إعادة المحاولة.";
            } else {
                $errorMessage = "يجب الانتظار {$remainingSeconds} ثانية قبل إعادة المحاولة.";
            }
        }
    
        return back()->with('error', $errorMessage);
    }
        dd($responseData);
        // Access the submissionId
        $submissionId = $responseData['data']['original']['data']['submissionId'];
        // Access the UUID of the first accepted document
        $uuid = $responseData['data']['original']['data']['acceptedDocuments'][0]['uuid'];
        $book->submission_id = $submissionId;
        $book->invoice_uuid = $uuid;
        $book->save();

        return back()
            ->with('success', 'تم رفع الفاتورة بنجاح');
        }
        catch (RequestException $e) {
            return back()->with('error', 'حدث خطأ اثناء الرفع الرجاء المحاولة بعد 10 دقائق');
        }
    }
    
    public function cancel_invoice($booking_id, Request $request)
    {
        $booking = Booking::findOrFail($booking_id);
        if (!$booking)
        {
            return $this->returnError(404, "This invoice not found");
        }
        // Step 2: Get Access Token
        $accessToken = $this->eInvoiceService->getAccessToken();
        
        $invocie_uuid = $booking->invoice_uuid;
        //dd($invocie_uuid);
        $response = $this->eInvoiceService->cancelInvoice($invocie_uuid, "some reason", $accessToken);
        return $response;
        // $response = json_decode($response, true);
        // dd($response);
    }
    
    public function reject_invoice($booking_id, Request $request)
    {
        $booking = Booking::findOrFail($booking_id);
        if (!$booking)
        {
            return $this->returnError(404, "This invoice not found");
        }
        // Step 2: Get Access Token
        $accessToken = $this->eInvoiceService->getAccessToken();
        
        $invocie_uuid = $booking->invoice_uuid;
        $response = $this->eInvoiceService->rejectInvoice($invocie_uuid, $request->reject_reason, $accessToken);
        return $response;
    }
    
    public function get_invoice($booking_id)
    {
        $booking = Booking::findOrFail($booking_id);
        if (!$booking)
        {
            return $this->returnError(404, "This invoice not found");
        }
        // Step 2: Get Access Token
        $accessToken = $this->eInvoiceService->getAccessToken();
        
        $invocie_uuid = $booking->invoice_uuid;
        $response = $this->eInvoiceService->getInvoice($invocie_uuid, $accessToken);
        return $response;
    }
    
      public function previewInvoicePDF(Request $request, $companyId = null , $invoiceUUID = null){
          
     //     dd($companyId  , $invoiceUUID );
        $company = CompanyFatoorah::findOrFail($companyId);
        $accessToken = $this->eInvoiceService->getAccessToken(
            $company->ETA_CLIENT_ID,
            $company->ETA_CLIENT_SECRET
        );
        $content = $this->eInvoiceService->getInvoicePDF($invoiceUUID, $accessToken);
        if ($content) {
              return response($content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="'. $invoiceUUID .'.pdf"');
        }
          
        abort(404);
          
      }
    
    
    
}
