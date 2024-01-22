<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
{
    public $preserveKeys = true;
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'code' => $this->code,
            $this->code => $this->title,
            'district' => DistrictResource::collection($this->whenLoaded('district')),
        ];
    }
}
