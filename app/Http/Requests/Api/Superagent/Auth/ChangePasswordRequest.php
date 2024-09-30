<?php

namespace App\Http\Requests\Api\Superagent\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;
use Illuminate\Validation\ValidationException;

class ChangePasswordRequest extends FormRequest
{
    use ResponseTrait;

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
            'old_password'     => 'required|min:8',
            'new_password'  => 'required|min:8',
        ];
    }

    protected function failedValidation(Validator $validator){
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
