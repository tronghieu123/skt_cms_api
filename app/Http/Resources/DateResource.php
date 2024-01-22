<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Date;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read CarbonInterface $resource
 */
final class DateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Convert UTCDateTime instances.
        if (is_a($this->resource, "MongoDB\BSON\UTCDateTime")) {
            $date           = $this->resource->toDateTime();
            $seconds        = $date->format('U');
            $milliseconds   = abs((int) $date->format('v'));
            $timestampMs    = sprintf('%d%03d', $seconds, $milliseconds);
            $this->resource = Date::createFromTimestampMs($timestampMs);
        }
        return [
            'human' => $this->resource->diffForHumans(),
            'timestamp' => $this->resource->timestamp,
            'string' => $this->resource->toDateTimeString(),
        ];
    }
}
