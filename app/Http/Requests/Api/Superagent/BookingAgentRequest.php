<?php

namespace App\Http\Requests\Api\Superagent;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;
use Illuminate\Validation\ValidationException;

class BookingAgentRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('booking_id') && ! is_array($this->booking_id)) {
            $this->merge(['booking_id' => [$this->booking_id]]);
        }
        if ($this->has('booking_container_ids') && ! is_array($this->booking_container_ids)) {
            $this->merge(['booking_container_ids' => [$this->booking_container_ids]]);
        }
        if ($this->has('agent_ids') && ! is_array($this->agent_ids)) {
            $this->merge(['agent_ids' => [$this->agent_ids]]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'agent_ids' => 'required|array|min:1',
            'agent_ids.*' => 'required|integer|exists:agents,id',
            'booking_id' => 'sometimes|array',
            'booking_id.*' => 'integer|exists:bookings,id',
            'booking_container_ids' => 'sometimes|array|min:1',
            'booking_container_ids.*' => 'integer|exists:booking_containers,id',
            'booking_container_id' => 'sometimes|integer|exists:booking_containers,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $this->filled('booking_id') && ! $this->filled('booking_container_ids') && ! $this->filled('booking_container_id')) {
                $validator->errors()->add('booking_id', 'يجب تحديد طلب أو حاويات');
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
