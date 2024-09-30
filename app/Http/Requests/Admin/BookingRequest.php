<?php

namespace App\Http\Requests\Admin;

use App\Mappers\BookingTypeMapper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BookingRequest extends FormRequest
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
    // protected function prepareForValidation()
    // {
    //     $company = Company::find(request()->company_id);
    //     $shippingAgent = shippingAgent::find(request()->shipping_agent_id);

    //     $this->merge([
    //         'taxed'             => !is_null($company) ? $company->taxed : null,
    //         'shipping_agent'    => !is_null($shippingAgent) ? $shippingAgent->title : null,
    //     ]);
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // "company_id" => "1"
        // "employee_id" => "1"
        // "shipping_agent_id" => "2"
        // "booking_number" => "book123"
        // "certificate_number" => "cert123"
        // "type_of_action" => "1"

        // "discharge_date" => "2023-07-17"
        // "permit_end_date" => "2023-07-26"
        $rules = [
            'company_id'        => ['required', 'exists:companies,id'],
            'employee_id'       => ['required', 'exists:employees,id'],

            'shipping_agent_id' => ['required', 'exists:shipping_agents,id'],
            'booking_number'    => ['required', 'string', 'max:255'],
            'employee_name'    => ['required', 'min:3', 'string', 'max:255'],
            'certificate_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type_of_action'    => ['required'],
            'containers' => ['required', 'array', 'min:1'],
            // 'containers.*.factory_id' => ['required', 'numeric',],
            'containers.*.branch_id' => ['required', 'numeric',],
            'containers.*.container_id' => ['required', 'numeric',],
            'containers.*.arrival_date' => ['required', 'date',],
            'containers.*.containers_count' => ['required', 'numeric', 'min:1'],
            'factory_id'        => ['required', 'string', 'exists:factories,id'],
            // 'taxed'             => ['sometimes', 'nullable'],
            // 'factoriesIDs.*'    => ['required', 'array'],
            // 'factoriesIDs.*'    => ['required', 'string', 'exists:factories,id'],
        ];
        if (
            isset(request()->type_of_action)
            && !is_null(request('type_of_action'))
            && request('type_of_action') == BookingTypeMapper::OUTBOUND
        ) {
            $rules['discharge_date']    = ['required', 'date', 'after:tomorrow'];
            $rules['permit_end_date']   = ['required', 'date', 'after_or_equal:discharge_date'];
        }
        // if (
        //     isset(request()->type_of_action)
        //     && !is_null(request('type_of_action'))
        //     && request('type_of_action') != BookingTypeMapper::CLEARANCE
        // ) {

        //     $rules['containers_number']     = ['required', 'numeric', 'min:0'];
        //     $rules['container_type']          = ['required', 'exists:containers,id'];

        //     $rules['arrival_dates']         = ['required', 'array'];
        //     $rules['arrival_dates.*']       = ['required', 'date'];

        //     $rules['branches']              = ['required', 'array'];
        //     $rules['branches.*']            = ['required', 'exists:branches,id'];

        //     $rules['sail_of_numbers']   = ['sometimes', 'nullable', 'array'];
        //     $rules['sail_of_numbers.*'] = ['sometimes', 'nullable', 'string', 'max:255'];
        //     $rules['container_no']      = ['sometimes', 'nullable', 'array'];
        //     $rules['container_no.*']    = ['sometimes', 'nullable', 'string', 'max:255'];
        // } else {
        //     $rules['quantity']              = ['required', 'numeric', 'min:0'];
        //     $rules['unit']                  = ['required', 'string', 'max:255'];
        //     $rules['branch_id']             = ['required', 'exists:branches,id'];
        //     $rules['arrival_date']          = ['required', 'date'];
        // }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, mixed>
     */
    public function attributes()
    {
        return [
            'factory_id'        =>  __('main.factory'),
            'arrival_dates.*'   => __('admin.arrival_date'),
            'branches.*'        => __('admin.branch'),
            'container_no.*'    => __('admin.container_no'),
        ];
    }



    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(redirect()->back()->withErrors($validator->errors())->withInput());
    }
}
