import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    ArrowLeft, 
    Building2, 
    Mail, 
    Phone, 
    Globe, 
    MapPin,
    User,
    Edit,
    Archive,
    Clock,
    Calendar,
    DollarSign,
    Activity,
    FolderOpen,
    CheckCircle,
    Plus
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

interface Project {
    id: number;
    name: string;
    status: 'active' | 'completed' | 'archived';
    tasks_count: number;
    created_at: string;
}

interface Client {
    id: number;
    name: string;
    slug: string;
    email?: string;
    phone?: string;
    website?: string;
    address?: string;
    city?: string;
    state?: string;
    country?: string;
    postal_code?: string;
    contact_name?: string;
    contact_email?: string;
    contact_phone?: string;
    notes?: string;
    timezone: string;
    currency: string;
    is_active: boolean;
    projects?: Project[];
    created_at: string;
    updated_at: string;
    deleted_at?: string;
}

interface Stats {
    total_projects: number;
    active_projects: number;
    total_hours: number;
    billable_hours: number;
    total_time_entries: number;
    last_activity: string | null;
}

interface Props {
    client: Client;
    stats: Stats;
}

export default function Show({ client, stats }: Props) {
    const toggleClientStatus = () => {
        router.post(route('tenant.clients.toggle-status', client.id), {}, {
            preserveScroll: true,
        });
    };

    const archiveClient = () => {
        if (confirm(`¿Estás seguro de que deseas archivar a ${client.name}?`)) {
            router.delete(route('tenant.clients.destroy', client.id));
        }
    };

    const restoreClient = () => {
        router.post(route('tenant.clients.restore', client.id));
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-MX', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatHours = (hours: number) => {
        return hours.toFixed(1);
    };

    const getProjectStatusBadge = (status: string) => {
        switch (status) {
            case 'active':
                return <Badge variant="default">Activo</Badge>;
            case 'completed':
                return <Badge variant="secondary">Completado</Badge>;
            case 'archived':
                return <Badge variant="outline">Archivado</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AppLayout>
            <Head title={client.name} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route('tenant.clients.index')}>
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-3xl font-bold tracking-tight">{client.name}</h1>
                                {!client.is_active && (
                                    <Badge variant="secondary">Inactivo</Badge>
                                )}
                                {client.deleted_at && (
                                    <Badge variant="destructive">Archivado</Badge>
                                )}
                            </div>
                            <p className="text-muted-foreground mt-1">
                                Cliente desde {formatDate(client.created_at)}
                            </p>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline">
                                Acciones
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <Link href={route('tenant.clients.edit', client.id)}>
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            {!client.deleted_at && (
                                <>
                                    <DropdownMenuItem onClick={toggleClientStatus}>
                                        <Activity className="mr-2 h-4 w-4" />
                                        {client.is_active ? 'Desactivar' : 'Activar'}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        onClick={archiveClient}
                                        className="text-destructive"
                                    >
                                        <Archive className="mr-2 h-4 w-4" />
                                        Archivar
                                    </DropdownMenuItem>
                                </>
                            )}
                            {client.deleted_at && (
                                <DropdownMenuItem onClick={restoreClient}>
                                    <Activity className="mr-2 h-4 w-4" />
                                    Restaurar
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div className="grid gap-6 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Proyectos Totales
                            </CardTitle>
                            <FolderOpen className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_projects}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.active_projects} activos
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Horas Totales
                            </CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatHours(stats.total_hours)}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.total_time_entries} entradas
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Horas Facturables
                            </CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatHours(stats.billable_hours)}</div>
                            <p className="text-xs text-muted-foreground">
                                {((stats.billable_hours / stats.total_hours) * 100).toFixed(0)}% facturables
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Última Actividad
                            </CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.last_activity ? formatDate(stats.last_activity) : 'Sin actividad'}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Tabs defaultValue="info" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="info">Información</TabsTrigger>
                        <TabsTrigger value="projects">Proyectos</TabsTrigger>
                        {client.notes && <TabsTrigger value="notes">Notas</TabsTrigger>}
                    </TabsList>

                    <TabsContent value="info" className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Información de Contacto</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {client.email && (
                                        <div className="flex items-center gap-2">
                                            <Mail className="h-4 w-4 text-muted-foreground" />
                                            <a href={`mailto:${client.email}`} className="hover:underline">
                                                {client.email}
                                            </a>
                                        </div>
                                    )}
                                    {client.phone && (
                                        <div className="flex items-center gap-2">
                                            <Phone className="h-4 w-4 text-muted-foreground" />
                                            <a href={`tel:${client.phone}`} className="hover:underline">
                                                {client.phone}
                                            </a>
                                        </div>
                                    )}
                                    {client.website && (
                                        <div className="flex items-center gap-2">
                                            <Globe className="h-4 w-4 text-muted-foreground" />
                                            <a 
                                                href={client.website} 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                className="hover:underline"
                                            >
                                                {client.website}
                                            </a>
                                        </div>
                                    )}
                                    {(client.address || client.city) && (
                                        <div className="flex items-start gap-2">
                                            <MapPin className="h-4 w-4 text-muted-foreground mt-0.5" />
                                            <div>
                                                {client.address && <div>{client.address}</div>}
                                                <div>
                                                    {[client.city, client.state, client.postal_code]
                                                        .filter(Boolean)
                                                        .join(', ')}
                                                </div>
                                                {client.country && <div>{client.country}</div>}
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Persona de Contacto</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {client.contact_name ? (
                                        <>
                                            <div className="flex items-center gap-2">
                                                <User className="h-4 w-4 text-muted-foreground" />
                                                <span>{client.contact_name}</span>
                                            </div>
                                            {client.contact_email && (
                                                <div className="flex items-center gap-2">
                                                    <Mail className="h-4 w-4 text-muted-foreground" />
                                                    <a 
                                                        href={`mailto:${client.contact_email}`} 
                                                        className="hover:underline"
                                                    >
                                                        {client.contact_email}
                                                    </a>
                                                </div>
                                            )}
                                            {client.contact_phone && (
                                                <div className="flex items-center gap-2">
                                                    <Phone className="h-4 w-4 text-muted-foreground" />
                                                    <a 
                                                        href={`tel:${client.contact_phone}`} 
                                                        className="hover:underline"
                                                    >
                                                        {client.contact_phone}
                                                    </a>
                                                </div>
                                            )}
                                        </>
                                    ) : (
                                        <p className="text-muted-foreground">
                                            No hay información de contacto disponible
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Configuración</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div>
                                        <span className="text-sm text-muted-foreground">Zona Horaria</span>
                                        <div className="font-medium">{client.timezone}</div>
                                    </div>
                                    <div>
                                        <span className="text-sm text-muted-foreground">Moneda</span>
                                        <div className="font-medium">{client.currency}</div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="projects" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle>Proyectos</CardTitle>
                                    <Link href={`${route('tenant.projects.create')}?client_id=${client.id}`}>
                                        <Button size="sm">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Nuevo Proyecto
                                        </Button>
                                    </Link>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {client.projects && client.projects.length > 0 ? (
                                    <div className="space-y-3">
                                        {client.projects.map((project) => (
                                            <div 
                                                key={project.id} 
                                                className="flex items-center justify-between p-3 border rounded-lg hover:bg-accent/50 transition-colors"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <Link
                                                        href={route('tenant.projects.show', project.id)}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {project.name}
                                                    </Link>
                                                    {getProjectStatusBadge(project.status)}
                                                </div>
                                                <div className="text-sm text-muted-foreground">
                                                    {project.tasks_count} tareas
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <FolderOpen className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                        <p className="text-muted-foreground">
                                            No hay proyectos asociados a este cliente
                                        </p>
                                        <Link href={`${route('tenant.projects.create')}?client_id=${client.id}`}>
                                            <Button className="mt-4">
                                                <Plus className="mr-2 h-4 w-4" />
                                                Crear Proyecto
                                            </Button>
                                        </Link>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {client.notes && (
                        <TabsContent value="notes" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Notas</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap">{client.notes}</p>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    )}
                </Tabs>
                </div>
            </div>
        </AppLayout>
    );
}