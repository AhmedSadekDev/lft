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
        $accessToken = $eInvoiceService->getAccessToken(
            $this->company->ETA_CLIENT_ID,
            $this->company->ETA_CLIENT_SECRET
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

            sleep(15); // تقليل فترة الانتظار لـ 15 ثانية فقط

            $invoiceDetails = $eInvoiceService->getInvoiceDetails($uuid, $accessToken);

            $invoiceStatus = $invoiceDetails['data']['status'] ?? 'Unknown';

            if ($invoiceStatus === 'Valid') {
                Booking::where('id', $this->referenceId)->update([
                    'submission_id'  => $submissionId,
                    'invoice_uuid'   => $uuid,
                    'invoice_status' => 'Valid',
                    'is_submitted' => 1
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
    }
}
