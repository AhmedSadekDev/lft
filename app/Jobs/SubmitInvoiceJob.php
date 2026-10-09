<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\EInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubmitInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [30, 60, 120];

    protected $invoice;
    protected $referenceId;
    protected $company;

    public function __construct($invoice, $referenceId, $company)
    {
        $this->invoice = $invoice;
        $this->referenceId = $referenceId;
        $this->company = $company;
    }

    public function handle(EInvoiceService $eInvoiceService)
    {
        try {
            $booking = Booking::where('id', $this->referenceId)->first();
            if (!$booking) {
                return;
            }

            // Idempotency check: don't resubmit if already Valid / submitted
            if ((int) ($booking->is_submitted ?? 0) === 1 && strcasecmp((string) ($booking->invoice_status ?? ''), 'valid') === 0) {
                return;
            }

            $accessToken = $eInvoiceService->getAccessToken(
                $this->company->ETA_CLIENT_ID ?? null,
                $this->company->ETA_CLIENT_SECRET ?? null
            );
            if (!$accessToken) {
                Booking::where('id', $this->referenceId)->update([
                    'invoice_status' => 'Failed',
                    'invoice_errors' => 'فشل في الحصول على Access Token',
                ]);
                return;
            }

            $response = $eInvoiceService->submitInvoice($this->invoice, $accessToken);

            if (!$response || $response['status'] === false) {
                Booking::where('id', $this->referenceId)->update([
                    'invoice_status' => 'Failed',
                    'invoice_errors' => $response['message'] ?? 'خطأ غير معروف',
                ]);
                return;
            }

            if (isset($response['data']['submissionId'], $response['data']['acceptedDocuments'][0]['uuid'])) {
                $submissionId = $response['data']['submissionId'];
                $uuid = $response['data']['acceptedDocuments'][0]['uuid'];

                // Short sleep before checking status if synchronous, otherwise retrieve details
                if (!app()->runningUnitTests()) {
                    sleep(10);
                }

                $invoiceDetails = $eInvoiceService->getInvoiceDetails($uuid, $accessToken);

                $invoiceStatus = $invoiceDetails['data']['status'] ?? 'Unknown';

                if ($invoiceStatus === 'Valid') {
                    Booking::where('id', $this->referenceId)->update([
                        'submission_id'  => $submissionId,
                        'invoice_uuid'   => $uuid,
                        'invoice_status' => 'Valid',
                        'is_submitted' => 1,
                        'invoice_errors' => null,
                    ]);
                } else {
                    $reasons = isset($invoiceDetails['data']['validationResults'])
                        ? collect($invoiceDetails['data']['validationResults'])->pluck('message')->implode(', ')
                        : "سبب غير معروف";

                    Booking::where('id', $this->referenceId)->update([
                        'submission_id'  => $submissionId,
                        'invoice_uuid'   => $uuid,
                        'invoice_status' => 'Invalid',
                        'invoice_errors' => $reasons,
                    ]);
                }
            } else {
                Booking::where('id', $this->referenceId)->update([
                    'invoice_status' => 'Failed',
                    'invoice_errors' => 'استجابة غير متوقعة من ETA: ' . json_encode($response),
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SubmitInvoiceJob error: ' . $e->getMessage());
            Booking::where('id', $this->referenceId)->update([
                'invoice_status' => 'Failed',
                'invoice_errors' => 'خطأ أثناء معالجة الطلب: ' . $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
