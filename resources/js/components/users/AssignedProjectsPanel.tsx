import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { Briefcase, Clock, DollarSign, Eye, FolderMinus, Infinity, MoreVertical, Plus, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Project {
    id: number;
    name: string;
    client?: string;
    status: 'active' | 'completed' | 'archived';
    role: 'member' | 'manager' | 'viewer';
    custom_rate?: number;
    all_current_projects: boolean;
    all_future_projects: boolean;
}

interface ProjectsData {
    projects: Project[];
    has_all_current_projects: boolean;
    has_all_future_projects: boolean;
    total_assigned: number;
}

interface AssignedProjectsPanelProps {
    userId: number;
    canManage: boolean;
    onAssignProjects?: () => void;
}

export default function AssignedProjectsPanel({ userId, canManage, onAssignProjects }: AssignedProjectsPanelProps) {
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<ProjectsData | null>(null);
    const [updatingRole, setUpdatingRole] = useState<number | null>(null);

    useEffect(() => {
        fetchAssignedProjects();
    }, [userId]);

    const fetchAssignedProjects = async () => {
        try {
            setLoading(true);
            const response = await axios.get(`/api/users/${userId}/projects`);
            setData(response.data.data);
        } catch (error) {
            console.error('Error fetching assigned projects:', error);
            toast.error('Failed to load assigned projects');
        } finally {
            setLoading(false);
        }
    };

    const updateProjectRole = async (projectId: number, newRole: string) => {
        try {
            setUpdatingRole(projectId);
            await axios.put(`/api/users/${userId}/projects/${projectId}`, {
                role: newRole,
            });
            toast.success('Rol del proyecto actualizado correctamente');
            fetchAssignedProjects();
        } catch (error) {
            console.error('Error updating project role:', error);
            toast.error('Error al actualizar el rol del proyecto');
        } finally {
            setUpdatingRole(null);
        }
    };

    const removeProject = async (projectId: number, projectName: string) => {
        if (!confirm(`¿Estás seguro de que quieres remover este usuario de ${projectName}?`)) {
            return;
        }

        try {
            await axios.delete(`/api/users/${userId}/projects/${projectId}`);
            toast.success('Proyecto removido correctamente');
            fetchAssignedProjects();
        } catch (error) {
            console.error('Error removing project:', error);
            toast.error('Error al remover proyecto');
        }
    };

    const getRoleIcon = (role: string) => {
        switch (role) {
            case 'manager':
                return <Users className="h-4 w-4" />;
            case 'viewer':
                return <Eye className="h-4 w-4" />;
            default:
                return <Briefcase className="h-4 w-4" />;
        }
    };

    const getRoleBadgeVariant = (role: string) => {
        switch (role) {
            case 'manager':
                return 'default';
            case 'viewer':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'active':
                return 'default';
            case 'completed':
                return 'secondary';
            case 'archived':
                return 'outline';
            default:
                return 'default';
        }
    };

    if (loading) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Proyectos Asignados</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-3">
                        <Skeleton className="h-20 w-full" />
                        <Skeleton className="h-20 w-full" />
                        <Skeleton className="h-20 w-full" />
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <div>
                    <CardTitle>Proyectos Asignados</CardTitle>
                    <CardDescription>
                        {data?.has_all_current_projects && data?.has_all_future_projects ? (
                            <span className="text-primary mt-1 flex items-center gap-2">
                                <Infinity className="h-4 w-4" />
                                Acceso a todos los proyectos actuales y futuros
                            </span>
                        ) : data?.has_all_current_projects ? (
                            <span className="text-primary mt-1 flex items-center gap-2">
                                <Infinity className="h-4 w-4" />
                                Acceso a todos los proyectos actuales
                            </span>
                        ) : data?.has_all_future_projects ? (
                            <span className="text-primary mt-1 flex items-center gap-2">
                                <Clock className="h-4 w-4" />
                                Acceso a todos los proyectos futuros
                            </span>
                        ) : (
                            `${data?.total_assigned || 0} proyectos asignados`
                        )}
                    </CardDescription>
                </div>
                {canManage && (
                    <Button onClick={onAssignProjects} size="sm">
                        <Plus className="mr-2 h-4 w-4" />
                        Asignar Proyectos
                    </Button>
                )}
            </CardHeader>
            <CardContent>
                {data?.projects && data.projects.length > 0 ? (
                    <div className="space-y-3">
                        {data.projects.map((project) => (
                            <div
                                key={project.id}
                                className={cn('flex items-center justify-between rounded-lg border p-4', 'hover:bg-muted/50 transition-colors')}
                            >
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <h4 className="font-medium">{project.name}</h4>
                                        <Badge variant={getRoleBadgeVariant(project.role)}>
                                            <span className="flex items-center gap-1">
                                                {getRoleIcon(project.role)}
                                                {project.role}
                                            </span>
                                        </Badge>
                                        <Badge variant={getStatusBadgeVariant(project.status)}>{project.status}</Badge>
                                    </div>
                                    {project.client && <p className="text-muted-foreground mt-1 text-sm">Cliente: {project.client}</p>}
                                    {project.custom_rate && (
                                        <div className="text-muted-foreground mt-1 flex items-center gap-1 text-sm">
                                            <DollarSign className="h-3 w-3" />${project.custom_rate}/hour
                                        </div>
                                    )}
                                </div>
                                {canManage && (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="sm" disabled={updatingRole === project.id}>
                                                <MoreVertical className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem disabled className="text-muted-foreground text-xs">
                                                Cambiar Rol
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                onClick={() => updateProjectRole(project.id, 'member')}
                                                disabled={project.role === 'member'}
                                            >
                                                <Briefcase className="mr-2 h-4 w-4" />
                                                Establecer como Miembro
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                onClick={() => updateProjectRole(project.id, 'manager')}
                                                disabled={project.role === 'manager'}
                                            >
                                                <Users className="mr-2 h-4 w-4" />
                                                Establecer como Manager
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                onClick={() => updateProjectRole(project.id, 'viewer')}
                                                disabled={project.role === 'viewer'}
                                            >
                                                <Eye className="mr-2 h-4 w-4" />
                                                Establecer como Visor
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem onClick={() => removeProject(project.id, project.name)} className="text-destructive">
                                                <FolderMinus className="mr-2 h-4 w-4" />
                                                Remover del Proyecto
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                )}
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="text-muted-foreground py-8 text-center">
                        {data?.has_all_current_projects || data?.has_all_future_projects ? (
                            <p>El usuario tiene acceso especial a todos los proyectos</p>
                        ) : (
                            <>
                                <Briefcase className="text-muted-foreground/50 mx-auto mb-3 h-12 w-12" />
                                <p>No hay proyectos asignados</p>
                                {canManage && (
                                    <Button onClick={onAssignProjects} variant="outline" size="sm" className="mt-3">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Asignar Primer Proyecto
                                    </Button>
                                )}
                            </>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
