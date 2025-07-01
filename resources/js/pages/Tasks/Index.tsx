import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, CheckCircle2, Circle, Clock, Grid3X3, LayoutList, MoreHorizontal, Plus, Search, SortDesc, Trash2, Users } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: 'pending' | 'in_progress' | 'completed';
    priority: number;
    due_date: string | null;
    estimated_hours: number | null;
    position: number;
    board_column: string | null;
    created_at: string;
    updated_at: string;
    completed_at: string | null;
    project: {
        id: number;
        name: string;
        color?: string;
    };
    user: {
        id: number;
        name: string;
        email: string;
        avatar?: string;
    };
    tags?: Array<{
        id: number;
        name: string;
        color?: string;
    }>;
    subtasks_count?: number;
    comments_count?: number;
}

interface Project {
    id: number;
    name: string;
    color?: string;
}

interface PaginatedTasks {
    data: Task[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props extends PageProps {
    tasks: PaginatedTasks;
    projects: Project[];
    users?: any[];
    tags?: any[];
    filters: {
        search?: string;
        status?: string;
        project_id?: string;
        assignee_id?: string;
        priority?: string;
        due_date_from?: string;
        due_date_to?: string;
        sort?: string;
        direction?: string;
    };
    stats?: {
        total: number;
        pending: number;
        in_progress: number;
        completed: number;
    };
}

export default function Index({ tasks, projects, filters, stats }: Props) {
    const [selectedTasks, setSelectedTasks] = useState<number[]>([]);
    
    // Calcular stats si no vienen del backend
    const tasksList = tasks.data || [];
    const taskStats = stats || {
        total: tasksList.length,
        pending: tasksList.filter(t => t.status === 'pending').length,
        in_progress: tasksList.filter(t => t.status === 'in_progress').length,
        completed: tasksList.filter(t => t.status === 'completed').length,
    };
    const [localSearch, setLocalSearch] = useState(filters.search || '');
    const [viewMode, setViewMode] = useState<'list' | 'kanban'>('list');

    useEffect(() => {
        const timer = setTimeout(() => {
            if (localSearch !== filters.search) {
                router.get(route('tasks.index'), {
                    ...filters,
                    search: localSearch,
                }, {
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [localSearch, filters]);

    const updateFilters = (newFilters: Partial<typeof filters>) => {
        router.get(route('tasks.index'), {
            ...filters,
            ...newFilters,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            setSelectedTasks(tasksList.map(task => task.id));
        } else {
            setSelectedTasks([]);
        }
    };

    const handleSelectTask = (taskId: number, checked: boolean) => {
        if (checked) {
            setSelectedTasks([...selectedTasks, taskId]);
        } else {
            setSelectedTasks(selectedTasks.filter(id => id !== taskId));
        }
    };

    const handleBulkAction = (action: 'complete' | 'in_progress' | 'delete') => {
        if (selectedTasks.length === 0) return;

        const routeMap = {
            complete: 'tasks.bulk-complete',
            in_progress: 'tasks.bulk-in-progress',
            delete: 'tasks.bulk-destroy',
        };

        router.post(route(routeMap[action]), {
            task_ids: selectedTasks,
        }, {
            onSuccess: () => setSelectedTasks([]),
        });
    };

    const getStatusIcon = (status: Task['status']) => {
        switch (status) {
            case 'completed':
                return <CheckCircle2 className="h-4 w-4 text-green-600" />;
            case 'in_progress':
                return <Clock className="h-4 w-4 text-blue-600" />;
            default:
                return <Circle className="h-4 w-4 text-gray-400" />;
        }
    };

    const getStatusBadge = (status: Task['status']) => {
        const variants: Record<Task['status'], 'secondary' | 'default' | 'outline'> = {
            pending: 'secondary',
            in_progress: 'default',
            completed: 'outline',
        };

        const labels = {
            pending: 'Pendiente',
            in_progress: 'En progreso',
            completed: 'Completada',
        };

        return (
            <Badge variant={variants[status]}>
                {labels[status]}
            </Badge>
        );
    };

    const getPriorityBadge = (priority: number) => {
        const variants: Array<{ variant: 'secondary' | 'default' | 'destructive' | 'outline'; label: string }> = [
            { variant: 'secondary', label: 'Baja' },
            { variant: 'default', label: 'Media' },
            { variant: 'outline', label: 'Alta' },
            { variant: 'destructive', label: 'Urgente' },
        ];

        const { variant, label } = variants[Math.min(priority, 3)];
        return <Badge variant={variant}>{label}</Badge>;
    };

    const breadcrumbs = [
        { title: 'Tareas', href: route('tasks.index') },
    ];

    if (viewMode === 'kanban') {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Tareas" />
                
                <div className="py-12">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div className="flex items-center justify-between mb-6">
                    <h1 className="text-3xl font-semibold">Tareas</h1>
                    <div className="flex items-center gap-2">
                        <div className="flex rounded-md shadow-sm">
                            <Button
                                size="sm"
                                variant={viewMode === 'list' ? 'default' : 'outline'}
                                className="rounded-r-none"
                                onClick={() => setViewMode('list')}
                            >
                                <LayoutList className="h-4 w-4" />
                            </Button>
                            <Button
                                size="sm"
                                variant={viewMode === 'kanban' ? 'default' : 'outline'}
                                className="rounded-l-none"
                                onClick={() => setViewMode('kanban')}
                            >
                                <Grid3X3 className="h-4 w-4" />
                            </Button>
                        </div>
                        <Link href={route('tasks.create')}>
                            <Button>
                                <Plus className="h-4 w-4 mr-2" />
                                Nueva Tarea
                            </Button>
                        </Link>
                    </div>
                        </div>

                        <div className="text-center py-12">
                            <p className="text-muted-foreground">Vista Kanban próximamente...</p>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tareas" />
            
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between mb-6">
                <h1 className="text-3xl font-semibold">Tareas</h1>
                <div className="flex items-center gap-2">
                    <div className="flex rounded-md shadow-sm">
                        <Button
                            size="sm"
                            variant={viewMode === 'list' ? 'default' : 'outline'}
                            className="rounded-r-none"
                            onClick={() => setViewMode('list')}
                        >
                            <LayoutList className="h-4 w-4" />
                        </Button>
                        <Button
                            size="sm"
                            variant={viewMode === 'kanban' ? 'default' : 'outline'}
                            className="rounded-l-none"
                            onClick={() => setViewMode('kanban')}
                        >
                            <Grid3X3 className="h-4 w-4" />
                        </Button>
                    </div>
                    <Link href={route('tasks.create')}>
                        <Button>
                            <Plus className="h-4 w-4 mr-2" />
                            Nueva Tarea
                        </Button>
                    </Link>
                </div>
            </div>

            {/* Stats Cards */}
            <div className="grid gap-4 md:grid-cols-4 mb-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Total</CardTitle>
                        <SortDesc className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{taskStats.total}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Pendientes</CardTitle>
                        <Circle className="h-4 w-4 text-gray-400" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{taskStats.pending}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">En Progreso</CardTitle>
                        <Clock className="h-4 w-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{taskStats.in_progress}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Completadas</CardTitle>
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{taskStats.completed}</div>
                    </CardContent>
                </Card>
            </div>

            {/* Filters */}
            <Card className="mb-6">
                <CardContent className="pt-6">
                    <div className="flex flex-col sm:flex-row gap-4">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Buscar tareas..."
                                value={localSearch}
                                onChange={(e) => setLocalSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Select
                            value={filters.status || 'all'}
                            onValueChange={(value) => updateFilters({ status: value === 'all' ? undefined : value })}
                        >
                            <SelectTrigger className="w-full sm:w-[180px]">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los estados</SelectItem>
                                <SelectItem value="pending">Pendiente</SelectItem>
                                <SelectItem value="in_progress">En progreso</SelectItem>
                                <SelectItem value="completed">Completada</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select
                            value={filters.project_id || 'all'}
                            onValueChange={(value) => updateFilters({ project_id: value === 'all' ? undefined : value })}
                        >
                            <SelectTrigger className="w-full sm:w-[180px]">
                                <SelectValue placeholder="Proyecto" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los proyectos</SelectItem>
                                {projects.map((project) => (
                                    <SelectItem key={project.id} value={project.id.toString()}>
                                        {project.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={filters.sort || 'created_desc'}
                            onValueChange={(value) => updateFilters({ sort: value })}
                        >
                            <SelectTrigger className="w-full sm:w-[180px]">
                                <SelectValue placeholder="Ordenar por" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="created_desc">Más recientes</SelectItem>
                                <SelectItem value="created_asc">Más antiguas</SelectItem>
                                <SelectItem value="priority_desc">Mayor prioridad</SelectItem>
                                <SelectItem value="priority_asc">Menor prioridad</SelectItem>
                                <SelectItem value="due_date_asc">Fecha límite próxima</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            {/* Bulk Actions */}
            {selectedTasks.length > 0 && (
                <Card className="mb-6 border-primary">
                    <CardContent className="py-3">
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-muted-foreground">
                                {selectedTasks.length} tarea{selectedTasks.length !== 1 && 's'} seleccionada{selectedTasks.length !== 1 && 's'}
                            </span>
                            <div className="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => handleBulkAction('complete')}
                                >
                                    <CheckCircle2 className="h-4 w-4 mr-2" />
                                    Completar
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => handleBulkAction('in_progress')}
                                >
                                    <Clock className="h-4 w-4 mr-2" />
                                    En progreso
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="text-destructive"
                                    onClick={() => handleBulkAction('delete')}
                                >
                                    <Trash2 className="h-4 w-4 mr-2" />
                                    Eliminar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Tasks Table */}
            <Card>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-12">
                                <Checkbox
                                    checked={selectedTasks.length === tasksList.length && tasksList.length > 0}
                                    onCheckedChange={handleSelectAll}
                                />
                            </TableHead>
                            <TableHead>Tarea</TableHead>
                            <TableHead>Proyecto</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Prioridad</TableHead>
                            <TableHead>Asignado a</TableHead>
                            <TableHead>Fecha límite</TableHead>
                            <TableHead className="text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {tasksList.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={8} className="text-center py-8">
                                    <p className="text-muted-foreground">No se encontraron tareas</p>
                                    <Link href={route('tasks.create')}>
                                        <Button className="mt-4" variant="outline">
                                            <Plus className="h-4 w-4 mr-2" />
                                            Crear primera tarea
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                        ) : (
                            tasksList.map((task) => (
                                <TableRow key={task.id}>
                                    <TableCell>
                                        <Checkbox
                                            checked={selectedTasks.includes(task.id)}
                                            onCheckedChange={(checked) => handleSelectTask(task.id, checked as boolean)}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-start gap-2">
                                            {getStatusIcon(task.status)}
                                            <div className="space-y-1">
                                                <Link
                                                    href={route('tasks.show', task.id)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {task.title}
                                                </Link>
                                                {task.description && (
                                                    <p className="text-sm text-muted-foreground line-clamp-1">
                                                        {task.description}
                                                    </p>
                                                )}
                                                {task.tags && task.tags.length > 0 && (
                                                    <div className="flex gap-1 flex-wrap">
                                                        {task.tags.map((tag) => (
                                                            <Badge
                                                                key={tag.id}
                                                                variant="outline"
                                                                className="text-xs"
                                                                style={{ borderColor: tag.color, color: tag.color }}
                                                            >
                                                                {tag.name}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Link
                                            href={route('tenant.projects.show', task.project.id)}
                                            className="text-sm hover:underline"
                                        >
                                            {task.project.name}
                                        </Link>
                                    </TableCell>
                                    <TableCell>{getStatusBadge(task.status)}</TableCell>
                                    <TableCell>{getPriorityBadge(task.priority)}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <Users className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm">{task.user.name}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {task.due_date ? (
                                            <div className="flex items-center gap-2 text-sm">
                                                <Calendar className="h-4 w-4 text-muted-foreground" />
                                                {new Date(task.due_date).toLocaleDateString('es-ES')}
                                            </div>
                                        ) : (
                                            <span className="text-sm text-muted-foreground">-</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm">
                                                    <MoreHorizontal className="h-4 w-4" />
                                                    <span className="sr-only">Abrir menú</span>
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem asChild>
                                                    <Link href={route('tasks.show', task.id)}>
                                                        Ver detalles
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem asChild>
                                                    <Link href={route('tasks.edit', task.id)}>
                                                        Editar
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                {task.status !== 'completed' && (
                                                    <DropdownMenuItem
                                                        onClick={() => router.post(route('tasks.complete', task.id))}
                                                    >
                                                        Marcar como completada
                                                    </DropdownMenuItem>
                                                )}
                                                {task.status === 'pending' && (
                                                    <DropdownMenuItem
                                                        onClick={() => router.post(route('tasks.in-progress', task.id))}
                                                    >
                                                        Iniciar progreso
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    className="text-destructive"
                                                    onClick={() => {
                                                        if (confirm('¿Estás seguro de eliminar esta tarea?')) {
                                                            router.delete(route('tasks.destroy', task.id));
                                                        }
                                                    }}
                                                >
                                                    Eliminar
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </Card>
                </div>
            </div>
        </AppLayout>
    );
}