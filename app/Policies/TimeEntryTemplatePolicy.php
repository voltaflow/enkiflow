<?php

namespace App\Policies;

use App\Models\TimeEntryTemplate;
use App\Models\User;

class TimeEntryTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TimeEntryTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TimeEntryTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TimeEntryTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    /**
     * Determine whether the user can use the template.
     */
    public function use(User $user, TimeEntryTemplate $template): bool
    {
        return $user->id === $template->user_id && $template->is_active;
    }
}
