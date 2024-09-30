<?php

namespace App\Http\Requests\Admin;

use App\Models\CitiesAndRegions;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BranchRequest extends FormRequest
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
            'country'   => !is_null(request()->country_id) ? CitiesAndRegions::find(request()->country_id)?->title : null,
            'city'      => !is_null(request()->city_id) ? CitiesAndRegions::find(request()->city_id)?->title : null,
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
            'factory_id'=> 'required',
            'name'      => 'required|max:100',
//            'country_id'=> 'sometimes|nullable|exists:cities_and_regions,id',
//            'country'   => 'required|max:100',
            'city_id'   => 'sometimes|nullable|exists:cities_and_regions,id',
            'city'      => 'required|max:100',
            'address'   => 'required|max:100',
            'email'     => request()->method() == 'POST' ? 'required|max:100|unique:branches,email|unique:factories,email, '.request('factory_id')   : 'required|max:100|unique:branches,email,'.$this->branch->id.'|unique:factories,email,'.request('factory_id'),
            'number'    => 'required|max:100000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, mixed>
     */
    public function attributes()
    {
        return [
            'factory_id'=> __('main.factory'),
            'name'      =>  __('admin.name'),
            'country'   => __('admin.country'),
            'city'      => __('admin.city'),
            'address'   => __('admin.address'),
            'email'     => __('admin.email'),
            'number'    => __('admin.branch_number'),
        ];
    }

    protected function failedValidation(Validator $validator){
        throw new HttpResponseException(redirect()->back()->withErrors($validator->errors())->withInput());
    }

}
