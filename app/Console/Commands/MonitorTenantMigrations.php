<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class MonitorTenantMigrations extends Command
{
    protected $signature   = 'tenants:migrations-monitor {--watch}';
    protected $description = 'Muestra el progreso de migraciones asíncronas';

    public function handle(): int
    {
        $this->option('watch') ? $this->loop() : $this->printStatus();
        return 0;
    }

    private function loop(): void
    {
        while (true) {
            $this->printStatus();
            sleep(5);
            $this->newLine(2);
        }
    }

    private function printStatus(): void
    {
        $rows = DB::table('tenant_migration_states')
            ->select('tenant_id','status',DB::raw('count(*) as total'))
            ->groupBy('tenant_id','status')
            ->get()
            ->groupBy('tenant_id');

        $table = $rows->map(fn($g,$id)=>[
            'Tenant'=>$id,
            'Pendientes'=>$g->firstWhere('status','pending')->total??0,
            'Migradas'=>$g->firstWhere('status','migrated')->total??0,
            'Fallidas'=>$g->firstWhere('status','failed')->total??0,
        ])->values()->toArray();

        $this->table(['Tenant','Pendientes','Migradas','Fallidas'],$table);
        $this->info('Jobs en cola: '.Queue::size('tenant-migrations'));
    }
}