<?php

namespace App\Http\Resources\Api\Superagent;

use App\Http\Resources\Api\Agent\AgentResource;
use App\Http\Resources\Api\Superagent\SuperagentResource;
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
        return [
            'superagent'      => new SuperagentResource($this->superagent),
            'expire_at' => $this->expire_at,
        ];
    }
}
