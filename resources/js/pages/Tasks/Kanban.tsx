import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, CheckCircle2, Circle, Clock, LayoutList, MessageSquare, MoreHorizontal, Plus, Search, Timer, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    DragDropContext,
    Draggable,
    DraggableProvided,
    DraggableStateSnapshot,
    Droppable,
    DroppableProvided,
    DroppableStateSnapshot,
    DropResult,
} from 'react-beautiful-dnd';

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: 'pending' | 'in_progress' | 'completed';
    priority: number;
    due_date: string | null;
    estimated_hours: number | null;
    position: number;
    board_column: string;
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
    time_entries_count?: number;
    total_logged_hours?: number;
}

interface Column {
    id: string;
    title: string;
    tasks: Task[];
    color: string;
    icon: React.ReactNode;
}

interface KanbanProps {
    tasks: Task[];
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
        project_id?: number;
        user_id?: number;
        priority?: string;
    };
}

export default function Kanban({ tasks, projects, users, tags, filters: initialFilters }: PageProps<KanbanProps>) {
    const [searchTerm, setSearchTerm] = useState(initialFilters?.search || '');
    const [filters, setFilters] = useState({
        project_id: initialFilters?.project_id || 'all',
        user_id: initialFilters?.user_id || 'all',
        priority: initialFilters?.priority || 'all',
    });
    const [columns, setColumns] = useState<Column[]>([]);
    const [isDragging, setIsDragging] = useState(false);

    // Initialize columns
    useEffect(() => {
        const tasksByColumn: Record<string, Task[]> = {
            todo: [],
            in_progress: [],
            done: [],
        };

        // Filter tasks
        let filteredTasks = tasks;
        if (searchTerm) {
            filteredTasks = filteredTasks.filter(
                (task) =>
                    task.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    (task.description && task.description.toLowerCase().includes(searchTerm.toLowerCase())),
            );
        }
        if (filters.project_id !== 'all') {
            filteredTasks = filteredTasks.filter((task) => task.project.id === Number(filters.project_id));
        }
        if (filters.user_id !== 'all') {
            filteredTasks = filteredTasks.filter((task) => task.user.id === Number(filters.user_id));
        }
        if (filters.priority !== 'all') {
            const priorityMap = { high: [4, 5], medium: [2, 3], low: [0, 1] };
            const range = priorityMap[filters.priority as keyof typeof priorityMap];
            if (range) {
                filteredTasks = filteredTasks.filter((task) => task.priority >= range[0] && task.priority <= range[1]);
            }
        }

        // Group tasks by column
        filteredTasks.forEach((task) => {
            const column = task.board_column || (task.status === 'completed' ? 'done' : task.status === 'in_progress' ? 'in_progress' : 'todo');
            if (tasksByColumn[column]) {
                tasksByColumn[column].push(task);
            }
        });

        // Sort tasks by position
        Object.keys(tasksByColumn).forEach((column) => {
            tasksByColumn[column].sort((a, b) => a.position - b.position);
        });

        setColumns([
            {
                id: 'todo',
                title: 'Por hacer',
                tasks: tasksByColumn.todo,
                color: 'bg-slate-100 dark:bg-slate-800',
                icon: <Circle className="h-4 w-4" />,
            },
            {
                id: 'in_progress',
                title: 'En progreso',
                tasks: tasksByColumn.in_progress,
                color: 'bg-blue-100 dark:bg-blue-900',
                icon: <Clock className="h-4 w-4" />,
            },
            {
                id: 'done',
                title: 'Completadas',
                tasks: tasksByColumn.done,
                color: 'bg-green-100 dark:bg-green-900',
                icon: <CheckCircle2 className="h-4 w-4" />,
            },
        ]);
    }, [tasks, searchTerm, filters]);

    const handleDragEnd = (result: DropResult) => {
        setIsDragging(false);

        if (!result.destination) return;

        const { source, destination } = result;

        // If dropped in the same position, do nothing
        if (source.droppableId === destination.droppableId && source.index === destination.index) {
            return;
        }

        const sourceColumn = columns.find((col) => col.id === source.droppableId);
        const destColumn = columns.find((col) => col.id === destination.droppableId);

        if (!sourceColumn || !destColumn) return;

        const sourceTasks = [...sourceColumn.tasks];
        const destTasks = source.droppableId === destination.droppableId ? sourceTasks : [...destColumn.tasks];

        // Remove task from source
        const [movedTask] = sourceTasks.splice(source.index, 1);

        // Add task to destination
        destTasks.splice(destination.index, 0, movedTask);

        // Update local state
        const newColumns = columns.map((col) => {
            if (col.id === source.droppableId) {
                return { ...col, tasks: sourceTasks };
            }
            if (col.id === destination.droppableId) {
                return { ...col, tasks: destTasks };
            }
            return col;
        });

        setColumns(newColumns);

        // Update server
        router.post(
            route('tasks.move'),
            {
                task_id: movedTask.id,
                column: destination.droppableId,
                position: destination.index,
                tasks_order: destTasks.map((t) => t.id),
            },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const getPriorityColor = (priority: number) => {
        if (priority >= 4) return 'text-red-600 dark:text-red-400';
        if (priority >= 2) return 'text-amber-600 dark:text-amber-400';
        return 'text-blue-600 dark:text-blue-400';
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

    const handleQuickAdd = (columnId: string) => {
        router.visit(route('tasks.create', { board_column: columnId }));
    };

    return (
        <AppLayout>
            <Head title="Tareas - Kanban" />

            <div className="flex h-full flex-col">
                {/* Header */}
                <div className="border-b p-4">
                    <div className="mx-auto max-w-full">
                        <div className="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Tablero Kanban</h1>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {tasks.length} {tasks.length === 1 ? 'tarea' : 'tareas'} en total
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <Link href={route('tasks.index')}>
                                    <Button variant="outline" size="sm">
                                        <LayoutList className="mr-2 h-4 w-4" />
                                        Vista Lista
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

                        {/* Filters */}
                        <div className="flex flex-col gap-4 lg:flex-row">
                            <div className="relative flex-1">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Input
                                    type="text"
                                    placeholder="Buscar tareas..."
                                    className="pl-10"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>
                            <div className="flex gap-2">
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
                            </div>
                        </div>
                    </div>
                </div>

                {/* Kanban Board */}
                <div className="flex-1 overflow-hidden">
                    <ScrollArea className="h-full">
                        <div className="p-4">
                            <DragDropContext onDragEnd={handleDragEnd} onDragStart={() => setIsDragging(true)}>
                                <div className="flex gap-4">
                                    {columns.map((column) => (
                                        <div key={column.id} className="w-[350px] flex-shrink-0">
                                            <div className={`rounded-lg ${column.color} p-4`}>
                                                <div className="mb-4 flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        {column.icon}
                                                        <h3 className="font-semibold">{column.title}</h3>
                                                        <Badge variant="secondary" className="text-xs">
                                                            {column.tasks.length}
                                                        </Badge>
                                                    </div>
                                                    <Button variant="ghost" size="sm" onClick={() => handleQuickAdd(column.id)}>
                                                        <Plus className="h-4 w-4" />
                                                    </Button>
                                                </div>

                                                <Droppable droppableId={column.id}>
                                                    {(provided: DroppableProvided, snapshot: DroppableStateSnapshot) => (
                                                        <div
                                                            ref={provided.innerRef}
                                                            {...provided.droppableProps}
                                                            className={`min-h-[200px] space-y-2 ${
                                                                snapshot.isDraggingOver ? 'rounded-lg bg-gray-200 dark:bg-gray-700' : ''
                                                            }`}
                                                        >
                                                            {column.tasks.map((task, index) => (
                                                                <Draggable key={task.id} draggableId={task.id.toString()} index={index}>
                                                                    {(provided: DraggableProvided, snapshot: DraggableStateSnapshot) => (
                                                                        <div
                                                                            ref={provided.innerRef}
                                                                            {...provided.draggableProps}
                                                                            {...provided.dragHandleProps}
                                                                            className={`${snapshot.isDragging ? 'rotate-2 shadow-lg' : ''}`}
                                                                        >
                                                                            <Card className="cursor-move transition-shadow hover:shadow-md">
                                                                                <CardHeader className="p-4 pb-3">
                                                                                    <div className="flex items-start justify-between">
                                                                                        <CardTitle className="text-sm leading-snug font-medium">
                                                                                            <Link
                                                                                                href={route('tasks.show', task.id)}
                                                                                                className="hover:text-blue-600 dark:hover:text-blue-400"
                                                                                                onClick={(e) => {
                                                                                                    if (isDragging) {
                                                                                                        e.preventDefault();
                                                                                                    }
                                                                                                }}
                                                                                            >
                                                                                                {task.title}
                                                                                            </Link>
                                                                                        </CardTitle>
                                                                                        <DropdownMenu>
                                                                                            <DropdownMenuTrigger asChild>
                                                                                                <Button
                                                                                                    variant="ghost"
                                                                                                    size="sm"
                                                                                                    className="h-6 w-6 p-0"
                                                                                                >
                                                                                                    <MoreHorizontal className="h-3 w-3" />
                                                                                                </Button>
                                                                                            </DropdownMenuTrigger>
                                                                                            <DropdownMenuContent align="end">
                                                                                                <DropdownMenuItem asChild>
                                                                                                    <Link href={route('tasks.edit', task.id)}>
                                                                                                        Editar
                                                                                                    </Link>
                                                                                                </DropdownMenuItem>
                                                                                                <DropdownMenuSeparator />
                                                                                                <DropdownMenuItem className="text-red-600">
                                                                                                    Eliminar
                                                                                                </DropdownMenuItem>
                                                                                            </DropdownMenuContent>
                                                                                        </DropdownMenu>
                                                                                    </div>
                                                                                </CardHeader>
                                                                                <CardContent className="p-4 pt-0">
                                                                                    {task.description && (
                                                                                        <p className="mb-3 line-clamp-2 text-xs text-gray-600 dark:text-gray-400">
                                                                                            {task.description}
                                                                                        </p>
                                                                                    )}
                                                                                    <div className="space-y-2">
                                                                                        <div className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                                                            <span className="font-medium">{task.project.name}</span>
                                                                                            {task.priority >= 4 && (
                                                                                                <span className={getPriorityColor(task.priority)}>
                                                                                                    • Alta prioridad
                                                                                                </span>
                                                                                            )}
                                                                                        </div>
                                                                                        {task.due_date && (
                                                                                            <div className="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400">
                                                                                                <Calendar className="h-3 w-3" />
                                                                                                <span>{formatDate(task.due_date)}</span>
                                                                                            </div>
                                                                                        )}
                                                                                        <div className="flex items-center justify-between">
                                                                                            <div className="flex items-center gap-1">
                                                                                                <Users className="h-3 w-3 text-gray-400" />
                                                                                                <span className="text-xs text-gray-600 dark:text-gray-400">
                                                                                                    {task.user.name.split(' ')[0]}
                                                                                                </span>
                                                                                            </div>
                                                                                            <div className="flex gap-3 text-xs text-gray-500 dark:text-gray-400">
                                                                                                {task.comments_count! > 0 && (
                                                                                                    <span className="flex items-center gap-1">
                                                                                                        <MessageSquare className="h-3 w-3" />
                                                                                                        {task.comments_count}
                                                                                                    </span>
                                                                                                )}
                                                                                                {task.time_entries_count! > 0 && (
                                                                                                    <span className="flex items-center gap-1">
                                                                                                        <Timer className="h-3 w-3" />
                                                                                                        {task.total_logged_hours?.toFixed(1)}h
                                                                                                    </span>
                                                                                                )}
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    {task.tags && task.tags.length > 0 && (
                                                                                        <div className="mt-3 flex flex-wrap gap-1">
                                                                                            {task.tags.map((tag) => (
                                                                                                <Badge
                                                                                                    key={tag.id}
                                                                                                    variant="outline"
                                                                                                    className="text-xs"
                                                                                                >
                                                                                                    {tag.name}
                                                                                                </Badge>
                                                                                            ))}
                                                                                        </div>
                                                                                    )}
                                                                                </CardContent>
                                                                            </Card>
                                                                        </div>
                                                                    )}
                                                                </Draggable>
                                                            ))}
                                                            {provided.placeholder}
                                                        </div>
                                                    )}
                                                </Droppable>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </DragDropContext>
                        </div>
                        <ScrollBar orientation="horizontal" />
                    </ScrollArea>
                </div>
            </div>
        </AppLayout>
    );
}
