<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
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
            'owner' => new UserResource($this->getCachedOwner()),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'team' => new TeamResource($this->whenLoaded('team')),
            'languages' => LanguageResource::collection($this->whenLoaded('languages')),
            'keys' => KeyResource::collection($this->whenLoaded('keys')),
            'values' => ValueResource::collection($this->whenLoaded('values')),
            'hooks' => HookResource::collection($this->whenLoaded('hooks')),
            'setting' => new SettingResource($this->whenLoaded('setting')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
