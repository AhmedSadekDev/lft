<?php

namespace App\Http\Requests\Api\Agent;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;
use Illuminate\Validation\ValidationException;

class TransferAgentRequest extends FormRequest
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
            'value'     => 'required|numeric',
            'agent_id'  => 'required|exists:agents,id',
            'notes'     => 'sometimes'
        ];
    }

    protected function failedValidation(Validator $validator){
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
