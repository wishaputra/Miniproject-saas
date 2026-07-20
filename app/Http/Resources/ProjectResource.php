<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'company_id'  => $this->company_id,
            'created_by'  => $this->created_by,
            // whenLoaded: hanya include relasi jika sudah di-eager load (with())
            // Mencegah lazy load N+1 yang tidak disengaja
            'creator'     => new UserResource($this->whenLoaded('creator')),
            'tasks_count' => $this->whenLoaded('tasks', fn () => $this->tasks->count()),
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
