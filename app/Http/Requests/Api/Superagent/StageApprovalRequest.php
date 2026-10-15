<?php

namespace App\Http\Requests\Api\Superagent;

use App\Traits\ResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StageApprovalRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize()
    {
        return true;
    }

    /**
     * booking_container_id approves one container; booking_id approves the whole booking.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'booking_container_id' => 'required_without:booking_id|nullable|integer|exists:booking_containers,id',
            'booking_id'           => 'required_without:booking_container_id|nullable|integer|exists:bookings,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
