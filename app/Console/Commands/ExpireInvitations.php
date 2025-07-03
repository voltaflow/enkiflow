<?php

namespace App\Console\Commands;

use App\Services\InvitationService;
use Illuminate\Console\Command;

class ExpireInvitations extends Command
{
    protected $signature = 'invitations:expire';
    protected $description = 'Expire invitations that have passed their expiration date';

    protected $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        parent::__construct();
        $this->invitationService = $invitationService;
    }

    public function handle()
    {
        $count = $this->invitationService->expireOldInvitations();
        $this->info("{$count} invitaciones han sido marcadas como expiradas.");
        
        return Command::SUCCESS;
    }
}