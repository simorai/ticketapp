<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\Ticket;
use App\Models\User;

class DashboardService
{
    public function stats(User $user): array
    {
        return [
            'total_tickets'  => Ticket::forUser($user)->count(),
            'open_tickets'   => Ticket::forUser($user)
                ->whereHas('status', fn ($q) => $q->where('is_closed', false))
                ->count(),
            'closed_tickets' => Ticket::forUser($user)
                ->whereHas('status', fn ($q) => $q->where('is_closed', true))
                ->count(),
            'entities'       => $user->isOperator() ? Entity::count() : null,
            'contacts'       => $user->isOperator() ? Contact::count() : null,
        ];
    }
}
