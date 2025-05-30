import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Espacios',
        href: route('spaces.index'),
    },
    {
        title: 'Crear Espacio',
        href: route('spaces.create'),
    },
];

export default function Create() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        domain: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post(route('spaces.store'), {
            onSuccess: () => reset(),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Espacio" />

            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <Card className="w-full max-w-md">
                    <form onSubmit={handleSubmit}>
                        <CardHeader>
                            <CardTitle>Crear Nuevo Espacio</CardTitle>
                            <CardDescription>Crea un nuevo espacio para tu organización o equipo.</CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre del Espacio</Label>
                                <Input
                                    id="name"
                                    placeholder="Mi Organización"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.name} />
                                <p className="text-muted-foreground text-xs">El nombre de tu organización o equipo.</p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="domain">Dominio Personalizado (Opcional)</Label>
                                <div className="flex">
                                    <Input
                                        id="domain"
                                        placeholder="mi-organizacion"
                                        value={data.domain}
                                        onChange={(e) => setData('domain', e.target.value)}
                                        className="rounded-r-none"
                                    />
                                    <div className="bg-muted border-input flex items-center justify-center rounded-r-md border border-l-0 px-3">
                                        .localhost
                                    </div>
                                </div>
                                <InputError message={errors.domain} />
                                <p className="text-muted-foreground text-xs">
                                    Un subdominio para acceder a tu espacio. Solo letras, números y guiones.
                                </p>
                            </div>
                        </CardContent>

                        <CardFooter className="flex justify-between">
                            <Button type="button" variant="outline" onClick={() => window.history.back()} disabled={processing}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                Crear Espacio
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}
