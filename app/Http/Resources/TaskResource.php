<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->toDateTimeString(),

            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'name' => $this->location_name,
            ],

            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],

            'assignee' => $this->when($this->assignee_id, [
                'id' => $this->assignee?->id,
                'name' => $this->assignee?->name,
            ]),

            'subtasks' => TaskSubtaskResource::collection($this->whenLoaded('subtasks')),

            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}