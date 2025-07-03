import React from 'react';
import { Head } from '@inertiajs/react';
import SettingsLayout from '@/layouts/settings/layout';

interface Props {
    scenarios?: any[];
    demoStats?: any;
    hasDemoData?: boolean;
}

export default function DemoDataSimple({ scenarios = [], demoStats = {}, hasDemoData = false }: Props) {
    return (
        <SettingsLayout>
            <Head title="Datos Demo" />
            <div className="space-y-6">
                <div>
                    <h3 className="text-lg font-medium">Datos de Demostración - Versión Simple</h3>
                    <p className="text-sm text-muted-foreground">
                        Esta es una versión de prueba para verificar que la página carga correctamente.
                    </p>
                </div>
                
                <div className="bg-gray-100 p-4 rounded">
                    <h4 className="font-bold">Props recibidas:</h4>
                    <pre className="text-xs mt-2">
                        {JSON.stringify({ scenarios, demoStats, hasDemoData }, null, 2)}
                    </pre>
                </div>
            </div>
        </SettingsLayout>
    );
}