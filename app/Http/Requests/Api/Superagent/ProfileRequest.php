<?php

namespace App\Http\Requests\Api\Superagent;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;
use Illuminate\Validation\ValidationException;

class ProfileRequest extends FormRequest
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
            "name" => "required",
            "phone" => "required|digits_between:9,12|unique:superagents,phone," . auth()->guard('superagent')->id(),
            'email'     => 'required|email|unique:superagents,email,' . auth()->guard('superagent')->id(),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
