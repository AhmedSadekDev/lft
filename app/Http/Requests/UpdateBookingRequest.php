<?php

namespace App\Http\Requests;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'containers' => ['sometimes', 'array'],
            'containers.*.branch_id' => [
                'sometimes',
                'nullable',
                'numeric',
                Rule::exists('branches', 'id')->where(
                    fn ($query) => $query->where('factory_id', $this->input('factory_id'))
                ),
            ],
            'branches' => ['sometimes', 'array'],
            'branches.*' => [
                'nullable',
                'numeric',
                Rule::exists('branches', 'id')->where(
                    fn ($query) => $query->where('factory_id', $this->input('factory_id'))
                ),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $factoryId = $this->input('factory_id');
            $booking = $this->route('booking');
            if (! $factoryId || ! $booking) {
                return;
            }

            $branchIds = collect($this->input('containers', []))
                ->pluck('branch_id')
                ->merge($this->input('branches', []))
                ->filter();

            if ($branchIds->isEmpty()) {
                $branchIds = $booking->bookingContainers()->pluck('branch_id');
            }

            $hasMismatch = Branch::whereIn('id', $branchIds->unique()->filter())
                ->where('factory_id', '!=', $factoryId)
                ->exists();

            if ($hasMismatch) {
                $validator->errors()->add('factory_id', __('alerts.branch_must_match_booking_factory'));
            }
        });
    }

    public function messages()
    {
        return [
            'containers.*.branch_id.exists' => __('alerts.branch_must_match_booking_factory'),
            'branches.*.exists' => __('alerts.branch_must_match_booking_factory'),
        ];
    }
}
