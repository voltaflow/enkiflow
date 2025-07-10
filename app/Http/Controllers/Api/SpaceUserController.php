<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpaceUserController extends Controller
{
    /**
     * Update user's role in a space.
     */
    public function updateRole(Request $request, Space $space, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['owner', 'admin', 'manager', 'member', 'viewer'])],
        ]);

        // Check if user is in the space
        $spaceUser = $space->users()->where('users.id', $user->id)->first();
        
        if (!$spaceUser) {
            return response()->json([
                'message' => 'User is not a member of this space'
            ], 404);
        }

        // Prevent changing owner role
        if ($spaceUser->pivot->role === 'owner' || $validated['role'] === 'owner') {
            return response()->json([
                'message' => 'Cannot change owner role'
            ], 422);
        }

        // Update role
        $space->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => [
                'user_id' => $user->id,
                'space_id' => $space->id,
                'role' => $validated['role'],
            ]
        ]);
    }
}