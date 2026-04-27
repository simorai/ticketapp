<?php

namespace App\Services;

use App\Models\Inbox;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class InboxService
{
    public function all(): Collection
    {
        return Inbox::withCount('operators')->orderBy('name')->get();
    }

    public function allSimple(): Collection
    {
        return Inbox::orderBy('name')->get(['id', 'name', 'slug', 'description']);
    }

    public function allOperators(): Collection
    {
        return User::where('role', 'operator')->orderBy('name')->get(['id', 'name']);
    }

    public function create(array $data): Inbox
    {
        return Inbox::create($data);
    }

    public function update(Inbox $inbox, array $data): Inbox
    {
        $inbox->update($data);

        return $inbox->fresh();
    }

    public function delete(Inbox $inbox): void
    {
        $inbox->delete();
    }

    public function syncOperators(Inbox $inbox, array $userIds): void
    {
        $inbox->operators()->sync($userIds);
    }
}
