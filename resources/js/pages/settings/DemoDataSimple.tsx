import SettingsLayout from '@/layouts/settings/layout';
import { Head } from '@inertiajs/react';

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
                    <p className="text-muted-foreground text-sm">Esta es una versión de prueba para verificar que la página carga correctamente.</p>
                </div>

                <div className="rounded bg-gray-100 p-4">
                    <h4 className="font-bold">Props recibidas:</h4>
                    <pre className="mt-2 text-xs">{JSON.stringify({ scenarios, demoStats, hasDemoData }, null, 2)}</pre>
                </div>
            </div>
        </SettingsLayout>
    );
}
