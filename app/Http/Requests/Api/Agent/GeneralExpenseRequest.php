<?php

namespace App\Http\Requests\Api\Agent;

use App\Http\Requests\Api\Agent\Concerns\MapsAgentExpenseNotes;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ResponseTrait;
use Illuminate\Validation\ValidationException;

class GeneralExpenseRequest extends FormRequest
{
    use ResponseTrait, MapsAgentExpenseNotes {
        MapsAgentExpenseNotes::prepareForValidation as mapExpenseNotes;
    }

    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mapExpenseNotes();

        $containerId = $this->input('booking_container_id');
        $typeId = $this->input('type_id');
        $bookingId = $this->input('booking_id');

        if ($containerId === '' || $containerId === null) {
            $containerId = null;
        }
        if ($typeId === '' || $typeId === null) {
            $typeId = null;
        }
        if ($bookingId === '' || $bookingId === null) {
            $bookingId = null;
        }

        // شاشة المصروفات العامة غالبًا ترسل type_id بالخطأ (نوع المصروف ≠ مرحلة).
        // من غير حاوية: تجاهل type_id واعتبره مصروفًا عامًا — إلا إذا وُجد booking_id مع type_id (سياق مرحلة ناقص).
        if ($containerId === null && $bookingId === null) {
            $typeId = null;
        }

        $this->merge([
            'booking_container_id' => $containerId,
            'type_id' => $typeId,
            'booking_id' => $bookingId,
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
            'value'     => 'required|numeric|min:0.01',
            'service_id'  => 'required|exists:services,id',
            'notes'     => 'sometimes',
            'booking_id' => 'nullable|integer|exists:bookings,id',
            'booking_container_id' => 'required_with:type_id|nullable|exists:booking_containers,id',
            'type_id' => 'required_with:booking_container_id|nullable|integer|in:0,1,2',
        ];
    }

    public function attributes()
    {
        return [
            'booking_container_id' => 'معرف الحاوية',
            'type_id' => 'مرحلة الإيصال',
            'booking_id' => 'رقم الحجز',
            'service_id' => 'الخدمة',
            'value' => 'المبلغ',
        ];
    }

    protected function failedValidation(Validator $validator){
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
