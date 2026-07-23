<?php

namespace App\Http\Requests\Admin\Receipt;

use App\Models\Receipt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'cost' => ['required', 'numeric', 'min:0'],
            'payment_source' => ['required', Rule::in([
                Receipt::PAYMENT_SOURCE_SAFE,
                Receipt::PAYMENT_SOURCE_REPRESENTATIVE,
                Receipt::PAYMENT_SOURCE_SUPPLIER,
            ])],
            'supplier_id' => [
                'nullable',
                'required_if:payment_source,' . Receipt::PAYMENT_SOURCE_SUPPLIER,
                'exists:suppliers,id',
            ],
            'supplier_invoice_number' => [
                'nullable',
                'required_if:payment_source,' . Receipt::PAYMENT_SOURCE_SUPPLIER,
                'string',
                'max:255',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cost.required' => 'التكلفة مطلوبة',
            'payment_source.required' => 'مصدر الدفع مطلوب',
            'supplier_id.required_if' => 'يجب اختيار المورد عند اختيار مصدر الدفع: مورد',
            'supplier_invoice_number.required_if' => 'رقم فاتورة المورد مطلوب عند اختيار مصدر الدفع: مورد',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('payment_source') !== Receipt::PAYMENT_SOURCE_SUPPLIER) {
            $this->merge([
                'supplier_id' => null,
                'supplier_invoice_number' => null,
            ]);
        }
    }
}
