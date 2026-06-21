<?php
namespace App\Http\Resources\Api\Desktop;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Models\Invoice;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $invoice = Invoice::where('booking_id', $this->id)->first();
        return [
            'id'             => $this->id,
            'company_name'   => $this->company?->name ?? "__",
            'signature_company'  => $this->signature_company ?? '',
            'signature_company_id'  => $this->signature_company_id ?? '',
            'signature_date'  => $this->signature_date ? Carbon::parse($this->signature_date)->format('Y-m-d h:i a') : '',
            'factory_name'   => $this->factory?->name ?? "__",
            'booking_number' => $invoice?->invoice_number ?? "",
            'taxed'          => $this->company?->taxed ?? 0,
            'taxed_invoice'  => $this->taxed_invoice,
            'created_at'     => Carbon::parse($this->created_at)->format('Y-m-d h:i a'),
            'submission_id'  => $this->submission_id ?? "",
            'invoice_uuid'   => $this->invoice_uuid ?? "",
            'invoice_status'   => $this->invoice_status ?? "",
            'is_submitted'   => $this->invoice_status == "Valid" ? 1 : 0,
            'invoice_link'   => $this->is_submitted ? route('bookings.previewInvoicePDF', [$this->signature_company_id, $this->invoice_uuid]) : "",
        ];
    }
}
