<?php
namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class OtpResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // تحقق أولاً من الـ company أو الـ employee
        if ($this->company) {
            return [
                'company' => new CompanyResource($this->company),
                'expire_at' => $this->expire_at,
            ];
        } elseif ($this->employee) {
            return [
                'employee' => new EmployeeResource($this->employee),
                'expire_at' => $this->expire_at,
            ];
        }

        // إذا لم يكن هناك either company or employee، نرجع null أو رسالة خطأ
        return [
            'error' => 'No associated company or employee found',
        ];
    }
}
