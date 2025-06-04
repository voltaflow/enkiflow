<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SpaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $ownedSpaces = Auth::user()->ownedSpaces()->with(['users' => function ($query) {
            $query->select('users.id', 'name', 'email');
        }])->get();

        $memberSpaces = Auth::user()
            ->spaces()
            ->wherePivot('role', '!=', 'admin')
            ->with(['owner:id,name,email'])
            ->get();

        return Inertia::render('Spaces/Index', [
            'owned_spaces' => $ownedSpaces,
            'member_spaces' => $memberSpaces,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Spaces/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:domains,domain'],
        ]);

        // Generate a slug for the space ID
        $id = Str::slug($request->name);

        // Check if ID exists and make it unique if needed
        $count = 1;
        $originalId = $id;
        while (Space::find($id)) {
            $id = $originalId.'-'.$count++;
        }

        // Generate a unique slug (could be different from ID if needed)
        $slug = Space::generateSubdomain($request->name);

        // Create the tenant (space)
        $space = Space::create([
            'id' => $id,
            'name' => $request->name,
            'slug' => $slug, // Use generated subdomain as slug
            'owner_id' => Auth::id(),
            'data' => [
                'plan' => 'free', // Default plan
            ],
        ]);

        // Create a domain if provided
        if ($request->domain) {
            $space->domains()->create([
                'domain' => $request->domain,
            ]);
        }

        // Add the owner as an admin of the space
        $space->users()->attach(Auth::id(), ['role' => 'admin']);

        return redirect()->route('spaces.show', $space->id)
            ->with('success', 'Space created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): Response
    {
        $space = Space::with(['users', 'owner:id,name,email', 'domains'])
            ->findOrFail($id);

        // Check if user has access to this space
        $this->authorize('view', $space);

        return Inertia::render('Spaces/Show', [
            'space' => $space,
            'is_owner' => Auth::id() === $space->owner_id,
            'member_count' => $space->users->count(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): Response
    {
        $space = Space::with('domains')->findOrFail($id);

        // Check if the user can edit this space
        $this->authorize('update', $space);

        return Inertia::render('Spaces/Edit', [
            'space' => $space,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $space = Space::findOrFail($id);

        // Check if the user can update this space
        $this->authorize('update', $space);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $space->update([
            'name' => $request->name,
        ]);

        return redirect()->route('spaces.show', $space->id)
            ->with('success', 'Space updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        $space = Space::findOrFail($id);

        // Check if the user can delete this space
        $this->authorize('delete', $space);

        // Delete the space
        $space->delete();

        return redirect()->route('spaces.index')
            ->with('success', 'Space deleted successfully.');
    }

    /**
     * Invite a user to the space.
     */
    public function invite(Request $request, string $id): RedirectResponse
    {
        $space = Space::findOrFail($id);

        // Check if the user can invite others to this space
        $this->authorize('invite', $space);

        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ]);

        $user = User::where('email', $request->email)->first();

        // Check if user is already a member
        if ($space->users()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'User is already a member of this space.');
        }

        // Add user to the space
        $space->users()->attach($user->id, ['role' => $request->role]);

        // Update subscription quantity if needed
        $space->syncMemberCount();

        return back()->with('success', 'User invited successfully.');
    }

    /**
     * Remove a user from the space.
     */
    public function removeUser(string $spaceId, string $userId): RedirectResponse
    {
        $space = Space::findOrFail($spaceId);

        // Check if the user can remove others from this space
        $this->authorize('removeUser', $space);

        // Cannot remove the owner
        if ($space->owner_id == $userId) {
            return back()->with('error', 'Cannot remove the owner of the space.');
        }

        // Remove the user
        $space->users()->detach($userId);

        // Update subscription quantity
        $space->syncMemberCount();

        return back()->with('success', 'User removed successfully.');
    }
}
