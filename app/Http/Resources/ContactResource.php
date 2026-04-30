<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'mobile'         => $this->mobile,
            'role'           => $this->whenLoaded('role', fn () => $this->role ? ['id' => $this->role->id, 'name' => $this->role->name] : null),
            'entities'       => $this->whenLoaded('entities', fn () => $this->entities->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])),
            'internal_notes' => $this->when($request->user()?->isOperator(), $this->internal_notes),
        ];
    }
}
