<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContactService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Contact::with(['role', 'entities']);

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        if (!empty($filters['entity_id'])) {
            $query->whereHas('entities', fn ($q) => $q->where('entities.id', $filters['entity_id']));
        }

        return $query->orderBy('name')->paginate(20)->withQueryString();
    }

    public function create(array $data, array $entityIds = []): Contact
    {
        $contact = Contact::create($data);

        if (!empty($entityIds)) {
            $contact->entities()->sync($entityIds);
        }

        return $contact->load(['role', 'entities']);
    }

    public function update(Contact $contact, array $data, array $entityIds = []): Contact
    {
        $contact->update($data);
        $contact->entities()->sync($entityIds);

        return $contact->fresh(['role', 'entities']);
    }

    public function delete(Contact $contact): void
    {
        $contact->delete();
    }
}
