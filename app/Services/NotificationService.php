<?php

namespace App\Services;

use App\Mail\TicketCreatedMail;
use App\Mail\TicketRepliedMail;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendTicketCreated(Ticket $ticket): void
    {
        $recipients = $this->buildRecipients($ticket, includeOperator: true);

        foreach ($recipients as $email) {
            Mail::to($email)->send(new TicketCreatedMail($ticket));
        }
    }

    public function sendTicketReplied(Ticket $ticket, TicketMessage $message): void
    {
        $includeOperator = $message->author?->isCustomer() ?? false;
        $recipients = $this->buildRecipients($ticket, includeOperator: $includeOperator);

        foreach ($recipients as $email) {
            Mail::to($email)->send(new TicketRepliedMail($ticket, $message));
        }
    }

    /**
     * Send a test email to the given address to verify mail delivery is working.
     */
    public function sendTestEmail(Ticket $ticket, string $to): void
    {
        Mail::to($to)->send(new TicketCreatedMail($ticket));
    }

    private function buildRecipients(Ticket $ticket, bool $includeOperator = false): array
    {
        $emails = [];

        if ($ticket->contact?->email) {
            $emails[] = $ticket->contact->email;
        }

        foreach ($ticket->knowledgeEmails as $ke) {
            $emails[] = $ke->email;
        }

        if ($includeOperator && $ticket->operator?->email) {
            $emails[] = $ticket->operator->email;
        }

        return array_unique(array_filter($emails));
    }
}
