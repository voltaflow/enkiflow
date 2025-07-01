<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientService
{
    /**
     * Get all clients.
     */
    public function getAllClients(): Collection
    {
        return Client::with(['projects' => function ($query) {
            $query->select('id', 'client_id', 'name', 'status');
        }])
        ->orderBy('name')
        ->get();
    }

    /**
     * Get active clients with cache.
     */
    public function getActiveClients(): Collection
    {
        return Cache::remember('active_clients_' . tenant('id'), 900, function () {
            return Client::active()
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        });
    }

    /**
     * Search and filter clients.
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Client::query()
            ->with(['projects' => function ($query) {
                $query->select('id', 'client_id', 'name', 'status');
            }]);

        // Search by term
        if (!empty($filters['term'])) {
            $term = $filters['term'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%")
                  ->orWhere('phone', 'LIKE', "%{$term}%");
            });
        }

        // Filter by status
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->inactive();
            }
        }

        // Include trashed if requested
        if (!empty($filters['include_archived'])) {
            $query->withTrashed();
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Get a client by ID with relationships.
     */
    public function getClientById(int $id): ?Client
    {
        return Client::with([
            'projects' => function ($query) {
                $query->withCount('tasks')
                      ->orderBy('created_at', 'desc');
            }
        ])->find($id);
    }

    /**
     * Get a client by slug.
     */
    public function getClientBySlug(string $slug): ?Client
    {
        return Client::where('slug', $slug)->first();
    }

    /**
     * Create a new client.
     */
    public function createClient(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            $client = Client::create($data);

            // Clear cache
            $this->clearCache();

            // Log activity
            Log::info('Client created', ['client_id' => $client->id, 'name' => $client->name]);

            return $client;
        });
    }

    /**
     * Update a client.
     */
    public function updateClient(Client $client, array $data): Client
    {
        return DB::transaction(function () use ($client, $data) {
            $client->update($data);

            // Clear cache
            $this->clearCache();

            // Log activity
            Log::info('Client updated', ['client_id' => $client->id, 'name' => $client->name]);

            return $client->fresh();
        });
    }

    /**
     * Toggle client active status.
     */
    public function toggleStatus(Client $client): Client
    {
        $client->is_active = !$client->is_active;
        $client->save();

        // Clear cache
        $this->clearCache();

        // Log activity
        Log::info('Client status toggled', [
            'client_id' => $client->id,
            'is_active' => $client->is_active
        ]);

        return $client;
    }

    /**
     * Delete a client (soft delete).
     */
    public function deleteClient(Client $client): bool
    {
        return DB::transaction(function () use ($client) {
            // Log activity before deletion
            Log::info('Client archived', ['client_id' => $client->id, 'name' => $client->name]);

            $result = $client->delete();

            // Clear cache
            $this->clearCache();

            return $result;
        });
    }

    /**
     * Restore a deleted client.
     */
    public function restoreClient(Client $client): Client
    {
        $client->restore();

        // Clear cache
        $this->clearCache();

        // Log activity
        Log::info('Client restored', ['client_id' => $client->id, 'name' => $client->name]);

        return $client;
    }

    /**
     * Force delete a client.
     */
    public function forceDeleteClient(Client $client): bool
    {
        return DB::transaction(function () use ($client) {
            // Log activity before deletion
            Log::warning('Client permanently deleted', [
                'client_id' => $client->id,
                'name' => $client->name
            ]);

            // Remove associations
            $client->projects()->update(['client_id' => null]);

            return $client->forceDelete();
        });
    }

    /**
     * Get client statistics.
     */
    public function getClientStats(Client $client): array
    {
        $projects = $client->projects();
        $timeEntries = $client->timeEntries();

        return [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->where('status', 'active')->count(),
            'total_hours' => $timeEntries->sum('duration') / 3600,
            'billable_hours' => $timeEntries->where('is_billable', true)->sum('duration') / 3600,
            'total_time_entries' => $timeEntries->count(),
            'last_activity' => $timeEntries->latest('started_at')->value('started_at'),
        ];
    }

    /**
     * Clear client-related cache.
     */
    protected function clearCache(): void
    {
        Cache::forget('active_clients_' . tenant('id'));
    }
}