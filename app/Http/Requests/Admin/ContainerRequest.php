<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ContainerRequest extends FormRequest
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
            'type'          => 'required|max:100',
            'size'          => 'required|max:100',
            // 'container_no'  => request()->method() == 'POST' ? 'required|max:100|unique:containers,container_no' : 'required|max:100|unique:containers,container_no,'. $this->id,
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, mixed>
     */
    public function messages()
    {
        return [
            'type.required'         => 'Type is required',
            'size.required'         => 'Size is required',
        ];
    }

    /**
     * Get the attributes for the defined validation rules.
     *
     * @return array<string, mixed>
     */
    public function attributes()
    {
        return [
            'type'          => 'Type',
            'size'          => 'Size',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function filters()
    {
        return [
            'type'          => 'trim|escape|capitalize',
            'size'          => 'trim|escape|capitalize',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function sanitize()
    {
        return [
            'type'          => 'trim|escape|capitalize',
            'size'          => 'trim|escape|capitalize',
        ];
    }

    /**
     * Get the response for a failed validation attempt.
     *
     * @param  array<string, mixed>  $errors
     * @return \Illuminate\Http\RedirectResponse
     */
    public function response(array $errors)
    {
        return redirect()->back()->withErrors($errors)->withInput();
    }
}
