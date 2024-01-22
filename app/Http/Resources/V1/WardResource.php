<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\DateResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WardResource extends JsonResource
{
    public $preserveKeys = true;
    public function toArray(Request $request): array
    {
        return [
            $this->code => $this->title,
            'district' => DistrictResource::collection($this->whenLoaded('district')),
            'created_at' => new DateResource($this->created_at),
            'updated_at' => new DateResource($this->updated_at),
        ];
    }
}
