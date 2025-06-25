import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

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

export default function DemoDataDebug({ scenarios, demoStats, hasDemoData }: Props) {
    const [confirmReset, setConfirmReset] = useState(false);

    const form = useForm({
        scenario: '',
        start_date: '',
        skip_time_entries: false,
        only_structure: false,
    });

    console.log('DemoDataDebug loaded with props:', { scenarios, demoStats, hasDemoData });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Datos Demo" />

            <SettingsLayout>
                <div className="space-y-6">
                <div>
                    <h3 className="text-lg font-medium">Datos de Demostración</h3>
                    <p className="text-sm text-muted-foreground">
                        Gestiona los datos de demostración para pruebas y desarrollo
                    </p>
                </div>

                {/* Alerta de datos demo activos */}
                {hasDemoData && (
                    <Alert>
                        <AlertTitle>Datos de demostración activos</AlertTitle>
                        <AlertDescription>
                            Tu cuenta tiene datos de demostración activos. Estos datos están marcados con [DEMO] y pueden ser eliminados en cualquier momento.
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
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                {Object.entries(demoStats.models).map(([model, stat]) => (
                                    <div key={model} className="bg-gray-50 p-4 rounded-lg">
                                        <h4 className="font-medium text-gray-700">{stat.label}</h4>
                                        <p className="text-2xl font-bold text-indigo-600">{stat.count}</p>
                                    </div>
                                ))}
                            </div>

                            <div className="flex justify-end space-x-4">
                                <Button
                                    variant="destructive"
                                    onClick={() => setConfirmReset(true)}
                                >
                                    Eliminar Datos Demo
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Panel de generación de datos - Versión simplificada */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {hasDemoData ? 'Generar Más Datos Demo' : 'Generar Datos Demo'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={(e) => {
                            e.preventDefault();
                            console.log('Form submitted with data:', form.data);
                            form.post('/settings/developer/demo-data/generate', {
                                preserveScroll: true,
                                onSuccess: () => {
                                    console.log('Demo data generated successfully!');
                                    form.reset();
                                },
                                onError: (errors) => {
                                    console.error('Error generating demo data:', errors);
                                }
                            });
                        }} className="space-y-4">
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

                            <div className="flex justify-end">
                                <Button type="submit" disabled={form.processing}>
                                    {hasDemoData ? 'Generar Más Datos' : 'Añadir Datos Demo'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                    <div className="text-xs text-gray-500">
                        Debug: Scenarios count: {scenarios.length}, Has demo data: {hasDemoData.toString()}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}