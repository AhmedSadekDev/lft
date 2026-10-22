<?php

namespace App\Http\Requests\Api\Agent;

use App\Models\BookingContainer;
use App\Services\ShipmentWorkflow;
use App\Traits\ResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class DeliveryPolicyRequest extends FormRequest
{
    use ResponseTrait;

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
        $rules = [
            'car_id'  => 'required|exists:cars,id',
            'driver_id'  => 'required|exists:drivers,id',
            'booking_container_ids' => 'required|array',
            'booking_container_ids.*' => 'exists:booking_containers,id',
            'value'     => 'required|numeric',
            'office_commission' => 'nullable|numeric|min:0',
            'departure_id' => 'required|exists:cities_and_regions,id',
            'loading_id'    => ['required', 'exists:cities_and_regions,id'],
            'aging_id'      => ['required', 'exists:cities_and_regions,id'],
            'date'          => ['nullable', 'date'],
            'image'         => ['sometimes', 'mimes:png,jpg,jpeg,pdf', 'max:10000'],
            // USD record-only; required only when linked booking is reefer (see withValidator).
            'custody_usd'   => ['nullable', 'numeric', 'min:0'],
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $ids = $this->input('booking_container_ids');
            if (! is_array($ids) || $ids === []) {
                return;
            }

            $containers = BookingContainer::with('booking')->whereIn('id', $ids)->get();
            $isReefer = $containers->contains(function ($container) {
                return $container->booking
                    && ShipmentWorkflow::of($container->booking) === ShipmentWorkflow::REEFER;
            });

            if ($isReefer && ! $this->filled('custody_usd')) {
                $validator->errors()->add('custody_usd', __('validation.required', [
                    'attribute' => __('admin.custody_usd'),
                ]));
            }

            if (! $isReefer && $this->filled('custody_usd')) {
                // Ignore silently for non-reefer: strip in prepare or reject — reject to avoid accidental USD on EGP-only paths.
                $validator->errors()->add('custody_usd', __('main.custody_usd_reefer_only'));
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        $response = $this->validationError($validator->errors()->first());
        throw new ValidationException($validator, $response);
    }
}
