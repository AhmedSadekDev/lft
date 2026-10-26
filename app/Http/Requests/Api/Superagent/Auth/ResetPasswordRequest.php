<?php

namespace App\Http\Requests\Api\Superagent\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;
use Illuminate\Validation\ValidationException;

class ResetPasswordRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('password_confirmation') && ! $this->filled('confirm_password')) {
            $this->merge(['confirm_password' => $this->input('password_confirmation')]);
        }
    }

    /**
     * Superagent mobile reset after verifyOtp: email + password only (no reset_token).
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'email' => 'required|exists:superagents,email',
            'password' => 'required|min:8',
            'confirm_password' => 'required|min:8|same:password',
        ];
    }

    public function attributes()
    {
        return [
            'password' => 'كلمة المرور',
            'confirm_password' => 'تأكيد كلمة المرور',
            'email' => 'البريد الإلكتروني',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
