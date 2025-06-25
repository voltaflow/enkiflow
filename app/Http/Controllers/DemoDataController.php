<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemoDataRequest;
use App\Services\DemoDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DemoDataController extends Controller
{
    /**
     * Servicio de datos demo.
     *
     * @var DemoDataService
     */
    protected $demoDataService;

    /**
     * Constructor.
     */
    public function __construct(DemoDataService $demoDataService)
    {
        $this->demoDataService = $demoDataService;
        
        // Solo permitir acceso a usuarios autenticados del tenant
        $this->middleware(['auth']);
    }

    /**
     * Mostrar la página de administración de datos demo.
     */
    public function index()
    {
        try {
            $scenarios = $this->demoDataService->getAvailableScenarios();
            $demoStats = $this->demoDataService->getDemoStats();
            
            \Log::info('DemoDataController::index', [
                'scenarios' => $scenarios,
                'demoStats' => $demoStats,
            ]);
            
            return Inertia::render('settings/DemoData', [
                'scenarios' => $scenarios,
                'demoStats' => $demoStats,
                'hasDemoData' => $demoStats['total'] > 0,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in DemoDataController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Generar datos demo.
     */
    public function generate(DemoDataRequest $request)
    {
        // Obtener el tenant actual de diferentes maneras
        $tenantId = null;
        
        // Primero intentar desde la sesión
        if (session()->has('current_space_id')) {
            $tenantId = session('current_space_id');
        }
        
        // Si no está en sesión, intentar desde el dominio actual
        if (!$tenantId) {
            $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $request->getHost())->first();
            if ($domain) {
                $tenantId = $domain->tenant_id;
            }
        }
        
        // Como último recurso, intentar desde el helper tenant()
        if (!$tenantId) {
            $currentTenant = tenant();
            if ($currentTenant && method_exists($currentTenant, 'getAttributes')) {
                $attributes = $currentTenant->getAttributes();
                $tenantId = $attributes['id'] ?? $attributes['tenant_id'] ?? null;
            }
        }
        
        \Log::info('DemoDataController::generate - Tenant actual', [
            'tenant_id' => $tenantId,
            'session_space_id' => session('current_space_id'),
            'host' => $request->getHost(),
        ]);
        
        if (!$tenantId) {
            return redirect()->route('settings.demo-data')->with('error', 'No se pudo determinar el tenant actual.');
        }
        
        $scenario = $request->input('scenario');
        $options = [
            'skip_time_entries' => $request->boolean('skip_time_entries'),
            'only_structure' => $request->boolean('only_structure'),
            'start_date' => $request->input('start_date'),
            'tenant_id' => $tenantId, // Pasar el tenant explícitamente
        ];
        
        $result = $this->demoDataService->generateDemoData($scenario, $options);
        
        \Log::info('DemoDataController: Resultado de generación', [
            'result' => $result,
        ]);
        
        if ($result['success']) {
            return redirect()->route('settings.demo-data')->with('success', 'Datos de demostración generados correctamente.');
        }
        
        return redirect()->route('settings.demo-data')->with('error', $result['message']);
    }

    /**
     * Eliminar datos demo.
     */
    public function reset()
    {
        $result = $this->demoDataService->resetDemoData();
        
        if ($result['success']) {
            return redirect()->route('settings.demo-data')->with('success', 'Datos de demostración eliminados correctamente.');
        }
        
        return redirect()->route('settings.demo-data')->with('error', $result['message']);
    }

    /**
     * Descargar snapshot de datos demo.
     */
    public function snapshot()
    {
        return $this->demoDataService->generateSnapshot();
    }

    /**
     * Clonar datos a otro tenant.
     */
    public function clone(Request $request)
    {
        $request->validate([
            'target_tenant' => 'required|string|exists:tenants,id',
            'mark_as_demo' => 'boolean',
        ]);
        
        $targetTenant = $request->input('target_tenant');
        $markAsDemo = $request->boolean('mark_as_demo', true);
        
        $result = $this->demoDataService->cloneToTenant($targetTenant, $markAsDemo);
        
        if ($result['success']) {
            return redirect()->route('settings.demo-data')->with('success', "Datos clonados correctamente al tenant '{$targetTenant}'.");
        }
        
        return redirect()->route('settings.demo-data')->with('error', $result['message']);
    }
}