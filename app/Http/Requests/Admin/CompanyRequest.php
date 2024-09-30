<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name'          => ['required'],
            'email'         => request()->method() == 'POST' ? ['required', 'email', 'unique:companies,email'] : ['required', 'email', 'unique:companies,email,' . $this->company->id],
            'address'       => ['required', 'max:255'],
            'phone'         => ['required', 'numeric', 'digits_between:9,12'],
            'taxed'         => ['required'],
            'tax_no'        => request()->method() == 'POST' ? ['required', 'numeric', 'min:0', 'unique:companies,tax_no', 'digits_between:1,20'] : ['required', 'numeric', 'min:0', 'unique:companies,tax_no,' . $this->company->id, 'digits_between:1,20'],
            'bill_type'     => ['required', 'in:1,2'],
            'attachments'   => ['sometimes', 'nullable'],
            'attachments.*' => ['sometimes', 'nullable', 'mimes:pdf,csv,xls,xlsx,doc,docx,png,jpeg,jpg', 'max:2048'],
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function attributes()
    {
        return [
            'name'          => __('admin.name'),
            'email'         => __('admin.email'),
            'address'       => __('admin.address'),
            'phone'         => __('admin.phone'),
            'tax_no'        => __('admin.tax_no'),
            'taxed'         => __('admin.taxed'),
            'bill_type'     => __('admin.bill_type'),
            'attachments'   => __('admin.attachments'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, mixed>
     */
    public function validationData()
    {
        return $this->all();
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(redirect()->back()->withErrors($validator->errors())->withInput());
    }
}
