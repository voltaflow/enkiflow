<?php

// Script para añadir el dominio enkiflow.test al primer tenant disponible
// Ejecutar con: php add-domain.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Verificar si el dominio ya existe
$domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', 'enkiflow.test')->first();

if (!$domain) {
    // Obtener el primer tenant
    $tenant = \App\Models\Space::first();
    
    if ($tenant) {
        // Crear el dominio y asociarlo al tenant
        $domain = new \Stancl\Tenancy\Database\Models\Domain;
        $domain->domain = 'enkiflow.test';
        $domain->tenant_id = $tenant->id;
        $domain->save();
        
        echo "Dominio 'enkiflow.test' creado y asociado al tenant {$tenant->id}\n";
    } else {
        echo "No hay tenants disponibles para asociar al dominio\n";
    }
} else {
    echo "El dominio 'enkiflow.test' ya existe y está asociado al tenant {$domain->tenant_id}\n";
}

// Listar todos los dominios para verificar
echo "\nLista de dominios en la base de datos:\n";
$domains = \Stancl\Tenancy\Database\Models\Domain::all();
foreach ($domains as $d) {
    echo "- {$d->domain} => {$d->tenant_id}\n";
}