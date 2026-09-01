<?php

namespace App\Http\Requests\Admin\Receipt;

use App\Models\Booking;
use App\Models\Receipt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'exists:bookings,id'],
            'booking_number' => ['nullable', 'string', 'max:255'],
            'service_type_id' => ['required', 'exists:service_categories,id'],
            'service_id' => ['required', 'exists:services,id'],
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
            'image' => ['nullable', 'mimes:png,jpg,jpeg', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cost.required' => 'التكلفة مطلوبة',
            'payment_source.required' => 'مصدر الدفع مطلوب',
            'booking_id.required' => 'يجب ربط الإيصال برقم البوكينج / الطلب',
            'service_type_id.required' => 'نوع الخدمة مطلوب',
            'service_id.required' => 'يجب اختيار الخدمة',
            'service_id.required_with' => 'يجب اختيار الخدمة عند ربط الإيصال بطلب',
            'supplier_id.required_if' => 'يجب اختيار المورد عند اختيار مصدر الدفع: مورد',
            'supplier_invoice_number.required_if' => 'رقم فاتورة المورد مطلوب عند اختيار مصدر الدفع: مورد',
            'booking_number.exists' => 'رقم البوكينج غير موجود',
        ];
    }

    public function attributes(): array
    {
        return [
            'service_id' => __('admin.service'),
            'service_type_id' => __('admin.service_type'),
            'image' => __('admin.receipt_image'),
            'booking_id' => 'الطلب',
            'booking_number' => 'رقم البوكينج',
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

        if ($this->input('service_id') === 'to_be_disabled' || $this->input('service_id') === '') {
            $this->merge(['service_id' => null]);
        }

        if ($this->input('service_type_id') === 'to_be_disabled' || $this->input('service_type_id') === '') {
            $this->merge(['service_type_id' => null]);
        }

        if ($this->input('booking_id') === '') {
            $this->merge(['booking_id' => null]);
        }

        $bookingNumber = trim((string) $this->input('booking_number', ''));
        if ($bookingNumber !== '' && !$this->filled('booking_id')) {
            $booking = Booking::query()
                ->where(function ($q) use ($bookingNumber) {
                    $q->where('booking_number', $bookingNumber);
                    if (ctype_digit($bookingNumber)) {
                        $q->orWhere('id', (int) $bookingNumber);
                    }
                })
                ->first();

            if ($booking) {
                $this->merge(['booking_id' => $booking->id]);
            }
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $bookingNumber = trim((string) $this->input('booking_number', ''));
            if ($bookingNumber !== '' && !$this->filled('booking_id')) {
                $validator->errors()->add('booking_number', 'رقم البوكينج غير موجود');
            }

            if (!$this->filled('booking_id') && $bookingNumber === '') {
                $validator->errors()->add('booking_number', 'يجب إدخال رقم البوكينج أو اختيار الطلب');
            }
        });
    }
}
