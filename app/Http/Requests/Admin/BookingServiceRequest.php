<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;

class BookingServiceRequest extends FormRequest
{
    use ResponseTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    //Before Validation
    protected function prepareForValidation()
    {
        // $serviceCategory = ServiceCategory::find(request()->service_category_id);
        // $this->merge([
        //     'service'   => !is_null($serviceCategory) ? $serviceCategory->title : null,
        // ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // 'invoice_id'    => ['sometimes', 'nullable', 'exists:invoices,id'],
            // 'booking_id'    => ['required', 'exists:bookings,id'],
            // 'service'       => ['required', 'string', 'max:255'],
            'service_id'    => ['required', 'exists:services,id'],
            // 'service_category_id' => ['required', 'exists:service_categories,id'],
            'price'         => ['required', 'numeric', 'min:0'],
            'note'          => ['sometimes', 'nullable', 'string'],
            'image'         => ['sometimes', 'nullable', 'mimes:png,jpg,jpeg', 'max:5000'],
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
        ];
    }

    /**
     * Get the response for a failed validation attempt.
     *
     * @param  array<string, mixed>  $errors
     * @return \Illuminate\Http\RedirectResponse
     */
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
