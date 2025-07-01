import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { 
    Plus, 
    FolderOpen, 
    Calendar, 
    CheckCircle, 
    Search,
    Filter,
    Building2,
    MoreHorizontal
} from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
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
                    }
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
            }
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
            day: 'numeric'
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
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Proyectos</h1>
                        <p className="text-muted-foreground mt-1">
                            Gestiona tus proyectos y realiza seguimiento del progreso
                        </p>
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
                        <div className="flex flex-col sm:flex-row gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
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
                            <div className="text-center py-12">
                                <FolderOpen className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                <h3 className="text-lg font-medium">No se encontraron proyectos</h3>
                                <p className="text-muted-foreground mt-2">
                                    {search || filters.status || filters.client_id 
                                        ? 'Intenta cambiar los filtros de búsqueda'
                                        : 'Comienza creando tu primer proyecto'}
                                </p>
                                {(!search && !filters.status && !filters.client_id) && (
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
                                        className={`border rounded-lg p-4 hover:bg-accent/50 transition-colors ${
                                            project.deleted_at ? 'opacity-60' : ''
                                        }`}
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <Link
                                                        href={route('tenant.projects.show', project.id)}
                                                        className="text-lg font-semibold hover:underline"
                                                    >
                                                        {project.name}
                                                    </Link>
                                                    <Badge 
                                                        variant={project.status === 'active' ? 'default' : 'secondary'}
                                                        className="capitalize"
                                                    >
                                                        {project.status === 'active' ? 'Activo' : 'Completado'}
                                                    </Badge>
                                                    {project.deleted_at && (
                                                        <Badge variant="destructive">Archivado</Badge>
                                                    )}
                                                </div>

                                                {project.description && (
                                                    <p className="text-sm text-muted-foreground mb-2 line-clamp-2">
                                                        {project.description}
                                                    </p>
                                                )}
                                                
                                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm text-muted-foreground">
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
                                                            <div className="flex-1 max-w-xs">
                                                                <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                    <div 
                                                                        className="h-full bg-primary rounded-full transition-all"
                                                                        style={{ 
                                                                            width: `${((project.completed_tasks_count || 0) / project.tasks_count) * 100}%` 
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
                                                        <Link href={route('tenant.projects.show', project.id)}>
                                                            Ver detalles
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('tenant.projects.edit', project.id)}>
                                                            Editar
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        onClick={() => deleteProject(project)}
                                                        className="text-destructive"
                                                    >
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
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => router.get(projects.prev_page_url!)}
                                        >
                                            Anterior
                                        </Button>
                                    )}
                                    <span className="flex items-center px-3 text-sm text-muted-foreground">
                                        Página {projects.current_page} de {projects.last_page}
                                    </span>
                                    {projects.current_page < projects.last_page && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => router.get(projects.next_page_url!)}
                                        >
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