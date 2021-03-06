<?php

namespace App\Http\Resources;

use App\Models\Key;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Key
 */
class KeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'values' => ValueResource::collection($this->whenLoaded('values')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
