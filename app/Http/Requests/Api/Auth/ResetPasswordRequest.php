<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;

class ResetPasswordRequest extends FormRequest
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
            'email'                 => ['required', 'email', 'exists:companies,email'],
            'password'              => ['required', 'min:6', 'confirmed'],
            'password_confirmation' => ['required', 'min:6']
        ];
    }

    public function messages()
    {
        return [
            'email.required'                => ':attribute is required',
            'password.required'             => ':attribute is required',
            'password_confirmation.required'=> ':attribute is required',

        ];
    }

    public function attributes()
    {
        return [
            'email'                 => __('main.email'),
            'password'              => __('main.password'),
            'password_confirmation' => __('main.cnf_passsword'),
        ];
    }

    protected function failedValidation(Validator $validator){
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }


}
