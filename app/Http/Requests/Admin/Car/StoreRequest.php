<?php

namespace App\Http\Requests\Admin\Car;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRequest extends FormRequest
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
            'car_number'     =>  'required|unique:cars,car_number',
            'wallet' => 'nullable|numeric'
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
            'car_number'       => __('admin.car_number')
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
