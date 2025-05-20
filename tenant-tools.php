<?php

// Este script debe ejecutarse con: php tenant-tools.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function printSectionHeader($title) {
    echo "\n";
    echo "======================================\n";
    echo " $title \n";
    echo "======================================\n";
}

// Listar todos los dominios
function listDomains() {
    printSectionHeader("DOMINIOS REGISTRADOS");
    
    try {
        $domains = \Stancl\Tenancy\Database\Models\Domain::all();
        
        if ($domains->isEmpty()) {
            echo "No hay dominios registrados en la base de datos.\n";
        } else {
            foreach ($domains as $domain) {
                echo "- {$domain->domain} -> Tenant ID: {$domain->tenant_id}\n";
            }
        }
    } catch (\Exception $e) {
        echo "Error al listar dominios: " . $e->getMessage() . "\n";
    }
}

// Listar todos los tenants
function listTenants() {
    printSectionHeader("TENANTS (ESPACIOS) REGISTRADOS");
    
    try {
        $tenants = \App\Models\Space::all();
        
        if ($tenants->isEmpty()) {
            echo "No hay tenants registrados en la base de datos.\n";
        } else {
            foreach ($tenants as $tenant) {
                echo "- ID: {$tenant->id} - Nombre: {$tenant->name}\n";
                
                // Intentar obtener los dominios asociados a este tenant
                try {
                    $domainCount = $tenant->domains()->count();
                    if ($domainCount === 0) {
                        echo "  * No tiene dominios asociados.\n";
                    } else {
                        $tenantDomains = $tenant->domains()->get();
                        echo "  * Dominios asociados (" . $domainCount . "): " . implode(', ', $tenantDomains->pluck('domain')->toArray()) . "\n";
                    }
                } catch (\Exception $e) {
                    echo "  * Error al obtener dominios: " . $e->getMessage() . "\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "Error al listar tenants: " . $e->getMessage() . "\n";
    }
}

// Añadir un dominio a un tenant
function addDomain($tenantId, $domain) {
    printSectionHeader("AÑADIR DOMINIO");
    
    try {
        $tenant = \App\Models\Space::find($tenantId);
        
        if (!$tenant) {
            echo "Error: No se encontró un tenant con ID $tenantId\n";
            return;
        }
        
        $existingDomain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $domain)->first();
        
        if ($existingDomain) {
            echo "El dominio '$domain' ya existe y está asociado al tenant ID: {$existingDomain->tenant_id}\n";
            
            if ($existingDomain->tenant_id !== $tenant->id) {
                echo "¿Desea reasignar este dominio al tenant {$tenant->name}? (s/n): ";
                $handle = fopen("php://stdin", "r");
                $line = fgets($handle);
                
                if (trim($line) === 's') {
                    $existingDomain->tenant_id = $tenant->id;
                    $existingDomain->save();
                    echo "Dominio reasignado correctamente al tenant {$tenant->name}\n";
                } else {
                    echo "Operación cancelada.\n";
                }
                
                fclose($handle);
            }
        } else {
            // Crear el dominio directamente con el constructor de Domain
            $newDomain = new \Stancl\Tenancy\Database\Models\Domain([
                'domain' => $domain,
                'tenant_id' => $tenant->id
            ]);
            $newDomain->save();
            
            echo "Dominio '$domain' creado y asociado al tenant {$tenant->name} (ID: {$tenant->id})\n";
        }
    } catch (\Exception $e) {
        echo "Error al añadir dominio: " . $e->getMessage() . "\n";
        
        // Mostrar stack trace para debug
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

// Eliminar un dominio
function deleteDomain($domain) {
    printSectionHeader("ELIMINAR DOMINIO");
    
    try {
        $existingDomain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $domain)->first();
        
        if (!$existingDomain) {
            echo "El dominio '$domain' no existe en la base de datos.\n";
            return;
        }
        
        echo "¿Está seguro de que desea eliminar el dominio '$domain'? (s/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        
        if (trim($line) === 's') {
            $existingDomain->delete();
            echo "Dominio '$domain' eliminado correctamente.\n";
        } else {
            echo "Operación cancelada.\n";
        }
        
        fclose($handle);
    } catch (\Exception $e) {
        echo "Error al eliminar dominio: " . $e->getMessage() . "\n";
    }
}

// Crear un tenant de prueba
function createTestTenant($name) {
    printSectionHeader("CREAR TENANT DE PRUEBA");
    
    try {
        // Verificar si ya existe un tenant con ese nombre
        $existingTenant = \App\Models\Space::where('name', $name)->first();
        
        if ($existingTenant) {
            echo "Ya existe un tenant con el nombre '$name'.\n";
            return;
        }
        
        // Obtener el primer usuario (para asignarlo como propietario)
        $owner = \App\Models\User::first();
        
        if (!$owner) {
            echo "Error: No se encontró ningún usuario para asignar como propietario.\n";
            return;
        }
        
        // Crear el tenant
        $tenant = \App\Models\Space::create([
            'id' => \Stancl\Tenancy\Tenancy::getTenantKeyName() === 'id' ? \Illuminate\Support\Str::uuid()->toString() : null,
            'name' => $name,
            'owner_id' => $owner->id,
            'data' => [
                'plan' => 'free',
            ],
        ]);
        
        echo "Tenant '$name' creado correctamente con ID: {$tenant->id}\n";
        echo "Propietario: {$owner->name} (ID: {$owner->id})\n";
        
    } catch (\Exception $e) {
        echo "Error al crear tenant: " . $e->getMessage() . "\n";
        
        // Mostrar stack trace para debug
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

// Menú principal
printSectionHeader("HERRAMIENTA DE GESTIÓN DE TENANTS");
echo "1. Listar dominios\n";
echo "2. Listar tenants\n";
echo "3. Añadir dominio a tenant\n";
echo "4. Eliminar dominio\n";
echo "5. Crear tenant de prueba\n";
echo "6. Añadir dominio 'enkiflow.test' al primer tenant\n";
echo "0. Salir\n\n";

echo "Seleccione una opción: ";
$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));

switch ($choice) {
    case '1':
        listDomains();
        break;
        
    case '2':
        listTenants();
        break;
        
    case '3':
        echo "Ingrese el ID del tenant: ";
        $tenantId = trim(fgets($handle));
        
        echo "Ingrese el dominio a añadir: ";
        $domain = trim(fgets($handle));
        
        addDomain($tenantId, $domain);
        break;
        
    case '4':
        echo "Ingrese el dominio a eliminar: ";
        $domain = trim(fgets($handle));
        
        deleteDomain($domain);
        break;
        
    case '5':
        echo "Ingrese el nombre del nuevo tenant: ";
        $name = trim(fgets($handle));
        
        createTestTenant($name);
        break;
        
    case '6':
        $tenant = \App\Models\Space::first();
        
        if (!$tenant) {
            echo "Error: No se encontró ningún tenant en la base de datos.\n";
        } else {
            addDomain($tenant->id, 'enkiflow.test');
        }
        break;
        
    case '0':
        echo "Saliendo...\n";
        break;
        
    default:
        echo "Opción no válida.\n";
        break;
}

fclose($handle);