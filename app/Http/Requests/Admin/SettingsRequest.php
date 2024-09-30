<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
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
            'phone'     => ['required', 'numeric', 'digits_between:9,12'],
            'email'     => ['required', 'string', 'email', 'max:255'],
            'whatsapp'  => ['nullable', 'sometimes', 'string', 'max:255'],
            'facebook'  => ['nullable', 'sometimes', 'string', 'max:255'],
            'twitter'   => ['nullable', 'sometimes', 'string', 'max:255'],
            'linkedin'  => ['nullable', 'sometimes', 'string', 'max:255'],
            'instagram' => ['nullable', 'sometimes', 'string', 'max:255'],
            'logo'      => request()->method() == 'POST' ? ['required' ,'image', 'mimes:png,jpg,jpeg'] : ['nullable' ,'sometimes' ,'image' , 'mimes:png,jpg,jpeg'],
        ];
    }
}
