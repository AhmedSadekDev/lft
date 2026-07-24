<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;

class BookingServiceRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->input('payment_type') !== 'supplier') {
            $this->merge([
                'supplier_id' => null,
                'supplier_invoice_number' => null,
            ]);
        }

        if ($this->input('payment_type') !== 'bank') {
            $this->merge(['bank_id' => null]);
        }

        if ($this->input('payment_type') !== 'agent') {
            $this->merge(['agent_id' => null]);
        }
    }

    public function rules()
    {
        return [
            'service_id'    => ['required', 'exists:services,id'],
            'price'         => ['required', 'numeric', 'min:0'],
            'note'          => ['sometimes', 'nullable', 'string'],
            'image'         => ['sometimes', 'nullable', 'mimes:png,jpg,jpeg', 'max:5000'],
            'payment_type'  => ['sometimes', 'nullable', 'in:vault,bank,agent,supplier'],
            'bank_id'       => ['required_if:payment_type,bank', 'nullable', 'exists:banks,id'],
            'agent_id'      => ['required_if:payment_type,agent', 'nullable', 'exists:agents,id'],
            'supplier_id'   => ['required_if:payment_type,supplier', 'nullable', 'exists:suppliers,id'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes()
    {
        return [
            'invoice_id'    => __('main.invoice'),
            'service'       => __('admin.service'),
            'service_type'  => __('admin.service_type'),
            'price'         => __('admin.price'),
            'note'          => __('admin.note'),
            'image'         => __('admin.receipt_image'),
            'service_id'    => __('admin.service'),
            'payment_type'  => __('admin.payment_type'),
            'bank_id'       => __('main.bank'),
            'agent_id'      => __('main.agent'),
            'supplier_id'   => __('main.suppliers'),
            'supplier_invoice_number' => 'رقم فاتورة المورد',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            $response = $this->validationError($validator->errors());
            throw new ValidationException($validator, $response);
        }
        $response = $this->redirector->to($this->getRedirectUrl())
            ->withInput($this->except($this->dontFlash))
            ->withErrors($validator->errors(), $this->errorBag);
        throw new ValidationException($validator, $response);
    }
}
