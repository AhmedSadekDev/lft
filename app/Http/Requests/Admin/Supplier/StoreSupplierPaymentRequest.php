<?php

namespace App\Http\Requests\Admin\Supplier;

use App\Models\SupplierPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'source_type' => ['required', Rule::in([
                SupplierPayment::SOURCE_SAFE,
                SupplierPayment::SOURCE_REPRESENTATIVE,
            ])],
            'source_id' => [
                'nullable',
                'required_if:source_type,' . SupplierPayment::SOURCE_REPRESENTATIVE,
                'exists:agents,id',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'مبلغ السداد مطلوب',
            'amount.min' => 'مبلغ السداد يجب أن يكون أكبر من صفر',
            'source_type.required' => 'مصدر السداد مطلوب',
            'source_type.in' => 'مصدر السداد غير صالح',
            'source_id.required_if' => 'يجب اختيار المندوب عند السداد من عهدة المندوب',
            'source_id.exists' => 'المندوب المحدد غير موجود',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('source_type') === SupplierPayment::SOURCE_SAFE) {
            $this->merge(['source_id' => null]);
        }
    }
}
