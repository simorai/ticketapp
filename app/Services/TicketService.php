<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TicketService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Ticket::with(['inbox', 'status', 'type', 'operator', 'entity', 'contact'])
            ->withCount('messages')
            ->forUser($user);

        if (!empty($filters['inbox_id'])) {
            $query->where('inbox_id', $filters['inbox_id']);
        }

        if (!empty($filters['ticket_status_id'])) {
            $query->where('ticket_status_id', $filters['ticket_status_id']);
        }

        if (!empty($filters['operator_id'])) {
            $query->where('operator_id', $filters['operator_id']);
        }

        if (!empty($filters['ticket_type_id'])) {
            $query->where('ticket_type_id', $filters['ticket_type_id']);
        }

        if (!empty($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', $search)
                  ->orWhere('subject', 'like', $search)
                  ->orWhereHas('entity', fn ($r) => $r->where('name', 'like', $search))
                  ->orWhereHas('contact', fn ($r) => $r->where('email', 'like', $search));
            });
        }

        return $query->orderByDesc('created_at')->paginate(20)->withQueryString();
    }

    public function create(User $user, array $data): Ticket
    {
        $defaultStatus = TicketStatus::default()->firstOrFail();

        // For customer users, auto-resolve contact and entity from their profile
        if (!$user->isOperator() && empty($data['contact_id'])) {
            $contact = $user->contact()->with('entities')->first();
            $data['contact_id'] = $contact?->id;
            if (empty($data['entity_id'])) {
                $data['entity_id'] = $contact?->entities->first()?->id;
            }
        }

        // For all users: if entity_id is still empty but contact_id is set, derive entity from contact
        if (empty($data['entity_id']) && !empty($data['contact_id'])) {
            $contact = Contact::with('entities')->find($data['contact_id']);
            $data['entity_id'] = $contact?->entities->first()?->id;
        }

        $ticket = Ticket::create([
            'number'           => Ticket::generateNumber(),
            'subject'          => $data['subject'],
            'inbox_id'         => $data['inbox_id'],
            'ticket_type_id'   => $data['ticket_type_id'] ?? null,
            'ticket_status_id' => $defaultStatus->id,
            'operator_id'      => $data['operator_id'] ?? null,
            'entity_id'        => $data['entity_id'] ?? null,
            'contact_id'       => $data['contact_id'],
            'created_by'       => $user->id,
        ]);

        if (!empty($data['knowledge_emails'])) {
            foreach ($data['knowledge_emails'] as $email) {
                $ticket->knowledgeEmails()->create(['email' => $email]);
            }
        }

        ActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'action'    => 'created',
            'payload'   => null,
        ]);

        // Create the initial message (no reply notification — creation notification covers this)
        if (!empty($data['message'])) {
            $ticketMessage = TicketMessage::create([
                'ticket_id'   => $ticket->id,
                'body'        => $data['message'],
                'user_id'     => $user->id,
                'is_internal' => false,
            ]);

            foreach (($data['attachments'] ?? []) as $file) {
                $path = $file->store('tickets/attachments', 'public');
                TicketAttachment::create([
                    'ticket_message_id' => $ticketMessage->id,
                    'original_name'     => $file->getClientOriginalName(),
                    'path'              => $path,
                    'mime_type'         => $file->getMimeType(),
                    'size'              => $file->getSize(),
                ]);
            }
        }

        $ticket->load(['inbox', 'status', 'type', 'operator', 'entity', 'contact', 'knowledgeEmails']);

        $this->notificationService->sendTicketCreated($ticket);

        return $ticket;
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update(array_filter([
            'subject'        => $data['subject'] ?? null,
            'ticket_type_id' => array_key_exists('ticket_type_id', $data) ? $data['ticket_type_id'] : $ticket->ticket_type_id,
            'entity_id'      => array_key_exists('entity_id', $data) ? $data['entity_id'] : $ticket->entity_id,
        ], fn ($v) => $v !== null));

        if (array_key_exists('knowledge_emails', $data)) {
            $ticket->knowledgeEmails()->delete();
            foreach (($data['knowledge_emails'] ?? []) as $email) {
                $ticket->knowledgeEmails()->create(['email' => $email]);
            }
        }

        return $ticket->fresh();
    }

    public function assignOperator(Ticket $ticket, ?int $operatorId, User $actor): Ticket
    {
        $ticket->update(['operator_id' => $operatorId]);

        ActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $actor->id,
            'action'    => 'operator_assigned',
            'payload'   => ['user_name' => $operatorId ? optional(User::find($operatorId))->name : null],
        ]);

        return $ticket->fresh(['operator']);
    }

    public function changeStatus(Ticket $ticket, int $statusId, User $actor): Ticket
    {
        $oldStatus = $ticket->status->name;
        $ticket->update(['ticket_status_id' => $statusId]);
        $ticket->load('status');

        ActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $actor->id,
            'action'    => 'status_changed',
            'payload'   => ['from' => $oldStatus, 'to' => $ticket->status->name],
        ]);

        return $ticket;
    }

    public function delete(Ticket $ticket): void
    {
        $ticket->delete();
    }

    public function allStatuses(): Collection
    {
        return TicketStatus::orderBy('sort_order')->get();
    }

    public function allTypes(): Collection
    {
        return TicketType::orderBy('name')->get();
    }

    public function createType(array $data): TicketType
    {
        return TicketType::create($data);
    }

    public function updateType(TicketType $ticketType, array $data): TicketType
    {
        $ticketType->update($data);

        return $ticketType->fresh();
    }

    public function deleteType(TicketType $ticketType): void
    {
        $ticketType->delete();
    }

    public function createStatus(array $data): TicketStatus
    {
        return TicketStatus::create($data);
    }

    public function updateStatus(TicketStatus $ticketStatus, array $data): TicketStatus
    {
        $ticketStatus->update($data);

        return $ticketStatus->fresh();
    }

    public function deleteStatus(TicketStatus $ticketStatus): void
    {
        $ticketStatus->delete();
    }
}
