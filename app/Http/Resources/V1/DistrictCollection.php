<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DistrictCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => 200,
            'data' => $this->collection,
        ];
    }
    public function paginationInformation($request, $paginated, $default)
    {
        unset($default['links']);
        unset($default['meta']['links']);
        unset($default['meta']['path']);
        return $default;
    }
}
