<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppreciationReasonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'name_ar'      => $this->name_ar,
            'display_name' => $this->display_name,
            'is_active'    => $this->is_active,
            'sort_order'   => $this->sort_order,
        ];
    }
}
