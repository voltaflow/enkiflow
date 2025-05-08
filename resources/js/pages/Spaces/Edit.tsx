import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Domain {
    id: number;
    domain: string;
}

interface Space {
    id: string;
    name: string;
    domains: Domain[];
}

interface EditProps {
    space: Space;
}

export default function Edit({ space }: EditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Espacios',
            href: route('spaces.index'),
        },
        {
            title: space.name,
            href: route('spaces.show', space.id),
        },
        {
            title: 'Editar',
            href: route('spaces.edit', space.id),
        },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        name: space.name,
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        patch(route('spaces.update', space.id));
    };

    // Get primary domain if exists
    const primaryDomain = space.domains && space.domains.length > 0 
        ? space.domains[0].domain 
        : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Espacio: ${space.name}`} />
            
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <Card className="w-full max-w-md">
                    <form onSubmit={handleSubmit}>
                        <CardHeader>
                            <CardTitle>Editar Espacio</CardTitle>
                            <CardDescription>
                                Actualiza la configuración de tu espacio.
                            </CardDescription>
                        </CardHeader>
                        
                        <CardContent className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre del Espacio</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                            
                            {primaryDomain && (
                                <div className="space-y-2">
                                    <Label>Dominio</Label>
                                    <div className="p-2 bg-muted rounded-md">
                                        <p className="text-sm">{primaryDomain}</p>
                                        <p className="text-xs text-muted-foreground mt-1">
                                            El dominio no se puede cambiar una vez creado.
                                        </p>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                        
                        <CardFooter className="flex justify-between">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => window.history.back()}
                                disabled={processing}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                Guardar Cambios
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}