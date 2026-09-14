<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;
use Illuminate\Validation\Rule;

class BookingContainerRequest extends FormRequest
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

    protected function bookingFactoryId(): ?int
    {
        $booking = $this->route('booking')
            ?? $this->route('booking_container')?->booking;

        return $booking?->factory_id ? (int) $booking->factory_id : null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $factoryId = $this->bookingFactoryId();

        return [
            'container_no' => [
                'nullable',
                'string'
            ],
            'sail_of_number' => [
                'nullable',
                'string'
            ],
            'factory_id' => array_values(array_filter([
                'nullable',
                $factoryId
                    ? Rule::in([$factoryId])
                    : 'exists:factories,id',
            ])),
            'branch_id' => array_values(array_filter([
                'nullable',
                $factoryId
                    ? Rule::exists('branches', 'id')->where(
                        fn ($query) => $query->where('factory_id', $factoryId)
                    )
                    : 'exists:branches,id',
            ])),
            'arrival_date' => [
                'nullable',
                'date'
            ],
            'status' => [
                'nullable',
                'integer',
                'in:0,1,2,3'
            ],
            'yard_id' => [
                'nullable',
                'exists:yards,id'
            ],
            'container_id' => [
                'nullable',
                'exists:containers,id'
            ],
            'departure_id' => [
                'nullable',
                'exists:cities_and_regions,id'
            ],
            'loading_id' => [
                'nullable',
                'exists:cities_and_regions,id'
            ],
            'aging_id' => [
                'nullable',
                'exists:cities_and_regions,id'
            ],
            'price' => [
                'nullable',
                'numeric',
                'min:0'
            ],
        ];
    }

    public function attributes()
    {
        return [
            'company_id'    => __('main.company'),
            'container_id'  => __('main.container'),
            'factory_id'    => __('main.factory'),
            'branch_id'     => __('admin.branch'),
            'departure_id'  => __('admin.departure_location'),
            'loading_id'    => __('admin.loading_location'),
            'aging_id'      => __('admin.aging_location'),
            'price'         => __('admin.price'),
        ];
    }

    public function messages()
    {
        return [
            'factory_id.in' => __('alerts.branch_must_match_booking_factory'),
            'branch_id.exists' => __('alerts.branch_must_match_booking_factory'),
        ];
    }
}
