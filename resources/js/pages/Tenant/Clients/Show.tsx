import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Activity, Archive, ArrowLeft, Calendar, Clock, DollarSign, Edit, FolderOpen, Globe, Mail, MapPin, Phone, Plus, User } from 'lucide-react';

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
        router.post(
            route('tenant.clients.toggle-status', client.id),
            {},
            {
                preserveScroll: true,
            },
        );
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
            day: 'numeric',
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
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
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
                                    {!client.is_active && <Badge variant="secondary">Inactivo</Badge>}
                                    {client.deleted_at && <Badge variant="destructive">Archivado</Badge>}
                                </div>
                                <p className="text-muted-foreground mt-1">Cliente desde {formatDate(client.created_at)}</p>
                            </div>
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline">Acciones</Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    onClick={(e) => {
                                        e.preventDefault();
                                        router.visit(route('tenant.clients.edit', client.id));
                                    }}
                                >
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                {!client.deleted_at && (
                                    <>
                                        <DropdownMenuItem onClick={toggleClientStatus}>
                                            <Activity className="mr-2 h-4 w-4" />
                                            {client.is_active ? 'Desactivar' : 'Activar'}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={archiveClient} className="text-destructive">
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
                                <CardTitle className="text-sm font-medium">Proyectos Totales</CardTitle>
                                <FolderOpen className="text-muted-foreground h-4 w-4" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.total_projects}</div>
                                <p className="text-muted-foreground text-xs">{stats.active_projects} activos</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Horas Totales</CardTitle>
                                <Clock className="text-muted-foreground h-4 w-4" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatHours(stats.total_hours)}</div>
                                <p className="text-muted-foreground text-xs">{stats.total_time_entries} entradas</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Horas Facturables</CardTitle>
                                <DollarSign className="text-muted-foreground h-4 w-4" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{formatHours(stats.billable_hours)}</div>
                                <p className="text-muted-foreground text-xs">
                                    {((stats.billable_hours / stats.total_hours) * 100).toFixed(0)}% facturables
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Última Actividad</CardTitle>
                                <Calendar className="text-muted-foreground h-4 w-4" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.last_activity ? formatDate(stats.last_activity) : 'Sin actividad'}</div>
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
                                                <Mail className="text-muted-foreground h-4 w-4" />
                                                <a href={`mailto:${client.email}`} className="hover:underline">
                                                    {client.email}
                                                </a>
                                            </div>
                                        )}
                                        {client.phone && (
                                            <div className="flex items-center gap-2">
                                                <Phone className="text-muted-foreground h-4 w-4" />
                                                <a href={`tel:${client.phone}`} className="hover:underline">
                                                    {client.phone}
                                                </a>
                                            </div>
                                        )}
                                        {client.website && (
                                            <div className="flex items-center gap-2">
                                                <Globe className="text-muted-foreground h-4 w-4" />
                                                <a href={client.website} target="_blank" rel="noopener noreferrer" className="hover:underline">
                                                    {client.website}
                                                </a>
                                            </div>
                                        )}
                                        {(client.address || client.city) && (
                                            <div className="flex items-start gap-2">
                                                <MapPin className="text-muted-foreground mt-0.5 h-4 w-4" />
                                                <div>
                                                    {client.address && <div>{client.address}</div>}
                                                    <div>{[client.city, client.state, client.postal_code].filter(Boolean).join(', ')}</div>
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
                                                    <User className="text-muted-foreground h-4 w-4" />
                                                    <span>{client.contact_name}</span>
                                                </div>
                                                {client.contact_email && (
                                                    <div className="flex items-center gap-2">
                                                        <Mail className="text-muted-foreground h-4 w-4" />
                                                        <a href={`mailto:${client.contact_email}`} className="hover:underline">
                                                            {client.contact_email}
                                                        </a>
                                                    </div>
                                                )}
                                                {client.contact_phone && (
                                                    <div className="flex items-center gap-2">
                                                        <Phone className="text-muted-foreground h-4 w-4" />
                                                        <a href={`tel:${client.contact_phone}`} className="hover:underline">
                                                            {client.contact_phone}
                                                        </a>
                                                    </div>
                                                )}
                                            </>
                                        ) : (
                                            <p className="text-muted-foreground">No hay información de contacto disponible</p>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Configuración</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        <div>
                                            <span className="text-muted-foreground text-sm">Zona Horaria</span>
                                            <div className="font-medium">{client.timezone}</div>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground text-sm">Moneda</span>
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
                                                    className="hover:bg-accent/50 flex items-center justify-between rounded-lg border p-3 transition-colors"
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
                                                    <div className="text-muted-foreground text-sm">{project.tasks_count} tareas</div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="py-8 text-center">
                                            <FolderOpen className="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                                            <p className="text-muted-foreground">No hay proyectos asociados a este cliente</p>
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
