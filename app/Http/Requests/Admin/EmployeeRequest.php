<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeRequest extends FormRequest
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
            'company_id'=> 'required|exists:companies,id',
            'name'      => 'required|string|max:255',
            'job'       => 'required|string|max:255',
            'email'     =>  request()->method() == 'POST' ? 'required|string|email|max:255|unique:employees,email' : 'required|string|email|max:255|unique:employees,email,'. $this->employee->id,
            'phone'     => 'required|string|digits_between:9,12',
            'note'      => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, mixed>
     */
    public function attributes()
    {
        return [
            'company_id' => __('main.company'),
            'name'       => __('admin.name'),
            'job'        => __('admin.job'),
            'email'      => __('admin.email'),
            'phone'      => __('admin.phone'),
            'note'       => __('admin.note'),
        ];
    }

    /**
     * Get the response for a failed validation attempt.
     *
     * @param  array<string, mixed>  $errors
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function failedValidation(Validator $validator){
        throw new HttpResponseException(redirect()->back()->withErrors($validator->errors())->withInput());
    }

}
