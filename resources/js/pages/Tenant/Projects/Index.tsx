import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Building2, Calendar, CheckCircle, FolderOpen, MoreHorizontal, Plus, Search } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Project {
    id: number;
    name: string;
    description?: string;
    status: 'active' | 'completed' | 'archived';
    created_at: string;
    updated_at: string;
    due_date?: string;
    tasks_count?: number;
    completed_tasks_count?: number;
    client?: {
        id: number;
        name: string;
    };
    user?: {
        id: number;
        name: string;
    };
    deleted_at?: string;
}

interface Client {
    id: number;
    name: string;
}

interface Props {
    projects: {
        data: Project[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters: {
        term?: string;
        status?: string;
        client_id?: string;
        include_archived?: boolean;
    };
    clients?: Client[];
}

export default function Index({ projects, filters, clients = [] }: Props) {
    const [search, setSearch] = useState(filters.term || '');

    // Búsqueda automática con debounce
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== filters.term) {
                router.get(
                    route('tenant.projects.index'),
                    { ...filters, search, page: 1 },
                    {
                        preserveState: true,
                        preserveScroll: true,
                    },
                );
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [search, filters]);

    const applyFilters = (newFilters: Record<string, any>) => {
        router.get(
            route('tenant.projects.index'),
            { ...filters, ...newFilters, page: 1 },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'active':
                return 'text-green-600 bg-green-50';
            case 'completed':
                return 'text-blue-600 bg-blue-50';
            case 'archived':
                return 'text-gray-600 bg-gray-50';
            default:
                return 'text-gray-600 bg-gray-50';
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'active':
                return <FolderOpen className="h-4 w-4" />;
            case 'completed':
                return <CheckCircle className="h-4 w-4" />;
            default:
                return <FolderOpen className="h-4 w-4" />;
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-MX', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const deleteProject = (project: Project) => {
        if (confirm(`¿Estás seguro de que deseas eliminar "${project.name}"? Esta acción no se puede deshacer.`)) {
            router.delete(route('tenant.projects.destroy', project.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout>
            <Head title="Proyectos" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Proyectos</h1>
                            <p className="text-muted-foreground mt-1">Gestiona tus proyectos y realiza seguimiento del progreso</p>
                        </div>
                        <Link href={route('tenant.projects.create')}>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Proyecto
                            </Button>
                        </Link>
                    </div>

                    <Card>
                        <CardHeader className="pb-3">
                            <div className="flex flex-col gap-4 sm:flex-row">
                                <div className="relative flex-1">
                                    <Search className="text-muted-foreground absolute top-2.5 left-2 h-4 w-4" />
                                    <Input
                                        placeholder="Buscar proyectos..."
                                        className="pl-8"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                    />
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Select
                                        value={filters.status || 'all'}
                                        onValueChange={(value) => applyFilters({ status: value === 'all' ? undefined : value })}
                                    >
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Estado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos los estados</SelectItem>
                                            <SelectItem value="active">Activos</SelectItem>
                                            <SelectItem value="completed">Completados</SelectItem>
                                        </SelectContent>
                                    </Select>

                                    {clients.length > 0 && (
                                        <Select
                                            value={filters.client_id || 'all'}
                                            onValueChange={(value) => applyFilters({ client_id: value === 'all' ? undefined : value })}
                                        >
                                            <SelectTrigger className="w-[180px]">
                                                <SelectValue placeholder="Cliente" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Todos los clientes</SelectItem>
                                                {clients.map((client) => (
                                                    <SelectItem key={client.id} value={client.id.toString()}>
                                                        {client.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent>
                            {projects.data.length === 0 ? (
                                <div className="py-12 text-center">
                                    <FolderOpen className="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                                    <h3 className="text-lg font-medium">No se encontraron proyectos</h3>
                                    <p className="text-muted-foreground mt-2">
                                        {search || filters.status || filters.client_id
                                            ? 'Intenta cambiar los filtros de búsqueda'
                                            : 'Comienza creando tu primer proyecto'}
                                    </p>
                                    {!search && !filters.status && !filters.client_id && (
                                        <Link href={route('tenant.projects.create')}>
                                            <Button className="mt-4">
                                                <Plus className="mr-2 h-4 w-4" />
                                                Crear Proyecto
                                            </Button>
                                        </Link>
                                    )}
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {projects.data.map((project) => (
                                        <div
                                            key={project.id}
                                            className={`hover:bg-accent/50 rounded-lg border p-4 transition-colors ${
                                                project.deleted_at ? 'opacity-60' : ''
                                            }`}
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <div className="mb-2 flex items-center gap-3">
                                                        <Link
                                                            href={route('tenant.projects.show', project.id)}
                                                            className="text-lg font-semibold hover:underline"
                                                        >
                                                            {project.name}
                                                        </Link>
                                                        <Badge variant={project.status === 'active' ? 'default' : 'secondary'} className="capitalize">
                                                            {project.status === 'active' ? 'Activo' : 'Completado'}
                                                        </Badge>
                                                        {project.deleted_at && <Badge variant="destructive">Archivado</Badge>}
                                                    </div>

                                                    {project.description && (
                                                        <p className="text-muted-foreground mb-2 line-clamp-2 text-sm">{project.description}</p>
                                                    )}

                                                    <div className="text-muted-foreground grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                                        {project.client && (
                                                            <div className="flex items-center gap-1">
                                                                <Building2 className="h-3 w-3" />
                                                                <span>{project.client.name}</span>
                                                            </div>
                                                        )}
                                                        {project.user && (
                                                            <div className="flex items-center gap-1">
                                                                <span>Responsable: {project.user.name}</span>
                                                            </div>
                                                        )}
                                                        <div className="flex items-center gap-1">
                                                            <Calendar className="h-3 w-3" />
                                                            <span>Creado: {formatDate(project.created_at)}</span>
                                                        </div>
                                                        {project.due_date && (
                                                            <div className="flex items-center gap-1">
                                                                <Calendar className="h-3 w-3" />
                                                                <span>Vence: {formatDate(project.due_date)}</span>
                                                            </div>
                                                        )}
                                                    </div>

                                                    {project.tasks_count !== undefined && project.tasks_count > 0 && (
                                                        <div className="mt-2 flex items-center gap-4 text-sm">
                                                            <div className="flex items-center gap-1">
                                                                <CheckCircle className="h-3 w-3" />
                                                                <span>
                                                                    {project.completed_tasks_count || 0} de {project.tasks_count} tareas completadas
                                                                </span>
                                                            </div>
                                                            {project.tasks_count > 0 && (
                                                                <div className="max-w-xs flex-1">
                                                                    <div className="h-2 overflow-hidden rounded-full bg-gray-200">
                                                                        <div
                                                                            className="bg-primary h-full rounded-full transition-all"
                                                                            style={{
                                                                                width: `${((project.completed_tasks_count || 0) / project.tasks_count) * 100}%`,
                                                                            }}
                                                                        />
                                                                    </div>
                                                                </div>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>

                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem asChild>
                                                            <Link href={route('tenant.projects.show', project.id)}>Ver detalles</Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem asChild>
                                                            <Link href={route('tenant.projects.edit', project.id)}>Editar</Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem onClick={() => deleteProject(project)} className="text-destructive">
                                                            Eliminar
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {projects.last_page > 1 && (
                                <div className="mt-6 flex justify-center">
                                    <div className="flex gap-2">
                                        {projects.current_page > 1 && (
                                            <Button variant="outline" size="sm" onClick={() => router.get(projects.prev_page_url!)}>
                                                Anterior
                                            </Button>
                                        )}
                                        <span className="text-muted-foreground flex items-center px-3 text-sm">
                                            Página {projects.current_page} de {projects.last_page}
                                        </span>
                                        {projects.current_page < projects.last_page && (
                                            <Button variant="outline" size="sm" onClick={() => router.get(projects.next_page_url!)}>
                                                Siguiente
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
