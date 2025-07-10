import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { ArrowRight, Briefcase, Calendar, CheckCircle, FolderOpen, Users } from 'lucide-react';
import { useEffect, useState } from 'react';

interface ProjectMember {
    id: number;
    name: string;
    description?: string;
    status: 'active' | 'completed' | 'archived';
    created_at: string;
    updated_at: string;
    due_date?: string;
    tasks_count?: number;
    completed_tasks_count?: number;
    user_role?: string; // Rol del usuario en el proyecto
    client?: {
        id: number;
        name: string;
    };
    user?: {
        id: number;
        name: string;
    };
}

interface AssignedProjectsProps {
    initialProjects?: ProjectMember[];
}

export default function AssignedProjects({ initialProjects = [] }: AssignedProjectsProps) {
    const [projects, setProjects] = useState<ProjectMember[]>(initialProjects);
    const [loading, setLoading] = useState(!initialProjects.length);
    const [filter, setFilter] = useState<'all' | 'active' | 'completed' | 'archived'>('all');
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!initialProjects.length) {
            fetchAssignedProjects();
        }
    }, []);

    const fetchAssignedProjects = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await axios.get('/api/user/assigned-projects');
            setProjects(response.data.data || []);
        } catch (err) {
            setError('Error al cargar tus proyectos asignados');
        } finally {
            setLoading(false);
        }
    };

    const filteredProjects = projects.filter(project => {
        if (filter === 'all') return true;
        return project.status === filter;
    });

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'active':
                return <FolderOpen className="h-4 w-4 text-green-600" />;
            case 'completed':
                return <CheckCircle className="h-4 w-4 text-blue-600" />;
            default:
                return <FolderOpen className="h-4 w-4 text-gray-400" />;
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'active':
                return <Badge className="bg-green-100 text-green-800">Activo</Badge>;
            case 'completed':
                return <Badge className="bg-blue-100 text-blue-800">Completado</Badge>;
            case 'archived':
                return <Badge className="bg-gray-100 text-gray-800">Archivado</Badge>;
            default:
                return <Badge>{status}</Badge>;
        }
    };

    const getRoleBadge = (role?: string) => {
        if (!role) return null;
        
        const roleColors: Record<string, string> = {
            owner: 'bg-purple-100 text-purple-800',
            admin: 'bg-red-100 text-red-800',
            manager: 'bg-blue-100 text-blue-800',
            member: 'bg-green-100 text-green-800',
            viewer: 'bg-gray-100 text-gray-800',
        };

        return (
            <Badge className={roleColors[role] || 'bg-gray-100 text-gray-800'}>
                {role.charAt(0).toUpperCase() + role.slice(1)}
            </Badge>
        );
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-MX', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    if (loading) {
        return (
            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>Tus Proyectos Asignados</CardTitle>
                    <CardDescription>Cargando proyectos...</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {[1, 2, 3].map(i => (
                            <div key={i} className="flex items-center space-x-4">
                                <Skeleton className="h-12 w-12 rounded-full" />
                                <div className="space-y-2 flex-1">
                                    <Skeleton className="h-4 w-[250px]" />
                                    <Skeleton className="h-4 w-[200px]" />
                                </div>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (error) {
        return (
            <Card className="mb-6 border-red-200">
                <CardHeader>
                    <CardTitle className="text-red-600">Error</CardTitle>
                    <CardDescription>{error}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button onClick={fetchAssignedProjects} variant="outline">
                        Reintentar
                    </Button>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="mb-6">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2">
                            <Briefcase className="h-5 w-5" />
                            Tus Proyectos Asignados
                        </CardTitle>
                        <CardDescription>
                            {projects.length > 0
                                ? `Estás asignado a ${projects.length} proyecto${projects.length !== 1 ? 's' : ''}`
                                : 'Aún no estás asignado a ningún proyecto'}
                        </CardDescription>
                    </div>
                    {projects.length > 0 && (
                        <Select value={filter} onValueChange={(value: any) => setFilter(value)}>
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Filtrar por estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="active">Activos</SelectItem>
                                <SelectItem value="completed">Completados</SelectItem>
                                <SelectItem value="archived">Archivados</SelectItem>
                            </SelectContent>
                        </Select>
                    )}
                </div>
            </CardHeader>
            <CardContent>
                {filteredProjects.length === 0 ? (
                    <div className="text-center py-8">
                        <Briefcase className="mx-auto h-12 w-12 text-gray-300 mb-4" />
                        <p className="text-gray-500">
                            {filter !== 'all'
                                ? `No tienes proyectos ${filter === 'active' ? 'activos' : filter === 'completed' ? 'completados' : 'archivados'}`
                                : 'Aún no estás asignado a ningún proyecto'}
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {filteredProjects.map(project => (
                            <div
                                key={project.id}
                                className="group relative rounded-lg border p-4 hover:shadow-md transition-shadow"
                            >
                                <div className="flex items-start justify-between mb-3">
                                    <div className="flex items-start gap-3">
                                        {getStatusIcon(project.status)}
                                        <div className="flex-1">
                                            <h4 className="font-semibold text-sm line-clamp-1">{project.name}</h4>
                                            {project.client && (
                                                <p className="text-xs text-muted-foreground mt-1">
                                                    {project.client.name}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    {getStatusBadge(project.status)}
                                </div>

                                {project.description && (
                                    <p className="text-sm text-muted-foreground line-clamp-2 mb-3">
                                        {project.description}
                                    </p>
                                )}

                                <div className="flex items-center gap-2 mb-3">
                                    {project.user_role && getRoleBadge(project.user_role)}
                                    {project.due_date && (
                                        <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                            <Calendar className="h-3 w-3" />
                                            {formatDate(project.due_date)}
                                        </div>
                                    )}
                                </div>

                                {(project.tasks_count !== undefined && project.tasks_count > 0) && (
                                    <div className="mb-3">
                                        <div className="flex items-center justify-between text-xs text-muted-foreground mb-1">
                                            <span>Progreso de tareas</span>
                                            <span>
                                                {project.completed_tasks_count || 0}/{project.tasks_count}
                                            </span>
                                        </div>
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div
                                                className="bg-primary h-2 rounded-full transition-all"
                                                style={{
                                                    width: `${((project.completed_tasks_count || 0) / project.tasks_count) * 100}%`
                                                }}
                                            />
                                        </div>
                                    </div>
                                )}

                                <Link
                                    href={route('tenant.projects.show', project.id)}
                                    className="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline"
                                >
                                    Ver detalles
                                    <ArrowRight className="h-3 w-3" />
                                </Link>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}