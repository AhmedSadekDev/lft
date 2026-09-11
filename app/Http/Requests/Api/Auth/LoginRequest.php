<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Traits\ResponseTrait;

class LoginRequest extends FormRequest
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
            'email'     => [
                'required',
                'email:rfc,dns',
                function ($attribute, $value, $fail) {
                    $companyExists = \App\Models\Company::where('email', $value)->exists();
                    $employeeExists = \App\Models\Employee::where('email', $value)->exists();
                    
                    if (! $companyExists && ! $employeeExists) {
                        $fail(__('validation.exists'));
                    }
                }
            ],
            'password'  => 'required',
        ];
    }


    protected function failedValidation(Validator $validator){
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
