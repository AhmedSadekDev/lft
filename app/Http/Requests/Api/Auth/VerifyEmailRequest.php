<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;

class VerifyEmailRequest extends FormRequest
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
            'email' => ['string', 'email:dns', 'exists:companies,email'],
            'otp'   => ['required', 'exists:otps,otp'],
        ];
    }

    public function messages()
    {
        return [
            'email.required'=> ':attribute is required',
            'email.exists'  => 'Your email is not correct',
            'otp.required'  => ':attribute is required',
            'otp.exists'    => 'Your OTP is not correct',
        ];
    }

    protected function failedValidation(Validator $validator){
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
