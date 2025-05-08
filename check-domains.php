<?php

// Este script debe ejecutarse con: php check-domains.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Verificar si hay dominios configurados
echo "=== Dominios configurados ===\n";
$domains = \Stancl\Tenancy\Database\Models\Domain::all();

if ($domains->isEmpty()) {
    echo "No hay dominios configurados en la base de datos.\n";
} else {
    foreach ($domains as $domain) {
        echo "Dominio: {$domain->domain} -> Tenant ID: {$domain->tenant_id}\n";
    }
}

// Verificar si hay tenants configurados
echo "\n=== Tenants (Spaces) configurados ===\n";
$tenants = \App\Models\Space::all();

if ($tenants->isEmpty()) {
    echo "No hay tenants (spaces) configurados en la base de datos.\n";
} else {
    foreach ($tenants as $tenant) {
        echo "Tenant ID: {$tenant->id} - Nombre: {$tenant->name}\n";
        
        // Intentar obtener los dominios asociados a este tenant
        try {
            $tenantDomains = $tenant->domains()->get();
            if ($tenantDomains->isEmpty()) {
                echo "  * No tiene dominios asociados.\n";
            } else {
                echo "  * Dominios asociados: " . implode(', ', $tenantDomains->pluck('domain')->toArray()) . "\n";
            }
        } catch (Exception $e) {
            echo "  * Error al obtener dominios: " . $e->getMessage() . "\n";
        }
    }
}

// Agregar dominio de prueba si no existe
echo "\n=== Creación de dominio de prueba ===\n";
try {
    // Verificar si hay al menos un tenant
    if ($tenants->isNotEmpty()) {
        $tenant = $tenants->first();
        
        // Verificar si ya existe el dominio enkiflow.test
        $existingDomain = \Stancl\Tenancy\Database\Models\Domain::where('domain', 'enkiflow.test')->first();
        
        if ($existingDomain) {
            echo "El dominio 'enkiflow.test' ya existe y está asociado al tenant ID: {$existingDomain->tenant_id}\n";
        } else {
            // Crear el dominio
            $domain = \Stancl\Tenancy\Database\Models\Domain::create([
                'domain' => 'enkiflow.test',
                'tenant_id' => $tenant->id,
            ]);
            
            echo "Se ha creado el dominio 'enkiflow.test' y se ha asociado al tenant ID: {$tenant->id}\n";
        }
    } else {
        echo "No se puede crear el dominio de prueba porque no hay tenants disponibles.\n";
    }
} catch (Exception $e) {
    echo "Error al crear dominio de prueba: " . $e->getMessage() . "\n";
}