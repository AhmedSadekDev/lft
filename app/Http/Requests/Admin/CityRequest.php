<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CityRequest extends FormRequest
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
            'title'     => ['required', 'array'],
            'title.*'   => ['required', 'string', 'max:100'],
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
            'title.en'  => __('admin.en_title'),
            'title.ar'  => __('admin.ar_title'),
        ];
    }

    protected function failedValidation(Validator $validator){
        throw new HttpResponseException(redirect()->back()->withErrors($validator->errors())->withInput());
    }
}
