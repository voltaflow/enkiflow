import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { MultiSelect } from '@/components/ui/multi-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import React from 'react';

interface Project {
    id: number;
    name: string;
    description: string;
    client_id: number | null;
    status: string;
    due_date: string | null;
    tags: Tag[];
}

interface Client {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
}

interface Props {
    project: Project;
    clients: Client[];
    tags: Tag[];
}

export default function Edit({ project, clients, tags }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: project.name || '',
        description: project.description || '',
        client_id: project.client_id?.toString() || '',
        status: project.status || 'active',
        due_date: project.due_date || null,
        tags: project.tags?.map((tag) => tag.id) || [],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('tenant.projects.update', project.id));
    };

    return (
        <AppLayout>
            <Head title={`Editar ${project.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-4">
                        <Link href={route('tenant.projects.show', project.id)}>
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Editar Proyecto</h1>
                            <p className="text-muted-foreground mt-1">Actualiza la información de {project.name}</p>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información del Proyecto</CardTitle>
                                <CardDescription>Datos básicos del proyecto</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nombre del Proyecto *</Label>
                                    <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                    {errors.name && <p className="text-destructive text-sm">{errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Descripción</Label>
                                    <Textarea
                                        id="description"
                                        rows={4}
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                    />
                                    {errors.description && <p className="text-destructive text-sm">{errors.description}</p>}
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="client_id">Cliente</Label>
                                        <Select
                                            value={data.client_id?.toString() || 'none'}
                                            onValueChange={(value) => setData('client_id', value === 'none' ? '' : value)}
                                        >
                                            <SelectTrigger id="client_id">
                                                <SelectValue placeholder="Seleccionar cliente" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Sin cliente</SelectItem>
                                                {clients.map((client) => (
                                                    <SelectItem key={client.id} value={client.id.toString()}>
                                                        {client.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.client_id && <p className="text-destructive text-sm">{errors.client_id}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="status">Estado</Label>
                                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                            <SelectTrigger id="status">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">Activo</SelectItem>
                                                <SelectItem value="completed">Completado</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.status && <p className="text-destructive text-sm">{errors.status}</p>}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="due_date">Fecha de Vencimiento</Label>
                                    <DatePicker
                                        value={data.due_date ? new Date(data.due_date) : undefined}
                                        onChange={(date) => setData('due_date', date ? date.toISOString() : null)}
                                    />
                                    {errors.due_date && <p className="text-destructive text-sm">{errors.due_date}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="tags">Etiquetas</Label>
                                    <MultiSelect
                                        options={tags.map((tag) => ({ value: tag.id.toString(), label: tag.name }))}
                                        selected={data.tags.map((id) => id.toString())}
                                        onChange={(selected) =>
                                            setData(
                                                'tags',
                                                selected.map((id) => parseInt(id)),
                                            )
                                        }
                                        placeholder="Seleccionar etiquetas"
                                    />
                                    {errors.tags && <p className="text-destructive text-sm">{errors.tags}</p>}
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex gap-4">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Guardando...' : 'Actualizar Proyecto'}
                            </Button>
                            <Link href={route('tenant.projects.show', project.id)}>
                                <Button variant="outline" type="button">
                                    Cancelar
                                </Button>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
