<?php

namespace App\Events;

use App\Models\Invitation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationViewed
{
    use Dispatchable, SerializesModels;

    public $invitation;
    public $ipAddress;

    public function __construct(Invitation $invitation, ?string $ipAddress = null)
    {
        $this->invitation = $invitation;
        $this->ipAddress = $ipAddress;
    }
}