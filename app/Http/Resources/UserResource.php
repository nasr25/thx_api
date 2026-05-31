<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'username'             => $this->username,
            'email'                => $this->email,
            'full_name'            => $this->full_name,
            'full_name_ar'         => $this->full_name_ar,
            'display_name'         => $this->display_name,
            'job_title'            => $this->job_title,
            'job_title_ar'         => $this->job_title_ar,
            'profile_photo_url'    => $this->profile_photo_url,
            'preferred_language'   => $this->preferred_language,
            'is_active'            => $this->is_active,
            'last_login_at'        => $this->last_login_at?->toIso8601String(),
            'department'           => $this->whenLoaded('department', fn () => [
                'id'      => $this->department->id,
                'name'    => $this->department->name,
                'name_ar' => $this->department->name_ar,
            ]),
            'roles'                => $this->whenLoaded('roles', fn () => $this->getRoleNames()),
            'total_appreciations'  => $this->when(
                isset($this->received_appreciations_count),
                $this->received_appreciations_count ?? 0
            ),
            'appreciation_count'   => $this->when(
                isset($this->appreciation_count),
                $this->appreciation_count ?? null
            ),
            'monthly_limit'        => $this->when(
                $request->user() && $request->user()->id === $this->id,
                fn () => $this->getMonthlyLimit()
            ),
            'monthly_remaining'    => $this->when(
                $request->user() && $request->user()->id === $this->id,
                fn () => max(0, $this->getMonthlyLimit() - $this->getMonthlyAppreciationsSentCount())
            ),
            'created_at'           => $this->created_at?->toIso8601String(),
        ];
    }
}
