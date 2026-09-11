<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;

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

    // protected function prepareForValidation()
    // {
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'container_no' => [
                'nullable',
                'string'
            ],
            'sail_of_number' => [
                'nullable',
                'string'
            ],
            'factory_id' => [
                'nullable',
                'exists:factories,id'
            ],
            'branch_id' => [
                'nullable',
                'exists:branches,id'
            ],
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
            'departure_id'  => __('admin.departure_location'),
            'loading_id'    => __('admin.loading_location'),
            'aging_id'      => __('admin.aging_location'),
            'price'         => __('admin.price'),
        ];
    }
}
