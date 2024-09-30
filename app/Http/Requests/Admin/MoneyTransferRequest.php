<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MoneyTransferRequest extends FormRequest
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
            'value'                  => ['required', 'numeric'],
            'agent_id'   => ['required', 'exists:agents,id'],
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
            'value'          => __('admin.financial_custody'),
            'agent_id' => __('admin.agent'),
        ];
    }
}
