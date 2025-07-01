import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    ArrowLeft, 
    Calendar, 
    CheckCircle, 
    Clock, 
    Edit, 
    FolderOpen, 
    Plus, 
    Trash2, 
    User,
    Building2,
    Tag
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

interface Task {
    id: number;
    title: string;
    status: string;
    priority: number;
    due_date: string | null;
    user: {
        id: number;
        name: string;
    } | null;
}

interface TimeEntry {
    id: number;
    description: string;
    started_at: string;
    duration: number;
    is_billable: boolean;
    user: {
        id: number;
        name: string;
    };
}

interface Tag {
    id: number;
    name: string;
    color: string;
}

interface Project {
    id: number;
    name: string;
    description: string;
    status: 'active' | 'completed';
    due_date: string | null;
    completed_at: string | null;
    client: {
        id: number;
        name: string;
    } | null;
    user: {
        id: number;
        name: string;
        email: string;
    };
    tags: Tag[];
    tasks: Task[];
    created_at: string;
    updated_at: string;
}

interface Props {
    project: Project;
    is_owner: boolean;
    can_edit: boolean;
    can_delete: boolean;
    can_complete: boolean;
    stats?: {
        total_tasks: number;
        completed_tasks: number;
        total_hours: number;
        billable_hours: number;
    };
}

export default function Show({ project, is_owner, can_edit, can_delete, can_complete, stats }: Props) {
    const completeProject = () => {
        if (confirm(`¿Estás seguro de que deseas marcar "${project.name}" como completado?`)) {
            router.post(route('tenant.projects.complete', project.id), {}, {
                preserveScroll: true,
            });
        }
    };

    const reactivateProject = () => {
        router.post(route('tenant.projects.reactivate', project.id), {}, {
            preserveScroll: true,
        });
    };

    const deleteProject = () => {
        if (confirm(`¿Estás seguro de que deseas eliminar "${project.name}"? Esta acción no se puede deshacer.`)) {
            router.delete(route('tenant.projects.destroy', project.id));
        }
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'No establecida';
        return new Date(dateString).toLocaleDateString('es-MX', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatHours = (seconds: number) => {
        return (seconds / 3600).toFixed(1);
    };

    const getTaskStatusBadge = (status: string) => {
        switch (status) {
            case 'completed':
                return <Badge variant="secondary">Completada</Badge>;
            case 'in_progress':
                return <Badge variant="default">En Progreso</Badge>;
            default:
                return <Badge variant="outline">Pendiente</Badge>;
        }
    };

    const getPriorityLabel = (priority: number) => {
        if (priority >= 4) return 'Alta';
        if (priority >= 2) return 'Media';
        return 'Baja';
    };

    return (
        <AppLayout>
            <Head title={project.name} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route('tenant.projects.index')}>
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-3xl font-bold tracking-tight">{project.name}</h1>
                                <Badge variant={project.status === 'active' ? 'default' : 'secondary'}>
                                    {project.status === 'active' ? 'Activo' : 'Completado'}
                                </Badge>
                            </div>
                            <p className="text-muted-foreground mt-1">
                                Creado el {formatDate(project.created_at)}
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
                            {can_edit && (
                                <DropdownMenuItem asChild>
                                    <Link href={route('tenant.projects.edit', project.id)}>
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar
                                    </Link>
                                </DropdownMenuItem>
                            )}
                            
                            {can_complete && (
                                <>
                                    <DropdownMenuSeparator />
                                    {project.status === 'active' ? (
                                        <DropdownMenuItem onClick={completeProject}>
                                            <CheckCircle className="mr-2 h-4 w-4" />
                                            Marcar como Completado
                                        </DropdownMenuItem>
                                    ) : (
                                        <DropdownMenuItem onClick={reactivateProject}>
                                            <FolderOpen className="mr-2 h-4 w-4" />
                                            Reactivar Proyecto
                                        </DropdownMenuItem>
                                    )}
                                </>
                            )}
                            
                            {can_delete && (
                                <>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        onClick={deleteProject}
                                        className="text-destructive"
                                    >
                                        <Trash2 className="mr-2 h-4 w-4" />
                                        Eliminar
                                    </DropdownMenuItem>
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                {stats && (
                    <div className="grid gap-6 md:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Tareas Totales
                                </CardTitle>
                                <CheckCircle className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.total_tasks}</div>
                                <p className="text-xs text-muted-foreground">
                                    {stats.completed_tasks} completadas
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
                                    {formatHours(stats.billable_hours)} facturables
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Fecha de Vencimiento
                                </CardTitle>
                                <Calendar className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {formatDate(project.due_date)}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Propietario
                                </CardTitle>
                                <User className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{project.user.name}</div>
                                <p className="text-xs text-muted-foreground truncate">
                                    {project.user.email}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                )}

                <Tabs defaultValue="info" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="info">Información</TabsTrigger>
                        <TabsTrigger value="tasks">Tareas</TabsTrigger>
                        <TabsTrigger value="time">Tiempo</TabsTrigger>
                    </TabsList>

                    <TabsContent value="info" className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Detalles del Proyecto</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {project.description && (
                                        <div>
                                            <h3 className="text-sm font-medium text-muted-foreground mb-1">Descripción</h3>
                                            <p className="whitespace-pre-wrap">{project.description}</p>
                                        </div>
                                    )}

                                    {project.client && (
                                        <div>
                                            <h3 className="text-sm font-medium text-muted-foreground mb-1">Cliente</h3>
                                            <div className="flex items-center gap-2">
                                                <Building2 className="h-4 w-4 text-muted-foreground" />
                                                <Link 
                                                    href={route('tenant.clients.show', project.client.id)}
                                                    className="hover:underline"
                                                >
                                                    {project.client.name}
                                                </Link>
                                            </div>
                                        </div>
                                    )}

                                    <div>
                                        <h3 className="text-sm font-medium text-muted-foreground mb-1">Estado</h3>
                                        <div className="flex items-center gap-2">
                                            {project.status === 'active' ? (
                                                <FolderOpen className="h-4 w-4 text-muted-foreground" />
                                            ) : (
                                                <CheckCircle className="h-4 w-4 text-muted-foreground" />
                                            )}
                                            <span className="capitalize">
                                                {project.status === 'active' ? 'Activo' : 'Completado'}
                                            </span>
                                        </div>
                                    </div>

                                    {project.completed_at && (
                                        <div>
                                            <h3 className="text-sm font-medium text-muted-foreground mb-1">Fecha de Finalización</h3>
                                            <div className="flex items-center gap-2">
                                                <Calendar className="h-4 w-4 text-muted-foreground" />
                                                <span>{formatDate(project.completed_at)}</span>
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Etiquetas</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {project.tags && project.tags.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {project.tags.map((tag) => (
                                                <Badge key={tag.id} variant="outline" className="flex items-center gap-1">
                                                    <Tag className="h-3 w-3" />
                                                    {tag.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-muted-foreground">
                                            No hay etiquetas asociadas a este proyecto
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="tasks" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle>Tareas</CardTitle>
                                    <Link href={`${route('tasks.create')}?project_id=${project.id}`}>
                                        <Button size="sm">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Nueva Tarea
                                        </Button>
                                    </Link>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {project.tasks && project.tasks.length > 0 ? (
                                    <div className="space-y-3">
                                        {project.tasks.map((task) => (
                                            <div 
                                                key={task.id} 
                                                className="flex items-center justify-between p-3 border rounded-lg hover:bg-accent/50 transition-colors"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <Link
                                                        href={route('tasks.show', task.id)}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {task.title}
                                                    </Link>
                                                    {getTaskStatusBadge(task.status)}
                                                </div>
                                                <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                                    <div>
                                                        Prioridad: {getPriorityLabel(task.priority)}
                                                    </div>
                                                    {task.user && (
                                                        <div className="flex items-center gap-1">
                                                            <User className="h-3 w-3" />
                                                            {task.user.name}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <CheckCircle className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                        <p className="text-muted-foreground">
                                            No hay tareas asociadas a este proyecto
                                        </p>
                                        <Link href={`${route('tasks.create')}?project_id=${project.id}`}>
                                            <Button className="mt-4">
                                                <Plus className="mr-2 h-4 w-4" />
                                                Crear Tarea
                                            </Button>
                                        </Link>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="time" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle>Registro de Tiempo</CardTitle>
                                    <Link href={`${route('tenant.time.index')}?project_id=${project.id}`}>
                                        <Button size="sm">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Registrar Tiempo
                                        </Button>
                                    </Link>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {/* Aquí iría la lista de entradas de tiempo */}
                                <div className="text-center py-8">
                                    <Clock className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                    <p className="text-muted-foreground">
                                        No hay registros de tiempo para este proyecto
                                    </p>
                                    <Link href={`${route('tenant.time.index')}?project_id=${project.id}`}>
                                        <Button className="mt-4">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Registrar Tiempo
                                        </Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
                </div>
            </div>
        </AppLayout>
    );
}