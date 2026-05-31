<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'action'      => $this->action,
            'description' => $this->description,
            'ip_address'  => $this->ip_address,
            'properties'  => $this->properties,
            'created_at'  => $this->created_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id'        => $this->user->id,
                'full_name' => $this->user->full_name,
                'username'  => $this->user->username,
            ] : null),
        ];
    }
}
