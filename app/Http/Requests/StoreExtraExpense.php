<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtraExpense extends FormRequest
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
            'name' => 'required|string|max:255',
            'value' => 'required',
            'booking_container_id' => 'required|exists:booking_containers,id',
            'car_id' => 'required|exists:cars,id',
            'driver_id' => 'required|exists:drivers,id'
        ];
    }
}
