<?php

namespace App\Console\Commands;

use App\Services\InvitationService;
use Illuminate\Console\Command;

class PruneInvitations extends Command
{
    protected $signature = 'invitations:prune';
    protected $description = 'Prune old expired or revoked invitations (GDPR compliance)';

    protected $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        parent::__construct();
        $this->invitationService = $invitationService;
    }

    public function handle()
    {
        $count = $this->invitationService->pruneOldInvitations();
        $this->info("{$count} invitaciones antiguas han sido eliminadas permanentemente.");
        
        return Command::SUCCESS;
    }
}