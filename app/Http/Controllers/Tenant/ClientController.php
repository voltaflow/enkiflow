<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        protected ClientService $clientService
    ) {}

    /**
     * Display a listing of the clients.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'term' => $request->get('search'),
            'status' => $request->get('status'),
            'include_archived' => $request->boolean('archived'),
        ];

        $clients = $this->clientService->search($filters, 15);

        return Inertia::render('Tenant/Clients/Index', [
            'clients' => $clients,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new client.
     */
    public function create(): Response
    {
        return Inertia::render('Tenant/Clients/Create');
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(ClientRequest $request): RedirectResponse
    {
        $client = $this->clientService->createClient($request->validated());

        return redirect()
            ->route('tenant.clients.show', $client)
            ->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client): Response
    {
        $client->load(['projects' => function ($query) {
            $query->withCount('tasks')
                  ->orderBy('created_at', 'desc');
        }]);

        $stats = $this->clientService->getClientStats($client);

        return Inertia::render('Tenant/Clients/Show', [
            'client' => $client,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Client $client): Response
    {
        return Inertia::render('Tenant/Clients/Edit', [
            'client' => $client,
        ]);
    }

    /**
     * Update the specified client in storage.
     */
    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $this->clientService->updateClient($client, $request->validated());

        return redirect()
            ->route('tenant.clients.show', $client)
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(Client $client): RedirectResponse
    {
        $this->clientService->deleteClient($client);

        return redirect()
            ->route('tenant.clients.index')
            ->with('success', 'Cliente archivado exitosamente.');
    }

    /**
     * Restore the specified client.
     */
    public function restore(Client $client): RedirectResponse
    {
        $this->clientService->restoreClient($client);

        return redirect()
            ->route('tenant.clients.show', $client)
            ->with('success', 'Cliente restaurado exitosamente.');
    }

    /**
     * Toggle the client's active status.
     */
    public function toggleStatus(Client $client): RedirectResponse
    {
        $this->clientService->toggleStatus($client);

        $status = $client->is_active ? 'activado' : 'desactivado';

        return redirect()
            ->back()
            ->with('success', "Cliente {$status} exitosamente.");
    }

    /**
     * Get clients for select dropdown (JSON response).
     */
    public function select(Request $request)
    {
        $query = Client::active()
            ->orderBy('name');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $clients = $query->get(['id', 'name', 'email']);

        return response()->json($clients);
    }
}