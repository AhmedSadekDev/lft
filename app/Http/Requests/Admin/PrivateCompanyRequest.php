<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PrivateCompanyRequest extends FormRequest
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
        // Get the private company ID from route parameter
        $privateCompany = $this->route('private-company') ?? $this->route('private_companies');
        $privateCompanyId = $privateCompany ? $privateCompany->id : null;

        return [
            'name'                  => ['required', 'string', 'max:255'],
            'tax_no'               => [
                'nullable',
                'string',
                'max:255',
                request()->method() == 'POST'
                    ? 'unique:private_companies,tax_no'
                    : 'unique:private_companies,tax_no,' . $privateCompanyId
            ],
            'commercial_register'   => [
                'nullable',
                'string',
                'max:255',
                request()->method() == 'POST'
                    ? 'unique:private_companies,commercial_register'
                    : 'unique:private_companies,commercial_register,' . $privateCompanyId
            ],
            'logo'                  => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'phone1'                => ['nullable', 'string', 'max:255'],
            'phone2'                => ['nullable', 'string', 'max:255'],
            'tel_fax'               => ['nullable', 'string', 'max:255'],
            'email'                 => ['nullable', 'email', 'max:255'],
            'address'               => ['nullable', 'string', 'max:500'],
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
            'name'                  => __('admin.name'),
            'tax_no'                => __('admin.tax_no'),
            'commercial_register'   => 'السجل التجاري',
            'logo'                  => 'اللوجو',
            'phone1'                => 'الهاتف الأول',
            'phone2'                => 'الهاتف الثاني',
            'tel_fax'               => 'تليفون - فاكس',
            'email'                 => __('admin.email'),
            'address'               => __('admin.address'),
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
