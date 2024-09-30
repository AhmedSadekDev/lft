<?php

namespace App\Http\Requests\Api\Agent\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;
use Illuminate\Validation\ValidationException;

class EmailRequest extends FormRequest
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
            'email'     => 'required|exists:agents,email'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
