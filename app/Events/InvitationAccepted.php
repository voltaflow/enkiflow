<?php

namespace App\Events;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationAccepted
{
    use Dispatchable, SerializesModels;

    public $invitation;
    public $user;
    public $userCreated;

    public function __construct(Invitation $invitation, User $user, bool $userCreated)
    {
        $this->invitation = $invitation;
        $this->user = $user;
        $this->userCreated = $userCreated;
    }
}