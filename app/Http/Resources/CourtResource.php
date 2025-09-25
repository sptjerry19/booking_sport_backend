<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourtResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'venue_id' => $this->venue_id,
            'sport_id' => $this->sport_id,
            'name' => $this->name,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'hourly_rate' => $this->hourly_rate,
            'status' => $this->status,
            'sport' => new SportResource($this->whenLoaded('sport')),
            'venue' => new VenueResource($this->whenLoaded('venue')),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
