<?php

// Este script debe ejecutarse con: php tenant-subdomain-helper.php
// Ayuda a crear y gestionar subdominios para tenants

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function printSectionHeader($title) {
    echo "\n";
    echo "======================================\n";
    echo " $title \n";
    echo "======================================\n";
}

// Listar todos los tenants
function listTenants() {
    printSectionHeader("TENANTS DISPONIBLES");
    
    try {
        $tenants = \App\Models\Space::all();
        
        if ($tenants->isEmpty()) {
            echo "No hay tenants registrados en la base de datos.\n";
            return [];
        } else {
            $data = [];
            foreach ($tenants as $index => $tenant) {
                $domains = $tenant->domains()->pluck('domain')->toArray();
                $domainStr = !empty($domains) ? implode(', ', $domains) : 'Ninguno';
                
                echo "[{$index}] ID: {$tenant->id} - Nombre: {$tenant->name}\n";
                echo "    Dominios: {$domainStr}\n";
                
                $data[] = [
                    'index' => $index,
                    'tenant' => $tenant,
                    'domains' => $domains
                ];
            }
            return $data;
        }
    } catch (\Exception $e) {
        echo "Error al listar tenants: " . $e->getMessage() . "\n";
        return [];
    }
}

// Crear un subdominio para un tenant
function createSubdomain($tenantId, $subdomain) {
    printSectionHeader("CREAR SUBDOMINIO");
    
    try {
        $tenant = \App\Models\Space::find($tenantId);
        
        if (!$tenant) {
            echo "Error: No se encontró un tenant con ID {$tenantId}\n";
            return false;
        }
        
        // Verificar si ya existe un dominio con ese subdominio
        $existingDomain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $subdomain)->first();
        
        if ($existingDomain) {
            echo "El subdominio '{$subdomain}' ya existe y está asociado al tenant ID: {$existingDomain->tenant_id}\n";
            
            if ($existingDomain->tenant_id !== $tenant->id) {
                echo "¿Desea reasignar este subdominio al tenant {$tenant->name}? (s/n): ";
                $handle = fopen("php://stdin", "r");
                $line = trim(fgets($handle));
                fclose($handle);
                
                if ($line === 's') {
                    $existingDomain->tenant_id = $tenant->id;
                    $existingDomain->save();
                    echo "Subdominio reasignado correctamente al tenant {$tenant->name}\n";
                    return true;
                } else {
                    echo "Operación cancelada.\n";
                    return false;
                }
            }
            
            return true;
        }
        
        // Crear el dominio con el subdominio
        $domain = $tenant->domains()->create([
            'domain' => $subdomain
        ]);
        
        echo "Subdominio '{$subdomain}' creado y asociado al tenant {$tenant->name} (ID: {$tenant->id})\n";
        return true;
        
    } catch (\Exception $e) {
        echo "Error al crear subdominio: " . $e->getMessage() . "\n";
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
        return false;
    }
}

// Eliminar un subdominio
function deleteSubdomain($subdomain) {
    printSectionHeader("ELIMINAR SUBDOMINIO");
    
    try {
        $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $subdomain)->first();
        
        if (!$domain) {
            echo "El subdominio '{$subdomain}' no existe en la base de datos.\n";
            return false;
        }
        
        // Encontrar el tenant asociado para mostrar información
        $tenant = \App\Models\Space::find($domain->tenant_id);
        $tenantName = $tenant ? $tenant->name : 'desconocido';
        
        echo "Está a punto de eliminar el subdominio '{$subdomain}' asociado al tenant '{$tenantName}'.\n";
        echo "¿Está seguro? (s/n): ";
        
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if ($line === 's') {
            $domain->delete();
            echo "Subdominio '{$subdomain}' eliminado correctamente.\n";
            return true;
        } else {
            echo "Operación cancelada.\n";
            return false;
        }
        
    } catch (\Exception $e) {
        echo "Error al eliminar subdominio: " . $e->getMessage() . "\n";
        return false;
    }
}

// Configurar un dominio local en el archivo hosts
function configureLocalDomain($subdomain, $domainBase = 'test') {
    printSectionHeader("CONFIGURAR DOMINIO LOCAL");
    
    $fullDomain = "{$subdomain}.{$domainBase}";
    $hostsFile = '/etc/hosts';
    $entry = "127.0.0.1\t{$fullDomain}";
    
    echo "Para utilizar el subdominio '{$subdomain}' localmente, debe añadir la siguiente línea a su archivo /etc/hosts:\n\n";
    echo "{$entry}\n\n";
    
    echo "Esto requiere permisos de administrador. Puede editar el archivo manualmente, o este script puede intentar hacerlo por usted.\n";
    echo "¿Desea que el script intente añadir esta entrada a /etc/hosts? (s/n): ";
    
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if ($line === 's') {
        // El usuario quiere que lo hagamos por él
        // Primero verificamos si la entrada ya existe
        $hostsContent = file_get_contents($hostsFile);
        
        if (strpos($hostsContent, $fullDomain) !== false) {
            echo "El dominio '{$fullDomain}' ya está configurado en /etc/hosts.\n";
            return true;
        }
        
        echo "Se intentará añadir la entrada a /etc/hosts usando sudo. Se le pedirá su contraseña.\n";
        
        // Usamos una técnica que evita tener que escribir en un archivo temporal
        $command = "echo '{$entry}' | sudo tee -a {$hostsFile} > /dev/null";
        system($command, $returnValue);
        
        if ($returnValue === 0) {
            echo "Entrada añadida correctamente a /etc/hosts.\n";
            echo "Ahora puede acceder a: http://{$fullDomain}/\n";
            return true;
        } else {
            echo "Error al añadir la entrada a /etc/hosts. Código de retorno: {$returnValue}\n";
            echo "Intente añadir la entrada manualmente.\n";
            return false;
        }
    } else {
        echo "Por favor, añada la entrada manualmente a su archivo /etc/hosts.\n";
        return false;
    }
}

// Menú principal
printSectionHeader("GESTOR DE SUBDOMINIOS PARA TENANTS");
echo "Este script ayuda a configurar subdominios para sus tenants en enkiflow.\n\n";

echo "1. Listar tenants disponibles\n";
echo "2. Crear un subdominio para un tenant\n";
echo "3. Eliminar un subdominio\n";
echo "4. Configurar el subdominio 'enkiflow' para el primer tenant\n";
echo "5. Configurar un dominio local en /etc/hosts\n";
echo "0. Salir\n\n";

echo "Seleccione una opción: ";
$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));

switch ($choice) {
    case '1':
        listTenants();
        break;
        
    case '2':
        $tenants = listTenants();
        
        if (empty($tenants)) {
            echo "No hay tenants disponibles para asignar subdominios.\n";
            break;
        }
        
        echo "\nIngrese el número del tenant al que desea añadir un subdominio: ";
        $tenantIndex = trim(fgets($handle));
        
        if (!isset($tenants[$tenantIndex])) {
            echo "Índice de tenant no válido.\n";
            break;
        }
        
        $tenant = $tenants[$tenantIndex]['tenant'];
        
        echo "Ingrese el subdominio que desea crear (solo el nombre, sin el dominio base): ";
        $subdomain = trim(fgets($handle));
        
        if (empty($subdomain)) {
            echo "El subdominio no puede estar vacío.\n";
            break;
        }
        
        createSubdomain($tenant->id, $subdomain);
        break;
        
    case '3':
        echo "Ingrese el subdominio que desea eliminar: ";
        $subdomain = trim(fgets($handle));
        
        if (empty($subdomain)) {
            echo "El subdominio no puede estar vacío.\n";
            break;
        }
        
        deleteSubdomain($subdomain);
        break;
        
    case '4':
        $tenants = listTenants();
        
        if (empty($tenants)) {
            echo "No hay tenants disponibles para asignar subdominios.\n";
            break;
        }
        
        $tenant = $tenants[0]['tenant'];
        
        if (!$tenant) {
            echo "No se encontró el primer tenant.\n";
            break;
        }
        
        $created = createSubdomain($tenant->id, 'enkiflow');
        
        if ($created) {
            echo "\n¿Desea configurar este subdominio en su archivo /etc/hosts? (s/n): ";
            $configure = trim(fgets($handle));
            
            if ($configure === 's') {
                configureLocalDomain('enkiflow', 'test');
            }
        }
        break;
        
    case '5':
        echo "Ingrese el subdominio que desea configurar: ";
        $subdomain = trim(fgets($handle));
        
        if (empty($subdomain)) {
            echo "El subdominio no puede estar vacío.\n";
            break;
        }
        
        echo "Ingrese el dominio base (por defecto 'test'): ";
        $domainBase = trim(fgets($handle));
        
        if (empty($domainBase)) {
            $domainBase = 'test';
        }
        
        configureLocalDomain($subdomain, $domainBase);
        break;
        
    case '0':
        echo "Saliendo...\n";
        break;
        
    default:
        echo "Opción no válida.\n";
        break;
}

fclose($handle);