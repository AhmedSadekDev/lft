<?php

namespace App\Http\Requests\Admin\Superagent;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateRequest extends FormRequest
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
            'name'      => 'required|string|max:255',
            'email'     =>  'required|string|email|max:255|unique:superagents,email,'. $this->superagent->id,
            'phone'     => 'required|string|digits_between:9,12',
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
            'name'       => __('admin.name'),
            'email'      => __('admin.email'),
            'phone'      => __('admin.phone'),
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
