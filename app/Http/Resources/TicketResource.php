<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'number'           => $this->number,
            'subject'          => $this->subject,
            'inbox'            => $this->whenLoaded('inbox', fn () => new InboxResource($this->inbox)),
            'type'             => $this->whenLoaded('type', fn () => $this->type ? new TicketTypeResource($this->type) : null),
            'status'           => $this->whenLoaded('status', fn () => $this->status ? new TicketStatusResource($this->status) : null),
            'operator'         => $this->whenLoaded('operator', fn () => $this->operator ? ['id' => $this->operator->id, 'name' => $this->operator->name] : null),
            'entity'           => $this->whenLoaded('entity', fn () => $this->entity ? ['id' => $this->entity->id, 'name' => $this->entity->name] : null),
            'contact'          => $this->whenLoaded('contact', fn () => $this->contact ? ['id' => $this->contact->id, 'name' => $this->contact->name, 'email' => $this->contact->email] : null),
            'knowledge_emails' => $this->whenLoaded('knowledgeEmails', fn () => $this->knowledgeEmails->pluck('email')),
            'messages'         => TicketMessageResource::collection($this->whenLoaded('messages')),
            'activity_logs'    => ActivityLogResource::collection($this->whenLoaded('activityLogs')),
            'messages_count'   => $this->whenCounted('messages'),
            'created_by'       => $this->whenLoaded('createdBy', fn () => $this->createdBy ? ['id' => $this->createdBy->id, 'name' => $this->createdBy->name] : null),
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
