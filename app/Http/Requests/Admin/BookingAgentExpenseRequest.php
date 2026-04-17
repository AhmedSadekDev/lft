<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BookingAgentExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type_id' => ['required', 'exists:service_categories,id'],
            'service_id' => ['required', 'exists:services,id'],
            'value' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'image' => ['sometimes', 'nullable', 'mimes:png,jpg,jpeg', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'service_type_id' => __('admin.service_type'),
            'service_id' => __('admin.service'),
            'value' => __('admin.price'),
            'notes' => __('admin.note'),
            'image' => __('admin.receipt_image'),
        ];
    }
}
