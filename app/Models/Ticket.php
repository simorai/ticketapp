<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'number',
        'subject',
        'inbox_id',
        'ticket_type_id',
        'ticket_status_id',
        'operator_id',
        'entity_id',
        'contact_id',
        'created_by',
    ];

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function knowledgeEmails(): HasMany
    {
        return $this->hasMany(TicketKnowledgeEmail::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->orderBy('created_at');
    }

    public static function generateNumber(): string
    {
        $max = static::withTrashed()->max('id') ?? 0;

        return 'TC-' . ($max + 1);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isCustomer()) {
            $contact   = $user->contact()->with('entities')->first();
            $entityIds = $contact ? $contact->entities->pluck('id') : [];
            $contactId = $contact?->id;

            return $query->where(function (Builder $q) use ($entityIds, $contactId) {
                $q->whereIn('entity_id', $entityIds);
                if ($contactId) {
                    $q->orWhere('contact_id', $contactId);
                }
            });
        }

        // Operators only see tickets from inboxes they are assigned to
        return $query->whereHas('inbox', fn ($q) =>
            $q->whereHas('operators', fn ($q2) =>
                $q2->where('users.id', $user->id)
            )
        );
    }
}
