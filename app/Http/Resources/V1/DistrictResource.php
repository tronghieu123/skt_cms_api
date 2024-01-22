<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistrictResource extends JsonResource
{
    public $preserveKeys = true;
    public function toArray(Request $request): array
    {
        return [
            $this->code => $this->title,
            'province' => new ProvinceResource($this->whenLoaded('province')),
            'ward' => WardResource::collection($this->whenLoaded('ward')),
        ];
    }
}
