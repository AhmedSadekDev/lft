<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FactoryRequest extends FormRequest
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
            'name'          => ['required', 'string', 'max:255'],
            'email'         =>  request()->method() == 'POST' ? 'required|string|max:255|unique:factories,email' : 'required|string|max:255|unique:factories,email,' . $this->factory->id,
            'number'        =>  request()->method() == 'POST' ? 'required|numeric|min:0|unique:factories,number' : 'required|numeric|min:0|unique:factories,number,' . $this->factory->id,
            'attachments'   => ['sometimes', 'nullable'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx', 'max:5000'],
        ];
    }

    /**
     * Get the error attributes for the defined validation rules.
     *
     * @return array<string, mixed>
     */
    public function attributes()
    {
        return [
            'name'          => __('admin.name'),
            'email'         => __('admin.email'),
            'number'        => __('admin.factory_number'),
            'attachments'   => __('admin.attachments'),
            'attachments.*' => __('admin.attachments'),
        ];
    }

    /**
     * Get the response for a failed validation attempt.
     *
     * @param  array<string, mixed>  $errors
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function failedValidation(Validator $validator){
        // dd($validator->errors());
        throw new HttpResponseException(redirect()->back()->withErrors($validator->errors())->withInput());
    }

}
