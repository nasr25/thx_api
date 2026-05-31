<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppreciationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'message'    => $this->message,
            'is_public'  => $this->is_public,
            'created_at' => $this->created_at?->toIso8601String(),
            'formatted_date' => $this->created_at?->diffForHumans(),
            'sender' => $this->whenLoaded('sender', fn () => [
                'id'             => $this->sender->id,
                'full_name'      => $this->sender->full_name,
                'full_name_ar'   => $this->sender->full_name_ar,
                'display_name'   => $this->sender->display_name,
                'profile_photo_url' => $this->sender->profile_photo_url,
                'department'     => $this->sender->department?->name,
                'department_ar'  => $this->sender->department?->name_ar,
                'job_title'      => $this->sender->job_title,
            ]),
            'receiver' => $this->whenLoaded('receiver', fn () => [
                'id'             => $this->receiver->id,
                'full_name'      => $this->receiver->full_name,
                'full_name_ar'   => $this->receiver->full_name_ar,
                'display_name'   => $this->receiver->display_name,
                'profile_photo_url' => $this->receiver->profile_photo_url,
                'department'     => $this->receiver->department?->name,
                'department_ar'  => $this->receiver->department?->name_ar,
                'job_title'      => $this->receiver->job_title,
            ]),
        ];
    }
}
