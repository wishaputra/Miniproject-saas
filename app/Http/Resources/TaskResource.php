<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'project_id'  => $this->project_id,
            'company_id'  => $this->company_id,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'assigned_to' => $this->assigned_to,
            'created_by'  => $this->created_by,
            // whenLoaded: hanya include jika sudah di-eager load — cegah N+1
            'assignee'    => new UserResource($this->whenLoaded('assignee')),
            'creator'     => new UserResource($this->whenLoaded('creator')),
            'project'     => new ProjectResource($this->whenLoaded('project')),
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
