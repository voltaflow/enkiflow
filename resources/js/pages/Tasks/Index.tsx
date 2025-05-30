import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
<<<<<<< HEAD
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, CheckCircle2, Circle, Clock, Grid3X3, LayoutList, MoreHorizontal, Plus, Search, SortDesc, Trash2, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
=======
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
>>>>>>> 83fa645f796570e19e5e8ef94c03f015ebf4a8b6

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: 'pending' | 'in_progress' | 'completed';
    priority: number;
    due_date: string | null;
<<<<<<< HEAD
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
=======
    project: {
        id: number;
        name: string;
>>>>>>> 83fa645f796570e19e5e8ef94c03f015ebf4a8b6
    };
    user: {
        id: number;
        name: string;
<<<<<<< HEAD
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
    time_entries_count?: number;
    total_logged_hours?: number;
=======
    };
>>>>>>> 83fa645f796570e19e5e8ef94c03f015ebf4a8b6
}

interface TasksProps {
    tasks: {
        data: Task[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
<<<<<<< HEAD
    projects: Array<{
        id: number;
        name: string;
    }>;
    users: Array<{
        id: number;
        name: string;
    }>;
    tags: Array<{
        id: number;
        name: string;
    }>;
    filters: {
        search?: string;
        status?: string;
        project_id?: number;
        user_id?: number;
        priority?: string;
        sort?: string;
        view?: 'grid' | 'list';
    };
}

export default function Index({ tasks, projects, users, tags, filters: initialFilters }: PageProps<TasksProps>) {
    const [searchTerm, setSearchTerm] = useState(initialFilters?.search || '');
    const [selectedTasks, setSelectedTasks] = useState<number[]>([]);
    const [viewMode, setViewMode] = useState<'grid' | 'list'>(initialFilters?.view || 'grid');
    const [filters, setFilters] = useState({
        status: initialFilters?.status || 'all',
        project_id: initialFilters?.project_id || 'all',
        user_id: initialFilters?.user_id || 'all',
        priority: initialFilters?.priority || 'all',
        sort: initialFilters?.sort || 'created_at',
    });

    // Apply filters via URL params
    useEffect(() => {
        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (filters.status !== 'all') params.append('status', filters.status);
        if (filters.project_id !== 'all') params.append('project_id', filters.project_id.toString());
        if (filters.user_id !== 'all') params.append('user_id', filters.user_id.toString());
        if (filters.priority !== 'all') params.append('priority', filters.priority);
        if (filters.sort !== 'created_at') params.append('sort', filters.sort);
        params.append('view', viewMode);

        const url = route('tasks.index') + '?' + params.toString();
        router.visit(url, { preserveState: true, preserveScroll: true });
    }, [searchTerm, filters, viewMode]);

    const handleSelectAll = () => {
        if (selectedTasks.length === tasks.data.length) {
            setSelectedTasks([]);
        } else {
            setSelectedTasks(tasks.data.map((task) => task.id));
        }
    };

    const handleSelectTask = (taskId: number) => {
        if (selectedTasks.includes(taskId)) {
            setSelectedTasks(selectedTasks.filter((id) => id !== taskId));
        } else {
            setSelectedTasks([...selectedTasks, taskId]);
        }
    };

    const handleBulkAction = (action: string) => {
        if (selectedTasks.length === 0) return;

        switch (action) {
            case 'delete':
                if (confirm(`¿Estás seguro de que quieres eliminar ${selectedTasks.length} tareas?`)) {
                    router.post(route('tasks.bulk-destroy'), { task_ids: selectedTasks });
                }
                break;
            case 'complete':
                router.post(route('tasks.bulk-complete'), { task_ids: selectedTasks });
                break;
            case 'in_progress':
                router.post(route('tasks.bulk-in-progress'), { task_ids: selectedTasks });
                break;
        }
        setSelectedTasks([]);
    };

    const getPriorityBadge = (priority: number) => {
        const priorityConfig = {
            high: { label: 'Alta', className: 'bg-red-500 hover:bg-red-600' },
            medium: { label: 'Media', className: 'bg-amber-500 hover:bg-amber-600' },
            low: { label: 'Baja', className: 'bg-blue-500 hover:bg-blue-600' },
        };

        const config = priority >= 4 ? priorityConfig.high : priority >= 2 ? priorityConfig.medium : priorityConfig.low;
        return <Badge className={config.className}>{config.label}</Badge>;
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'pending':
                return <Circle className="h-4 w-4" />;
            case 'in_progress':
                return <Clock className="h-4 w-4" />;
            case 'completed':
                return <CheckCircle2 className="h-4 w-4" />;
            default:
                return null;
        }
    };

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            pending: { label: 'Pendiente', className: 'bg-slate-500 hover:bg-slate-600' },
            in_progress: { label: 'En progreso', className: 'bg-blue-500 hover:bg-blue-600' },
            completed: { label: 'Completada', className: 'bg-green-500 hover:bg-green-600' },
        };

        const config = statusConfig[status as keyof typeof statusConfig];
        if (!config) return null;

        return (
            <Badge className={`flex items-center gap-1 ${config.className}`}>
                {getStatusIcon(status)}
                {config.label}
            </Badge>
        );
    };

    const handleStatusChange = (taskId: number, status: string) => {
        let routeName;
        switch (status) {
            case 'in_progress':
                routeName = 'tasks.in-progress';
                break;
            case 'completed':
                routeName = 'tasks.complete';
                break;
            default:
                return;
        }

        router.post(
            route(routeName, taskId),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedTasks(selectedTasks.filter((id) => id !== taskId));
                },
            },
        );
    };

    const handleDelete = (taskId: number) => {
        if (confirm('¿Estás seguro de que quieres eliminar esta tarea?')) {
            router.delete(route('tasks.destroy', taskId), {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedTasks(selectedTasks.filter((id) => id !== taskId));
                },
            });
        }
    };

    const formatDate = (date: string | null) => {
        if (!date) return null;
        const d = new Date(date);
        const today = new Date();
        const diffTime = d.getTime() - today.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays === 0) return 'Hoy';
        if (diffDays === 1) return 'Mañana';
        if (diffDays === -1) return 'Ayer';
        if (diffDays > 0 && diffDays <= 7) return `En ${diffDays} días`;
        if (diffDays < 0) return `Hace ${Math.abs(diffDays)} días`;

        return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
    };

    return (
        <AppLayout>
            <Head title="Tareas" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Tareas</h1>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                {tasks.total} {tasks.total === 1 ? 'tarea' : 'tareas'} en total
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Link href={route('tasks.kanban')}>
                                <Button variant="outline" size="sm">
                                    <Grid3X3 className="mr-2 h-4 w-4" />
                                    Vista Kanban
                                </Button>
                            </Link>
                            <Button asChild>
                                <Link href={route('tasks.create')}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nueva tarea
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Filters and Search */}
                    <div className="mb-6 space-y-4">
                        <div className="flex flex-col gap-4 lg:flex-row">
                            {/* Search */}
                            <div className="relative flex-1">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Input
                                    type="text"
                                    placeholder="Buscar por título o descripción..."
                                    className="pl-10"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>

                            {/* Filters */}
                            <div className="flex gap-2">
                                <Select value={filters.status} onValueChange={(value) => setFilters({ ...filters, status: value })}>
                                    <SelectTrigger className="w-[140px]">
                                        <SelectValue placeholder="Estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="pending">Pendiente</SelectItem>
                                        <SelectItem value="in_progress">En progreso</SelectItem>
                                        <SelectItem value="completed">Completada</SelectItem>
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={filters.project_id.toString()}
                                    onValueChange={(value) => setFilters({ ...filters, project_id: value })}
                                >
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Proyecto" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los proyectos</SelectItem>
                                        {projects?.map((project) => (
                                            <SelectItem key={project.id} value={project.id.toString()}>
                                                {project.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select value={filters.user_id.toString()} onValueChange={(value) => setFilters({ ...filters, user_id: value })}>
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Asignado a" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        {users?.map((user) => (
                                            <SelectItem key={user.id} value={user.id.toString()}>
                                                {user.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select value={filters.priority} onValueChange={(value) => setFilters({ ...filters, priority: value })}>
                                    <SelectTrigger className="w-[140px]">
                                        <SelectValue placeholder="Prioridad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas</SelectItem>
                                        <SelectItem value="high">Alta</SelectItem>
                                        <SelectItem value="medium">Media</SelectItem>
                                        <SelectItem value="low">Baja</SelectItem>
                                    </SelectContent>
                                </Select>

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" size="sm">
                                            <SortDesc className="mr-2 h-4 w-4" />
                                            Ordenar
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuLabel>Ordenar por</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem onClick={() => setFilters({ ...filters, sort: 'created_at' })}>
                                            Fecha de creación
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => setFilters({ ...filters, sort: 'due_date' })}>
                                            Fecha de vencimiento
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => setFilters({ ...filters, sort: 'priority' })}>Prioridad</DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => setFilters({ ...filters, sort: 'title' })}>Título</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>

                        {/* View Toggle and Bulk Actions */}
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                {selectedTasks.length > 0 && (
                                    <>
                                        <span className="text-sm text-gray-600 dark:text-gray-400">{selectedTasks.length} seleccionadas</span>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="outline" size="sm">
                                                    Acciones
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent>
                                                <DropdownMenuItem onClick={() => handleBulkAction('complete')}>
                                                    <CheckCircle2 className="mr-2 h-4 w-4" />
                                                    Marcar como completadas
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleBulkAction('in_progress')}>
                                                    <Clock className="mr-2 h-4 w-4" />
                                                    Marcar como en progreso
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem onClick={() => handleBulkAction('delete')} className="text-red-600">
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    Eliminar
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <Button variant={viewMode === 'grid' ? 'default' : 'ghost'} size="sm" onClick={() => setViewMode('grid')}>
                                    <Grid3X3 className="h-4 w-4" />
                                </Button>
                                <Button variant={viewMode === 'list' ? 'default' : 'ghost'} size="sm" onClick={() => setViewMode('list')}>
                                    <LayoutList className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Task List/Grid */}
                    {viewMode === 'grid' ? (
                        <div className="grid gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                            {tasks.data.length > 0 ? (
                                tasks.data.map((task) => (
                                    <Card key={task.id} className="relative overflow-hidden transition-shadow hover:shadow-lg">
                                        {/* Selection checkbox */}
                                        <div className="absolute top-2 right-2 z-10">
                                            <Checkbox
                                                checked={selectedTasks.includes(task.id)}
                                                onCheckedChange={() => handleSelectTask(task.id)}
                                                className="bg-white dark:bg-gray-800"
                                            />
                                        </div>
                                        <CardHeader className="pb-3">
                                            <div className="pr-8">
                                                <CardTitle className="text-lg leading-tight">
                                                    <Link
                                                        href={route('tasks.show', task.id)}
                                                        className="hover:text-blue-600 dark:hover:text-blue-400"
                                                    >
                                                        {task.title}
                                                    </Link>
                                                </CardTitle>
                                                <div className="mt-2 flex flex-wrap items-center gap-2">
                                                    <span className="text-sm text-gray-600 dark:text-gray-400">{task.project.name}</span>
                                                    {task.due_date && (
                                                        <span className="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                                            <Calendar className="h-3 w-3" />
                                                            {formatDate(task.due_date)}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </CardHeader>
                                        <CardContent className="pb-3">
                                            {task.description && (
                                                <p className="line-clamp-2 text-sm text-gray-600 dark:text-gray-300">{task.description}</p>
                                            )}
                                            <div className="mt-3 flex flex-wrap gap-2">
                                                {getStatusBadge(task.status)}
                                                {getPriorityBadge(task.priority)}
                                                {task.tags?.map((tag) => (
                                                    <Badge key={tag.id} variant="outline" className="text-xs">
                                                        {tag.name}
                                                    </Badge>
                                                ))}
                                            </div>
                                            {(task.subtasks_count! > 0 || task.comments_count! > 0 || task.time_entries_count! > 0) && (
                                                <div className="mt-3 flex gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                    {task.subtasks_count! > 0 && <span>{task.subtasks_count} subtareas</span>}
                                                    {task.comments_count! > 0 && <span>{task.comments_count} comentarios</span>}
                                                    {task.time_entries_count! > 0 && <span>{task.total_logged_hours?.toFixed(1)}h registradas</span>}
                                                </div>
                                            )}
                                        </CardContent>
                                        <Separator />
                                        <CardFooter className="flex items-center justify-between py-3">
                                            <div className="flex items-center gap-2">
                                                <Users className="h-4 w-4 text-gray-400" />
                                                <span className="text-sm text-gray-600 dark:text-gray-400">{task.user.name}</span>
                                            </div>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="sm">
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    {task.status !== 'completed' && (
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                handleStatusChange(task.id, task.status === 'pending' ? 'in_progress' : 'completed')
                                                            }
                                                        >
                                                            {task.status === 'pending' ? 'Iniciar' : 'Completar'}
                                                        </DropdownMenuItem>
                                                    )}
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('tasks.edit', task.id)}>Editar</Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem onClick={() => handleDelete(task.id)} className="text-red-600">
                                                        Eliminar
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </CardFooter>
                                    </Card>
                                ))
                            ) : (
                                <div className="col-span-3 py-12 text-center">
                                    <p className="text-gray-500 dark:text-gray-400">
                                        No se encontraron tareas.{' '}
                                        <Link href={route('tasks.create')} className="text-blue-500 hover:underline">
                                            Crea una nueva tarea
                                        </Link>
                                    </p>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">
                                            <Checkbox
                                                checked={selectedTasks.length === tasks.data.length && tasks.data.length > 0}
                                                onCheckedChange={handleSelectAll}
                                            />
                                        </TableHead>
                                        <TableHead>Tarea</TableHead>
                                        <TableHead>Proyecto</TableHead>
                                        <TableHead>Asignado a</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Prioridad</TableHead>
                                        <TableHead>Vencimiento</TableHead>
                                        <TableHead className="text-right">Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tasks.data.length > 0 ? (
                                        tasks.data.map((task) => (
                                            <TableRow key={task.id} className="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedTasks.includes(task.id)}
                                                        onCheckedChange={() => handleSelectTask(task.id)}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <Link
                                                            href={route('tasks.show', task.id)}
                                                            className="font-medium hover:text-blue-600 dark:hover:text-blue-400"
                                                        >
                                                            {task.title}
                                                        </Link>
                                                        {task.description && (
                                                            <p className="mt-1 line-clamp-1 text-sm text-gray-500 dark:text-gray-400">
                                                                {task.description}
                                                            </p>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm">{task.project.name}</span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm">{task.user.name}</span>
                                                </TableCell>
                                                <TableCell>{getStatusBadge(task.status)}</TableCell>
                                                <TableCell>{getPriorityBadge(task.priority)}</TableCell>
                                                <TableCell>{task.due_date && <span className="text-sm">{formatDate(task.due_date)}</span>}</TableCell>
                                                <TableCell className="text-right">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="sm">
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            {task.status !== 'completed' && (
                                                                <DropdownMenuItem
                                                                    onClick={() =>
                                                                        handleStatusChange(
                                                                            task.id,
                                                                            task.status === 'pending' ? 'in_progress' : 'completed',
                                                                        )
                                                                    }
                                                                >
                                                                    {task.status === 'pending' ? 'Iniciar' : 'Completar'}
                                                                </DropdownMenuItem>
                                                            )}
                                                            <DropdownMenuItem asChild>
                                                                <Link href={route('tasks.edit', task.id)}>Editar</Link>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuSeparator />
                                                            <DropdownMenuItem onClick={() => handleDelete(task.id)} className="text-red-600">
                                                                Eliminar
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={8} className="py-12 text-center">
                                                <p className="text-gray-500 dark:text-gray-400">
                                                    No se encontraron tareas.{' '}
                                                    <Link href={route('tasks.create')} className="text-blue-500 hover:underline">
                                                        Crea una nueva tarea
                                                    </Link>
                                                </p>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    )}

=======
}

export default function Index({ tasks }: PageProps<TasksProps>) {
    const [searchTerm, setSearchTerm] = useState('');

    const filteredTasks = tasks.data.filter(
        (task) =>
            task.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (task.description && task.description.toLowerCase().includes(searchTerm.toLowerCase())),
    );

    const getPriorityBadge = (priority: number) => {
        if (priority >= 4) return <Badge className="bg-red-600">Alta</Badge>;
        if (priority >= 2) return <Badge className="bg-amber-500">Media</Badge>;
        return <Badge className="bg-blue-500">Baja</Badge>;
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge className="bg-slate-500">Pendiente</Badge>;
            case 'in_progress':
                return <Badge className="bg-blue-500">En progreso</Badge>;
            case 'completed':
                return <Badge className="bg-green-600">Completada</Badge>;
            default:
                return null;
        }
    };

    const handleStatusChange = (taskId: number, status: string) => {
        let routeName;
        switch (status) {
            case 'in_progress':
                routeName = 'tasks.in-progress';
                break;
            case 'completed':
                routeName = 'tasks.complete';
                break;
            default:
                return;
        }

        router.post(route(routeName, taskId));
    };

    const handleDelete = (taskId: number) => {
        if (confirm('¿Estás seguro de que quieres eliminar esta tarea?')) {
            router.delete(route('tasks.destroy', taskId));
        }
    };

    return (
        <AppLayout>
            <Head title="Tareas" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Tareas</h1>
                        <Button asChild>
                            <Link href={route('tasks.create')}>Crear tarea</Link>
                        </Button>
                    </div>

                    <div className="mb-6">
                        <input
                            type="text"
                            placeholder="Buscar tareas..."
                            className="w-full rounded-md border border-gray-300 p-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </div>

                    <div className="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                        {filteredTasks.length > 0 ? (
                            filteredTasks.map((task) => (
                                <Card key={task.id} className="shadow-sm">
                                    <CardHeader className="pb-2">
                                        <div className="flex items-start justify-between">
                                            <CardTitle className="text-lg">
                                                <Link href={route('tasks.show', task.id)} className="hover:underline">
                                                    {task.title}
                                                </Link>
                                            </CardTitle>
                                            <div className="flex gap-1">
                                                {getPriorityBadge(task.priority)}
                                                {getStatusBadge(task.status)}
                                            </div>
                                        </div>
                                        <CardDescription>Proyecto: {task.project.name}</CardDescription>
                                    </CardHeader>
                                    <CardContent className="pb-2">
                                        <p className="line-clamp-2 text-sm text-gray-600 dark:text-gray-300">
                                            {task.description || 'Sin descripción'}
                                        </p>
                                        {task.due_date && (
                                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                Vencimiento: {new Date(task.due_date).toLocaleDateString()}
                                            </p>
                                        )}
                                    </CardContent>
                                    <Separator />
                                    <CardFooter className="flex items-center justify-between pt-4 pb-2">
                                        <span className="text-xs text-gray-500 dark:text-gray-400">Asignado a: {task.user.name}</span>
                                        <div className="flex gap-2">
                                            {task.status !== 'completed' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        handleStatusChange(task.id, task.status === 'pending' ? 'in_progress' : 'completed')
                                                    }
                                                >
                                                    {task.status === 'pending' ? 'Iniciar' : 'Completar'}
                                                </Button>
                                            )}
                                            <Button size="sm" variant="outline" asChild>
                                                <Link href={route('tasks.edit', task.id)}>Editar</Link>
                                            </Button>
                                            <Button size="sm" variant="destructive" onClick={() => handleDelete(task.id)}>
                                                Eliminar
                                            </Button>
                                        </div>
                                    </CardFooter>
                                </Card>
                            ))
                        ) : (
                            <div className="col-span-3 py-12 text-center">
                                <p className="text-gray-500 dark:text-gray-400">
                                    No se encontraron tareas.{' '}
                                    <Link href={route('tasks.create')} className="text-blue-500 hover:underline">
                                        Crea una nueva tarea
                                    </Link>
                                </p>
                            </div>
                        )}
                    </div>

>>>>>>> 83fa645f796570e19e5e8ef94c03f015ebf4a8b6
                    {tasks.last_page > 1 && (
                        <div className="mt-6 flex justify-center">
                            <nav className="flex gap-2">
                                {Array.from({ length: tasks.last_page }, (_, i) => i + 1).map((page) => (
                                    <Link
                                        key={page}
                                        href={route('tasks.index', { page })}
                                        className={`rounded px-3 py-1 ${
                                            page === tasks.current_page
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                        }`}
                                    >
                                        {page}
                                    </Link>
                                ))}
                            </nav>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
