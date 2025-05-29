import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { CalendarDays, CheckCircle, Clock, LayoutGrid, List, TrendingUp, User, Users } from 'lucide-react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

// Define the component props with TypeScript interfaces
interface UserTask {
    id: number;
    title: string;
    status: string;
    due_date: string | null;
    priority: number;
    project: {
        id: number;
        name: string;
    };
}

interface BasicStats {
    userTasksCount: number;
    userPendingTasksCount: number;
    userInProgressTasksCount: number;
    userCompletedTasksCount: number;
    userOverdueTasksCount: number;
    userProjectsCount: number;
    userActiveProjectsCount: number;
    userCompletedProjectsCount: number;
    recentTasks: UserTask[];
}

interface Project {
    id: number;
    name: string;
    tasks_count: number;
}

interface ExtendedStats {
    allTasksCount: number;
    allPendingTasksCount: number;
    allInProgressTasksCount: number;
    allCompletedTasksCount: number;
    allProjectsCount: number;
    allActiveProjectsCount: number;
    allCompletedProjectsCount: number;
    taskCompletionTrend: Record<string, number>;
    taskCreationTrend: Record<string, number>;
    topProjects: Project[];
}

interface ProjectStats {
    statusDistribution: Record<string, number>;
    creationTrend: Record<string, number>;
}

interface TaskWithUser {
    id: number;
    title: string;
    due_date: string | null;
    project: {
        id: number;
        name: string;
    };
    user: {
        id: number;
        name: string;
    };
}

interface TaskStats {
    priorityDistribution: Record<string, number>;
    userTasksByStatus: Record<string, number>;
    overdueTasks: TaskWithUser[];
    dueSoonTasks: TaskWithUser[];
}

interface UserActivityItem {
    user_id: number;
    user_name: string;
    count: number;
}

interface ActivityItem {
    type: string;
    user_id: number;
    user_name: string;
    entity_id: number;
    entity_name: string;
    date: string;
}

interface UserActivity {
    topUsersByCompletedTasks: UserActivityItem[];
    topUsersByOpenTasks: UserActivityItem[];
    recentActivity: ActivityItem[];
}

interface DashboardProps {
    basicStats: BasicStats;
    extendedStats: ExtendedStats | null;
    projectStats: ProjectStats;
    taskStats: TaskStats;
    userActivity: UserActivity | null;
    canViewStats: boolean;
}

// Helper function to format dates
const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'Sin fecha';

    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Helper function to get color for task status
const getStatusColor = (status: string): string => {
    switch (status) {
        case 'pending':
            return 'text-slate-600';
        case 'in_progress':
            return 'text-blue-600';
        case 'completed':
            return 'text-green-600';
        default:
            return 'text-gray-600';
    }
};

// Helper function to get formatted task status
const getFormattedStatus = (status: string): string => {
    switch (status) {
        case 'pending':
            return 'Pendiente';
        case 'in_progress':
            return 'En progreso';
        case 'completed':
            return 'Completada';
        default:
            return status;
    }
};

// Helper function to get activity type text
const getActivityTypeText = (type: string, entityName: string): string => {
    switch (type) {
        case 'task_created':
            return `creó la tarea "${entityName}"`;
        case 'task_completed':
            return `completó la tarea "${entityName}"`;
        case 'project_created':
            return `creó el proyecto "${entityName}"`;
        default:
            return `realizó una acción en "${entityName}"`;
    }
};

// Helper function to convert trend data for Recharts
const convertTrendDataForChart = (trendData: Record<string, number>): Array<{ date: string; count: number }> => {
    return Object.entries(trendData).map(([date, count]) => ({
        date: formatDate(date),
        count,
    }));
};

// Helper to convert two trend datasets for comparative chart
const combineChartData = (
    dataset1: Record<string, number>,
    dataset2: Record<string, number>,
    label1: string = 'Completadas',
    label2: string = 'Creadas',
): Array<{ date: string; [key: string]: string | number }> => {
    const dates = Object.keys(dataset1);

    return dates.map((date) => ({
        date: formatDate(date),
        [label1]: dataset1[date] || 0,
        [label2]: dataset2[date] || 0,
    }));
};

// Helper function to convert distribution data for pie chart
const convertDistributionForPieChart = (distribution: Record<string, number>): Array<{ name: string; rawStatus: string; value: number }> => {
    return Object.entries(distribution).map(([status, count]) => ({
        name: getFormattedStatus(status),
        rawStatus: status, // Keep the original status for color mapping
        value: count,
    }));
};

// COLORS for pie charts
const STATUS_COLORS: Record<string, string> = {
    pending: '#94a3b8', // slate-400
    in_progress: '#3b82f6', // blue-500
    completed: '#10b981', // emerald-500
    active: '#0ea5e9', // sky-500
    inactive: '#6b7280', // gray-500
    '0': '#94a3b8', // Priority 0 (low)
    '1': '#0ea5e9', // Priority 1
    '2': '#f59e0b', // Priority 2
    '3': '#f97316', // Priority 3
    '4': '#dc2626', // Priority 4
    '5': '#991b1b', // Priority 5 (high)
};

const PIE_COLORS = ['#3b82f6', '#10b981', '#0ea5e9', '#94a3b8', '#6b7280', '#8b5cf6'];

export default function Dashboard({ basicStats, extendedStats, projectStats, taskStats, userActivity, canViewStats }: DashboardProps) {
    // Calculate percentages for task completion
    const userTaskCompletionPercent =
        basicStats.userTasksCount > 0 ? Math.round((basicStats.userCompletedTasksCount / basicStats.userTasksCount) * 100) : 0;

    const allTaskCompletionPercent =
        extendedStats && extendedStats.allTasksCount > 0 ? Math.round((extendedStats.allCompletedTasksCount / extendedStats.allTasksCount) * 100) : 0;

    // Prepare project status distribution data for pie chart
    const projectStatusData = projectStats ? convertDistributionForPieChart(projectStats.statusDistribution) : [];
    const taskPriorityData = taskStats ? convertDistributionForPieChart(taskStats.priorityDistribution) : [];

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-8">
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Dashboard</h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Bienvenido a tu espacio de trabajo. Aquí tienes un resumen de tu actividad.
                        </p>
                    </div>

                    {/* Personal Task Summary */}
                    <div className="mb-10">
                        <h2 className="mb-4 flex items-center text-xl font-semibold">
                            <User className="mr-2 h-5 w-5" />
                            Tu Resumen Personal
                        </h2>

                        <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-muted-foreground text-sm font-medium">Tareas Asignadas</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{basicStats.userTasksCount}</div>
                                    <Progress value={userTaskCompletionPercent} className="mt-2" />
                                    <p className="text-muted-foreground mt-2 text-xs">
                                        {basicStats.userCompletedTasksCount} de {basicStats.userTasksCount} completadas ({userTaskCompletionPercent}%)
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-muted-foreground text-sm font-medium">Tareas Pendientes</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{basicStats.userPendingTasksCount}</div>
                                    <div className="mt-2 flex items-center justify-between">
                                        <span className="text-muted-foreground text-xs font-medium">En Progreso</span>
                                        <span className="text-xs font-medium">{basicStats.userInProgressTasksCount}</span>
                                    </div>
                                    <div className="mt-1 flex items-center justify-between">
                                        <span className="text-muted-foreground text-xs font-medium">Pendientes</span>
                                        <span className="text-xs font-medium">
                                            {basicStats.userPendingTasksCount - basicStats.userInProgressTasksCount}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-muted-foreground text-sm font-medium">Tareas Vencidas</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-red-600">{basicStats.userOverdueTasksCount}</div>
                                    <div className="mt-2">
                                        <Button
                                            asChild
                                            variant="ghost"
                                            size="sm"
                                            className="text-muted-foreground hover:text-primary h-auto p-0 text-xs"
                                        >
                                            <Link href={route('tasks.index', { status: 'overdue' })}>Ver tareas vencidas →</Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-muted-foreground text-sm font-medium">Tus Proyectos</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{basicStats.userProjectsCount}</div>
                                    <div className="mt-2 flex items-center justify-between">
                                        <span className="text-muted-foreground text-xs font-medium">Activos</span>
                                        <span className="text-xs font-medium">{basicStats.userActiveProjectsCount}</span>
                                    </div>
                                    <div className="mt-1 flex items-center justify-between">
                                        <span className="text-muted-foreground text-xs font-medium">Completados</span>
                                        <span className="text-xs font-medium">{basicStats.userCompletedProjectsCount}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Recent User Tasks */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Tus Tareas Recientes</CardTitle>
                                <CardDescription>Últimas tareas creadas o actualizadas asignadas a ti</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {basicStats.recentTasks.length > 0 ? (
                                    <div className="space-y-4">
                                        {basicStats.recentTasks.map((task) => (
                                            <div key={task.id} className="flex items-start justify-between border-b pb-3 last:border-0 last:pb-0">
                                                <div>
                                                    <Link href={route('tasks.show', task.id)} className="font-medium hover:underline">
                                                        {task.title}
                                                    </Link>
                                                    <div className="text-muted-foreground mt-1 text-sm">
                                                        Proyecto:{' '}
                                                        <Link href={route('tenant.projects.show', task.project.id)} className="hover:underline">
                                                            {task.project.name}
                                                        </Link>
                                                    </div>
                                                </div>
                                                <div className="flex flex-col items-end">
                                                    <span className={`text-sm ${getStatusColor(task.status)}`}>
                                                        {getFormattedStatus(task.status)}
                                                    </span>
                                                    {task.due_date && (
                                                        <span className="text-muted-foreground mt-1 text-xs">Vence: {formatDate(task.due_date)}</span>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-muted-foreground py-4 text-center">No tienes tareas recientes</div>
                                )}

                                <div className="mt-4">
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={route('tasks.index')}>Ver todas tus tareas</Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Workspace Statistics */}
                    {canViewStats && extendedStats && (
                        <div className="mb-10">
                            <h2 className="mb-4 flex items-center text-xl font-semibold">
                                <TrendingUp className="mr-2 h-5 w-5" />
                                Estadísticas del Espacio
                            </h2>

                            <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <Card>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-muted-foreground text-sm font-medium">Total de Tareas</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{extendedStats.allTasksCount}</div>
                                        <Progress value={allTaskCompletionPercent} className="mt-2" />
                                        <p className="text-muted-foreground mt-2 text-xs">
                                            {extendedStats.allCompletedTasksCount} de {extendedStats.allTasksCount} completadas (
                                            {allTaskCompletionPercent}%)
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-muted-foreground text-sm font-medium">Estado de Tareas</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-2">
                                        <div className="text-xs">
                                            <div className="mb-1 flex justify-between">
                                                <span className="text-muted-foreground">Pendientes</span>
                                                <span>{extendedStats.allPendingTasksCount}</span>
                                            </div>
                                            <Progress
                                                value={(extendedStats.allPendingTasksCount / extendedStats.allTasksCount) * 100}
                                                className="h-1"
                                            />
                                        </div>
                                        <div className="text-xs">
                                            <div className="mb-1 flex justify-between">
                                                <span className="text-muted-foreground">En Progreso</span>
                                                <span>{extendedStats.allInProgressTasksCount}</span>
                                            </div>
                                            <Progress
                                                value={(extendedStats.allInProgressTasksCount / extendedStats.allTasksCount) * 100}
                                                className="h-1"
                                            />
                                        </div>
                                        <div className="text-xs">
                                            <div className="mb-1 flex justify-between">
                                                <span className="text-muted-foreground">Completadas</span>
                                                <span>{extendedStats.allCompletedTasksCount}</span>
                                            </div>
                                            <Progress
                                                value={(extendedStats.allCompletedTasksCount / extendedStats.allTasksCount) * 100}
                                                className="h-1"
                                            />
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-muted-foreground text-sm font-medium">Total de Proyectos</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{extendedStats.allProjectsCount}</div>
                                        <div className="mt-2 flex items-center justify-between">
                                            <span className="text-muted-foreground text-xs font-medium">Activos</span>
                                            <span className="text-xs font-medium">{extendedStats.allActiveProjectsCount}</span>
                                        </div>
                                        <div className="mt-1 flex items-center justify-between">
                                            <span className="text-muted-foreground text-xs font-medium">Completados</span>
                                            <span className="text-xs font-medium">{extendedStats.allCompletedProjectsCount}</span>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-muted-foreground text-sm font-medium">Proyectos Destacados</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-2 text-xs">
                                            {extendedStats.topProjects.slice(0, 3).map((project) => (
                                                <div key={project.id} className="flex items-center justify-between">
                                                    <Link
                                                        href={route('tenant.projects.show', project.id)}
                                                        className="max-w-[180px] truncate hover:underline"
                                                    >
                                                        {project.name}
                                                    </Link>
                                                    <span className="font-medium">{project.tasks_count} tareas</span>
                                                </div>
                                            ))}
                                        </div>
                                        {extendedStats.topProjects.length > 0 && (
                                            <div className="mt-3">
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-muted-foreground hover:text-primary h-auto p-0 text-xs"
                                                >
                                                    <Link href={route('tenant.projects.index')}>Ver todos los proyectos →</Link>
                                                </Button>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Charts section - Simplified for example */}
                            <div className="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Tareas Completadas (últimos 30 días)</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="h-[300px]">
                                            <ResponsiveContainer width="100%" height="100%">
                                                <AreaChart
                                                    data={convertTrendDataForChart(extendedStats.taskCompletionTrend)}
                                                    margin={{ top: 10, right: 30, left: 0, bottom: 0 }}
                                                >
                                                    <CartesianGrid strokeDasharray="3 3" />
                                                    <XAxis
                                                        dataKey="date"
                                                        tickMargin={10}
                                                        tickFormatter={(value) => value.split(' ')[0]} // Show only the day part
                                                    />
                                                    <YAxis allowDecimals={false} />
                                                    <Tooltip
                                                        formatter={(value: number) => [value, 'Tareas Completadas']}
                                                        labelFormatter={(label) => `Fecha: ${label}`}
                                                    />
                                                    <Area
                                                        type="monotone"
                                                        dataKey="count"
                                                        name="Tareas Completadas"
                                                        stroke="#10b981"
                                                        fill="#10b981"
                                                        fillOpacity={0.2}
                                                    />
                                                </AreaChart>
                                            </ResponsiveContainer>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Comparativa de Tareas (últimos 30 días)</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="h-[300px]">
                                            <ResponsiveContainer width="100%" height="100%">
                                                <BarChart
                                                    data={combineChartData(
                                                        extendedStats.taskCompletionTrend,
                                                        extendedStats.taskCreationTrend,
                                                        'Completadas',
                                                        'Creadas',
                                                    )}
                                                    margin={{ top: 20, right: 30, left: 0, bottom: 0 }}
                                                >
                                                    <CartesianGrid strokeDasharray="3 3" />
                                                    <XAxis
                                                        dataKey="date"
                                                        tickMargin={10}
                                                        tickFormatter={(value) => value.split(' ')[0]} // Show only the day part
                                                    />
                                                    <YAxis allowDecimals={false} />
                                                    <Tooltip
                                                        formatter={(value: number, name: string) => [value, name]}
                                                        labelFormatter={(label) => `Fecha: ${label}`}
                                                    />
                                                    <Legend />
                                                    <Bar dataKey="Creadas" name="Tareas Creadas" fill="#3b82f6" barSize={20} />
                                                    <Bar dataKey="Completadas" name="Tareas Completadas" fill="#10b981" barSize={20} />
                                                </BarChart>
                                            </ResponsiveContainer>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Distribution Charts */}
                            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Distribución de Estados de Proyectos</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="h-[300px]">
                                            <ResponsiveContainer width="100%" height="100%">
                                                <PieChart>
                                                    <Pie
                                                        data={projectStatusData}
                                                        cx="50%"
                                                        cy="50%"
                                                        labelLine={true}
                                                        outerRadius={80}
                                                        fill="#8884d8"
                                                        dataKey="value"
                                                        nameKey="name"
                                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                                    >
                                                        {projectStatusData.map((entry, index) => (
                                                            <Cell
                                                                key={`cell-${index}`}
                                                                fill={STATUS_COLORS[entry.rawStatus] || PIE_COLORS[index % PIE_COLORS.length]}
                                                            />
                                                        ))}
                                                    </Pie>
                                                    <Tooltip formatter={(value) => [`${value} proyectos`, '']} />
                                                    <Legend />
                                                </PieChart>
                                            </ResponsiveContainer>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Distribución de Prioridades de Tareas</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="h-[300px]">
                                            <ResponsiveContainer width="100%" height="100%">
                                                <PieChart>
                                                    <Pie
                                                        data={taskPriorityData}
                                                        cx="50%"
                                                        cy="50%"
                                                        labelLine={true}
                                                        outerRadius={80}
                                                        fill="#8884d8"
                                                        dataKey="value"
                                                        nameKey="name"
                                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                                    >
                                                        {taskPriorityData.map((entry, index) => (
                                                            <Cell
                                                                key={`cell-${index}`}
                                                                fill={STATUS_COLORS[entry.rawStatus] || PIE_COLORS[index % PIE_COLORS.length]}
                                                            />
                                                        ))}
                                                    </Pie>
                                                    <Tooltip formatter={(value) => [`${value} tareas`, '']} />
                                                    <Legend />
                                                </PieChart>
                                            </ResponsiveContainer>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    )}

                    {/* Task Management Section */}
                    <div className="mb-10">
                        <h2 className="mb-4 flex items-center text-xl font-semibold">
                            <List className="mr-2 h-5 w-5" />
                            Administración de Tareas
                        </h2>

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Clock className="mr-2 h-4 w-4 text-red-600" />
                                        Tareas Vencidas
                                    </CardTitle>
                                    <CardDescription>Tareas que han superado su fecha de vencimiento y requieren atención</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {taskStats.overdueTasks.length > 0 ? (
                                        <div className="space-y-4">
                                            {taskStats.overdueTasks.map((task) => (
                                                <div key={task.id} className="flex items-start justify-between border-b pb-3 last:border-0 last:pb-0">
                                                    <div>
                                                        <Link
                                                            href={route('tasks.show', task.id)}
                                                            className="font-medium text-red-600 hover:underline"
                                                        >
                                                            {task.title}
                                                        </Link>
                                                        <div className="text-muted-foreground mt-1 text-sm">
                                                            <div>Proyecto: {task.project.name}</div>
                                                            <div>Asignado a: {task.user.name}</div>
                                                        </div>
                                                    </div>
                                                    <div className="flex-shrink-0 text-xs font-medium text-red-600">
                                                        Vencida el {formatDate(task.due_date)}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-muted-foreground py-6 text-center">
                                            <CheckCircle className="mx-auto mb-2 h-8 w-8 text-green-500" />
                                            No hay tareas vencidas
                                        </div>
                                    )}

                                    <div className="mt-4">
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={route('tasks.index', { status: 'overdue' })}>Ver todas las tareas vencidas</Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <CalendarDays className="mr-2 h-4 w-4 text-amber-600" />
                                        Tareas Próximas a Vencer
                                    </CardTitle>
                                    <CardDescription>Tareas con fecha de vencimiento en los próximos 7 días</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {taskStats.dueSoonTasks.length > 0 ? (
                                        <div className="space-y-4">
                                            {taskStats.dueSoonTasks.map((task) => (
                                                <div key={task.id} className="flex items-start justify-between border-b pb-3 last:border-0 last:pb-0">
                                                    <div>
                                                        <Link href={route('tasks.show', task.id)} className="font-medium hover:underline">
                                                            {task.title}
                                                        </Link>
                                                        <div className="text-muted-foreground mt-1 text-sm">
                                                            <div>Proyecto: {task.project.name}</div>
                                                            <div>Asignado a: {task.user.name}</div>
                                                        </div>
                                                    </div>
                                                    <div className="flex-shrink-0 text-xs font-medium text-amber-600">
                                                        Vence el {formatDate(task.due_date)}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-muted-foreground py-6 text-center">No hay tareas próximas a vencer</div>
                                    )}

                                    <div className="mt-4">
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={route('tasks.index', { due_soon: true })}>Ver todas las tareas próximas</Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    {/* User Activity Section - Only visible to admins */}
                    {userActivity && (
                        <div className="mb-10">
                            <h2 className="mb-4 flex items-center text-xl font-semibold">
                                <Users className="mr-2 h-5 w-5" />
                                Actividad del Equipo
                            </h2>

                            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Rendimiento del Equipo</CardTitle>
                                        <CardDescription>Usuarios con más tareas completas y pendientes</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-6">
                                            <div>
                                                <h3 className="mb-2 text-sm font-medium">Top Usuarios por Tareas Completadas</h3>
                                                <div className="space-y-2">
                                                    {userActivity.topUsersByCompletedTasks.slice(0, 3).map((user) => (
                                                        <div key={user.user_id} className="flex items-center justify-between">
                                                            <span className="text-sm">{user.user_name}</span>
                                                            <span className="text-sm font-medium">{user.count} completadas</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="mb-2 text-sm font-medium">Top Usuarios por Tareas Abiertas</h3>
                                                <div className="space-y-2">
                                                    {userActivity.topUsersByOpenTasks.slice(0, 3).map((user) => (
                                                        <div key={user.user_id} className="flex items-center justify-between">
                                                            <span className="text-sm">{user.user_name}</span>
                                                            <span className="text-sm font-medium">{user.count} abiertas</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Actividad Reciente</CardTitle>
                                        <CardDescription>Últimas acciones realizadas en el espacio</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-4">
                                            {userActivity.recentActivity.slice(0, 5).map((activity, index) => (
                                                <div key={index} className="flex flex-col">
                                                    <div className="text-sm">
                                                        <span className="font-medium">{activity.user_name}</span>{' '}
                                                        {getActivityTypeText(activity.type, activity.entity_name)}
                                                    </div>
                                                    <div className="text-muted-foreground text-xs">{new Date(activity.date).toLocaleString()}</div>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    )}

                    {/* Quick Actions */}
                    <div>
                        <h2 className="mb-4 flex items-center text-xl font-semibold">
                            <LayoutGrid className="mr-2 h-5 w-5" />
                            Acciones Rápidas
                        </h2>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                            <Card className="hover:bg-muted/50 transition-colors">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-base">Nueva Tarea</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-muted-foreground mb-4 text-sm">Crea una nueva tarea y asígnala a un miembro del equipo</p>
                                    <Button asChild>
                                        <Link href={route('tasks.create')}>Crear Tarea</Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card className="hover:bg-muted/50 transition-colors">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-base">Nuevo Proyecto</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-muted-foreground mb-4 text-sm">Inicia un nuevo proyecto para organizar tus tareas</p>
                                    <Button asChild>
                                        <Link href={route('tenant.projects.create')}>Crear Proyecto</Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card className="hover:bg-muted/50 transition-colors">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-base">Ver Proyectos</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-muted-foreground mb-4 text-sm">Revisar el estado de todos los proyectos activos</p>
                                    <Button asChild variant="outline">
                                        <Link href={route('tenant.projects.index')}>Ver Proyectos</Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card className="hover:bg-muted/50 transition-colors">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-base">Gestionar Equipo</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-muted-foreground mb-4 text-sm">Administra los miembros del equipo y sus roles</p>
                                    <Button asChild variant="outline">
                                        <Link href="/users">Gestionar Equipo</Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
