<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SponserRequest extends FormRequest
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
            'title'         => ['required', 'array'],
            'title.*'       => ['required', 'string', 'max:100'],
            'description'   => ['required', 'array'],
            'description.*' => ['required', 'string'],
            'image'         => request()->method() == 'POST' ? ['required' ,'image', 'mimes:png,jpg,jpeg'] : ['nullable' ,'sometimes' ,'image' , 'mimes:png,jpg,jpeg'],
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
            'title.en'          => __('admin.en_title'),
            'title.ar'          => __('admin.ar_title'),
            'description.en'    => __('admin.en_description'),
            'description.ar'    => __('admin.ar_description'),
            'image'         => __('admin.image'),
        ];
    }
}
