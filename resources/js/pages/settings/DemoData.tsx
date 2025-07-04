import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import React, { useState } from 'react';
// import { InformationCircleIcon } from '@heroicons/react/24/outline';
import { Download } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Demo Data',
        href: '/settings/developer/demo-data',
    },
];

interface Scenario {
    id: string;
    name: string;
    description: string;
    projects_count: number;
}

interface DemoStat {
    label: string;
    count: number;
}

interface DemoStats {
    models: Record<string, DemoStat>;
    total: number;
}

interface Props {
    scenarios: Scenario[];
    demoStats: DemoStats;
    hasDemoData: boolean;
}

export default function DemoData({ scenarios, demoStats, hasDemoData }: Props) {
    const [confirmReset, setConfirmReset] = useState(false);

    const form = useForm({
        scenario: 'default',
        start_date: '',
        skip_time_entries: false,
        only_structure: false,
    });

    const cloneForm = useForm({
        target_tenant: '',
        mark_as_demo: true,
    });

    const generateDemoData = (e: React.FormEvent) => {
        e.preventDefault();

        // Preparar los datos
        const originalScenario = form.data.scenario;

        // Cambiar temporalmente el scenario si es "default"
        if (form.data.scenario === 'default') {
            form.setData('scenario', '');
        }

        form.post(route('settings.demo-data.generate'), {
            preserveScroll: true,
            onSuccess: () => {
                // Resetear el form con scenario en "default"
                form.reset();
                form.setData('scenario', 'default');
            },
            onError: (errors) => {
                console.error('Error generating demo data:', errors);
                // Restaurar el valor original si hay error
                form.setData('scenario', originalScenario);
            },
            onProgress: () => {},
        });
    };

    const resetDemoData = () => {
        setConfirmReset(false);

        // Usar router de Inertia directamente para POST simple
        router.post(
            route('settings.demo-data.reset'),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {},
                onError: (errors) => {
                    console.error('Error resetting demo data:', errors);
                },
                onProgress: () => {},
            },
        );
    };

    const cloneData = (e: React.FormEvent) => {
        e.preventDefault();
        cloneForm.post(route('settings.demo-data.clone'), {
            preserveScroll: true,
            onSuccess: () => {
                cloneForm.reset();
                cloneForm.setData('mark_as_demo', true);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Datos Demo" />

            <SettingsLayout>
                <div className="space-y-6">
                    <div>
                        <h3 className="text-lg font-medium">Datos de Demostración</h3>
                        <p className="text-muted-foreground text-sm">Gestiona los datos de demostración para pruebas y desarrollo</p>
                    </div>

                    {/* Alerta de datos demo activos */}
                    {hasDemoData && (
                        <Alert>
                            <AlertTitle>Datos de demostración activos</AlertTitle>
                            <AlertDescription>
                                Tu cuenta tiene datos de demostración activos. Estos datos están marcados con [DEMO] y pueden ser eliminados en
                                cualquier momento.
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* Panel de estadísticas */}
                    {hasDemoData && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Datos Demo Actuales</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                                    {Object.entries(demoStats.models).map(([model, stat]) => (
                                        <div key={model} className="rounded-lg bg-gray-50 p-4">
                                            <h4 className="font-medium text-gray-700">{stat.label}</h4>
                                            <p className="text-2xl font-bold text-indigo-600">{stat.count}</p>
                                        </div>
                                    ))}
                                </div>

                                <div className="flex justify-end space-x-4">
                                    <Button variant="outline" asChild>
                                        <a href={route('settings.demo-data.snapshot')} className="inline-flex items-center">
                                            <Download className="mr-2 h-4 w-4" />
                                            Descargar Snapshot
                                        </a>
                                    </Button>

                                    <Button variant="destructive" onClick={() => setConfirmReset(true)}>
                                        Eliminar Datos Demo
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Panel de generación de datos */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{hasDemoData ? 'Generar Más Datos Demo' : 'Generar Datos Demo'}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={generateDemoData} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="scenario">Escenario</Label>
                                    <Select value={form.data.scenario} onValueChange={(value) => form.setData('scenario', value)}>
                                        <SelectTrigger id="scenario">
                                            <SelectValue placeholder="Escenario predeterminado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="default">Escenario predeterminado</SelectItem>
                                            {scenarios.map((scenario) => (
                                                <SelectItem key={scenario.id} value={scenario.id}>
                                                    {scenario.name} ({scenario.projects_count} proyectos)
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-sm text-gray-600">
                                        Selecciona un escenario predefinido o deja en blanco para usar el predeterminado.
                                    </p>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="start_date">Fecha de inicio</Label>
                                    <Input
                                        id="start_date"
                                        type="date"
                                        value={form.data.start_date}
                                        onChange={(e) => form.setData('start_date', e.target.value)}
                                    />
                                    <p className="text-sm text-gray-600">
                                        Fecha de referencia para fechas relativas. Deja en blanco para usar la fecha actual.
                                    </p>
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="skip_time_entries"
                                            checked={form.data.skip_time_entries}
                                            onCheckedChange={(checked) => form.setData('skip_time_entries', checked === true)}
                                        />
                                        <Label htmlFor="skip_time_entries">Omitir entradas de tiempo</Label>
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="only_structure"
                                            checked={form.data.only_structure}
                                            onCheckedChange={(checked) => form.setData('only_structure', checked === true)}
                                        />
                                        <Label htmlFor="only_structure">Solo estructura básica</Label>
                                    </div>
                                </div>

                                <div className="flex justify-end">
                                    <Button type="submit" disabled={form.processing}>
                                        {hasDemoData ? 'Generar Más Datos' : 'Añadir Datos Demo'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Panel de clonación de datos - Comentado para uso futuro */}
                    {/* {hasDemoData && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Clonar Datos a Otro Tenant</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={cloneData} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="target_tenant">Tenant de destino</Label>
                                    <Input
                                        id="target_tenant"
                                        type="text"
                                        value={cloneForm.data.target_tenant}
                                        onChange={(e) => cloneForm.setData('target_tenant', e.target.value)}
                                        placeholder="ID del tenant de destino"
                                        required
                                    />
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="mark_as_demo"
                                        checked={cloneForm.data.mark_as_demo}
                                        onCheckedChange={(checked) => 
                                            cloneForm.setData('mark_as_demo', checked === true)
                                        }
                                    />
                                    <Label htmlFor="mark_as_demo">
                                        Marcar como datos demo
                                    </Label>
                                </div>

                                <div className="flex justify-end">
                                    <Button type="submit" disabled={cloneForm.processing}>
                                        Clonar Datos
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )} */}

                    {/* Modal de confirmación para eliminar datos */}
                    <Dialog open={confirmReset} onOpenChange={setConfirmReset}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Confirmar eliminación de datos demo</DialogTitle>
                                <DialogDescription>
                                    ¿Estás seguro de que deseas eliminar todos los datos de demostración? Esta acción no se puede deshacer.
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setConfirmReset(false)}>
                                    Cancelar
                                </Button>
                                <Button variant="destructive" onClick={resetDemoData}>
                                    Eliminar Datos Demo
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
