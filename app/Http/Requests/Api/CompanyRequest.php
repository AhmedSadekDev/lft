<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;

class CompanyRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name'      => request()->method() == 'POST' ? ['required', 'string','unique:companies,name,'.auth()->user()->id] : ['nullable', 'sometimes', 'string','unique:companies,name,'.auth()->user()->id],
            'email'     => request()->method() == 'POST' ? ['required', 'email','unique:companies,email,'.auth()->user()->id] : ['nullable', 'sometimes', 'email','unique:companies,email,'.auth()->user()->id],
            'phone'     => request()->method() == 'POST' ? ['required', 'digits_between:9,12','unique:companies,phone,'.auth()->user()->id] : ['nullable', 'sometimes', 'digits_between:9,12','unique:companies,phone,'.auth()->user()->id],
            'address'   => ['nullable', 'string', 'max:200'],
            'password'              => request()->method() == 'POST' ? ['required', 'min:6', 'confirmed'] : ['nullable', 'sometimes', 'min:6', 'confirmed'],
            'password_confirmation' => request()->method() == 'POST' ? ['required', 'min:6'] : ['nullable', 'sometimes', 'min:6'],
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
            'address'    => __('admin.address'),
            'password'   => __('main.password'),
        ];
    }

    protected function failedValidation(Validator $validator){
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
