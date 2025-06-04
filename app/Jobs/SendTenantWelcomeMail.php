<?php

namespace App\Jobs;

use App\Models\Space;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTenantWelcomeMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Space $tenant
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Por ahora solo registramos en log
            // En producción, aquí enviarías el email real
            Log::info('Enviando email de bienvenida para tenant', [
                'tenant_id' => $this->tenant->id,
                'tenant_name' => $this->tenant->name,
                'owner_email' => $this->tenant->owner->email,
            ]);
            
            // Ejemplo de envío de email (descomentar cuando se configure Mail)
            /*
            Mail::to($this->tenant->owner->email)
                ->send(new \App\Mail\TenantWelcome($this->tenant));
            */
        } catch (\Exception $e) {
            Log::error('Error al enviar email de bienvenida', [
                'tenant_id' => $this->tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}