import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarDays, CheckCircle2, Clock, Plus, ListTodo, Folder, AlertCircle } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    stats?: {
        pending_tasks: number;
        in_progress_tasks: number;
        completed_tasks: number;
        total_tasks: number;
        pending_projects: number;
        completed_projects: number;
        total_projects: number;
        recent_tasks: {
            id: number;
            title: string;
            status: string;
            due_date: string | null;
            priority: number;
        }[];
        overdue_tasks: {
            id: number;
            title: string;
            due_date: string;
            project_id: number;
            project_name: string;
        }[];
    }
}

export default function Dashboard({ stats }: DashboardProps) {
    // Default data if stats are not provided
    const defaultStats = {
        pending_tasks: 5,
        in_progress_tasks: 3,
        completed_tasks: 8,
        total_tasks: 16,
        pending_projects: 2,
        completed_projects: 1,
        total_projects: 3,
        recent_tasks: [
            {
                id: 1,
                title: 'Configurar entorno de desarrollo',
                status: 'completed',
                due_date: '2025-05-10',
                priority: 3
            },
            {
                id: 2,
                title: 'Crear componentes UI para tareas',
                status: 'in_progress',
                due_date: '2025-05-12',
                priority: 4
            },
            {
                id: 3,
                title: 'Implementar sistema de filtros',
                status: 'pending',
                due_date: '2025-05-15',
                priority: 2
            }
        ],
        overdue_tasks: [
            {
                id: 4,
                title: 'Actualizar documentación',
                due_date: '2025-05-01',
                project_id: 1,
                project_name: 'Proyecto Demo'
            }
        ]
    };

    const dashboardStats = stats || defaultStats;
    const totalTasks = dashboardStats.total_tasks;
    const completedPercentage = totalTasks > 0 
        ? Math.round((dashboardStats.completed_tasks / totalTasks) * 100) 
        : 0;

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

    const getPriorityBadge = (priority: number) => {
        if (priority >= 4) return <Badge className="bg-red-600">Alta</Badge>;
        if (priority >= 2) return <Badge className="bg-amber-500">Media</Badge>;
        return <Badge className="bg-blue-500">Baja</Badge>;
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'Sin fecha';
        const date = new Date(dateString);
        return date.toLocaleDateString('es', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                {/* Stats cards */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-lg">Resumen de Tareas</CardTitle>
                            <CardDescription>Estado general del proyecto</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between">
                                <div className="flex flex-col">
                                    <span className="text-3xl font-bold">{dashboardStats.completed_tasks}</span>
                                    <span className="text-sm text-muted-foreground">Tareas completadas</span>
                                </div>
                                <div className="flex flex-col items-end">
                                    <span className="text-3xl font-bold">{totalTasks}</span>
                                    <span className="text-sm text-muted-foreground">Total tareas</span>
                                </div>
                            </div>
                            <div className="mt-4">
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-sm">Progreso</span>
                                    <span className="text-sm font-semibold">{completedPercentage}%</span>
                                </div>
                                <Progress value={completedPercentage} className="h-2" />
                            </div>
                        </CardContent>
                        <CardFooter className="pt-1">
                            <div className="flex w-full justify-between text-xs text-muted-foreground">
                                <div className="flex items-center gap-1">
                                    <Clock className="h-3 w-3" />
                                    <span>{dashboardStats.pending_tasks} pendientes</span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <ListTodo className="h-3 w-3" />
                                    <span>{dashboardStats.in_progress_tasks} en progreso</span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <CheckCircle2 className="h-3 w-3" />
                                    <span>{dashboardStats.completed_tasks} completadas</span>
                                </div>
                            </div>
                        </CardFooter>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-lg">Proyectos</CardTitle>
                            <CardDescription>Vista general de proyectos</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between">
                                <div className="flex flex-col">
                                    <span className="text-3xl font-bold">{dashboardStats.completed_projects}</span>
                                    <span className="text-sm text-muted-foreground">Completados</span>
                                </div>
                                <div className="flex flex-col items-end">
                                    <span className="text-3xl font-bold">{dashboardStats.total_projects}</span>
                                    <span className="text-sm text-muted-foreground">Total proyectos</span>
                                </div>
                            </div>
                            <div className="mt-4">
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-sm">Progreso</span>
                                    <span className="text-sm font-semibold">
                                        {dashboardStats.total_projects > 0 
                                            ? Math.round((dashboardStats.completed_projects / dashboardStats.total_projects) * 100) 
                                            : 0}%
                                    </span>
                                </div>
                                <Progress 
                                    value={dashboardStats.total_projects > 0 
                                        ? Math.round((dashboardStats.completed_projects / dashboardStats.total_projects) * 100) 
                                        : 0} 
                                    className="h-2" 
                                />
                            </div>
                        </CardContent>
                        <CardFooter className="pt-1">
                            <Button variant="outline" asChild className="w-full">
                                <Link href="/projects">
                                    <Folder className="mr-2 h-4 w-4" />
                                    Ver todos los proyectos
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-lg">Acciones rápidas</CardTitle>
                            <CardDescription>Crea nuevos elementos</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2">
                            <Button asChild>
                                <Link href="/tasks/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nueva tarea
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/projects/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nuevo proyecto
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent tasks and overdue tasks */}
                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Tareas recientes</CardTitle>
                            <CardDescription>Las últimas tareas añadidas</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {dashboardStats.recent_tasks.length > 0 ? (
                                    dashboardStats.recent_tasks.map(task => (
                                        <div key={task.id} className="flex items-start justify-between border-b pb-3 last:border-b-0 last:pb-0">
                                            <div>
                                                <Link 
                                                    href={`/tasks/${task.id}`} 
                                                    className="font-medium hover:underline"
                                                >
                                                    {task.title}
                                                </Link>
                                                <div className="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                                                    <CalendarDays className="h-3 w-3" />
                                                    <span>Vence: {formatDate(task.due_date)}</span>
                                                </div>
                                            </div>
                                            <div className="flex gap-1">
                                                {getStatusBadge(task.status)}
                                                {getPriorityBadge(task.priority)}
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center py-4 text-muted-foreground">
                                        No hay tareas recientes
                                    </div>
                                )}
                            </div>
                        </CardContent>
                        <CardFooter>
                            <Button variant="outline" asChild className="w-full">
                                <Link href="/tasks">
                                    Ver todas las tareas
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Tareas Vencidas</CardTitle>
                                    <CardDescription>Tareas que requieren atención</CardDescription>
                                </div>
                                {dashboardStats.overdue_tasks.length > 0 && (
                                    <Badge variant="destructive" className="rounded-full px-3">
                                        {dashboardStats.overdue_tasks.length}
                                    </Badge>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {dashboardStats.overdue_tasks.length > 0 ? (
                                    dashboardStats.overdue_tasks.map(task => (
                                        <div key={task.id} className="flex items-start justify-between border-b pb-3 last:border-b-0 last:pb-0">
                                            <div>
                                                <Link 
                                                    href={`/tasks/${task.id}`} 
                                                    className="font-medium hover:underline"
                                                >
                                                    {task.title}
                                                </Link>
                                                <div className="mt-1 flex flex-col gap-1 text-xs text-muted-foreground">
                                                    <div className="flex items-center gap-1">
                                                        <AlertCircle className="h-3 w-3 text-red-500" />
                                                        <span className="text-red-500">Vencida: {formatDate(task.due_date)}</span>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <Folder className="h-3 w-3" />
                                                        <Link 
                                                            href={`/projects/${task.project_id}`}
                                                            className="hover:underline"
                                                        >
                                                            {task.project_name}
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                            <Button 
                                                variant="outline" 
                                                size="sm" 
                                                asChild
                                                className="mt-2"
                                            >
                                                <Link href={`/tasks/${task.id}/edit`}>
                                                    Actualizar
                                                </Link>
                                            </Button>
                                        </div>
                                    ))
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-8 text-center">
                                        <CheckCircle2 className="mb-2 h-12 w-12 text-green-500" />
                                        <p className="text-muted-foreground">
                                            No tienes tareas vencidas
                                        </p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                        {dashboardStats.overdue_tasks.length > 0 && (
                            <CardFooter>
                                <Button variant="outline" asChild className="w-full">
                                    <Link href="/tasks?status=pending">
                                        Ver todas las tareas pendientes
                                    </Link>
                                </Button>
                            </CardFooter>
                        )}
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
