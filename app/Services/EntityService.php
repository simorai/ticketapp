<?php

namespace App\Services;

use App\Models\Entity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EntityService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Entity::query();

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('nif', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        return $query->orderBy('name')->paginate(20)->withQueryString();
    }

    public function recentTickets(Entity $entity): Collection
    {
        return $entity->tickets()
            ->with(['status', 'contact', 'operator'])
            ->withCount('messages')
            ->latest()
            ->take(20)
            ->get();
    }

    public function create(array $data): Entity
    {
        return Entity::create($data);
    }

    public function update(Entity $entity, array $data): Entity
    {
        $entity->update($data);

        return $entity->fresh();
    }

    public function delete(Entity $entity): void
    {
        $entity->delete();
    }
}
