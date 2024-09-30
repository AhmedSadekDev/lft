<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
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
            'company_id'        => ['required', 'exists:companies,id'],
            'employee_id'       => ['required', 'exists:employees,id'],

            'shipping_agent_id' => ['required', 'exists:shipping_agents,id'],
            'booking_number'    => ['required', 'string', 'max:255'],
            'employee_name'    => ['required', 'min:3', 'string', 'max:255'],
            'certificate_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type_of_action'    => ['required'],
            'factory_id'        => ['required', 'string', 'exists:factories,id'],
        ];
    }
}
