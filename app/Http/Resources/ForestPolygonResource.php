<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForestPolygonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'id' => $this->uuid,
                'type' => $this->type,
                'height_category' => $this->height_category,
                'canopy_density' => $this->canopy_density,
                'source' => $this->source,
                'area_hectares' => $this->area_hectares,
                'area_square_meters' => $this->area_square_meters,
                'centroid_latitude' => $this->centroid_latitude,
                'centroid_longitude' => $this->centroid_longitude,
                'name' => $this->name,
                'description' => $this->description,
                'confidence_level' => $this->confidence_level,
                'observation_date' => $this->observation_date?->toDateString(),
                'is_public' => $this->is_public,
                'user' => $this->whenLoaded('user', fn() => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ]),
                'organization' => $this->whenLoaded('organization', fn() => [
                    'id' => $this->organization->id,
                    'name' => $this->organization->name,
                ]),
                'verified_at' => $this->verified_at?->toISOString(),
                'verified_by' => $this->whenLoaded('verifiedBy', fn() => [
                    'id' => $this->verifiedBy->id,
                    'name' => $this->verifiedBy->name,
                ]),
                'created_at' => $this->created_at->toISOString(),
                'updated_at' => $this->updated_at->toISOString(),
                // Include additional properties if they exist
                ...$this->additional_properties ?? []
            ],
            'geometry' => $this->geometry
        ];
    }
}
