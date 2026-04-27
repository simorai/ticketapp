<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TicketMessageService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * @param  UploadedFile[]  $uploadedFiles
     */
    public function addMessage(Ticket $ticket, User $user, array $data, array $uploadedFiles = []): TicketMessage
    {
        $message = TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'body'        => $data['body'],
            'user_id'     => $user->id,
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        foreach ($uploadedFiles as $file) {
            $path = $file->store('tickets/attachments', 'public');

            TicketAttachment::create([
                'ticket_message_id' => $message->id,
                'original_name'     => $file->getClientOriginalName(),
                'path'              => $path,
                'mime_type'         => $file->getMimeType(),
                'size'              => $file->getSize(),
            ]);
        }

        $message->load('attachments');

        ActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'action'    => 'message_added',
            'payload'   => null,
        ]);

        if (!($data['is_internal'] ?? false)) {
            $ticket->load(['contact', 'knowledgeEmails', 'operator']);
            $this->notificationService->sendTicketReplied($ticket, $message);
        }

        return $message;
    }
}
