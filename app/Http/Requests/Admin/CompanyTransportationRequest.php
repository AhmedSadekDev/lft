<?php

namespace App\Http\Requests\Admin;

use App\Models\CitiesAndRegions;
use App\Models\Company;
use App\Models\Container;
use Illuminate\Foundation\Http\FormRequest;

class CompanyTransportationRequest extends FormRequest
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

    //Before Validation
    protected function prepareForValidation()
    {
        $this->merge([
            'departure'         => !is_null(request()->departure_id) ? CitiesAndRegions::find(request()->departure_id)?->title : null,
            'loading'           => !is_null(request()->loading_id) ? CitiesAndRegions::find(request()->loading_id)?->title : null,
            'aging'             => !is_null(request()->aging_id) ? CitiesAndRegions::find(request()->aging_id)?->title : null,
            'company_data'      => !is_null(request()->company_id) ? Company::find(request()->company_id) : null,
            'container_data'    => !is_null(request()->container_id) ? Container::find(request()->container_id) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'company_id'    => ['required', 'exists:companies,id'],
            'container_id'  => ['required', 'exists:containers,id'],
            'departure_id'  => ['required', 'exists:cities_and_regions,id'],

            'loading_id'    => ['required', 'exists:cities_and_regions,id'],
            'aging_id'      => ['required', 'exists:cities_and_regions,id'],
            'price'         => ['required', 'numeric', 'min:0'],

            'loading'       => ['required'],
            'departure'     => ['required'],
            'aging'         => ['required'],

            'company_data'  => ['sometimes', 'nullable'],
            'container_data'=> ['sometimes', 'nullable'],
        ];
    }

    /**
     * Get the error attributes for the defined validation rules.
     *
     * @return array<string, mixed>
     */
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
