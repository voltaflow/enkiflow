<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionsController extends Controller
{
    /**
     * Display the permissions settings page.
     */
    public function index(Request $request): Response
    {
        $space = tenant();
        
        if (!$space) {
            abort(404, 'Space not found');
        }

        // Get all users in the space with their roles
        $users = $space->users()
            ->select('users.id', 'users.name', 'users.email', 'space_users.role', 'space_users.created_at')
            ->orderBy('users.name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                ];
            });

        return Inertia::render('Settings/Permissions', [
            'users' => $users,
            'space' => [
                'id' => $space->id,
                'name' => $space->name,
            ],
            'currentUserId' => $request->user()->id,
        ]);
    }
}