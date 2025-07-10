import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import axios from 'axios';
import { AlertCircle, Briefcase, Clock, DollarSign, Eye, Infinity, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import ProjectSelector from './ProjectSelector';

interface Project {
    id: number;
    name: string;
    client?: string;
    status: 'active' | 'completed' | 'archived';
}

interface AssignmentData {
    type: 'specific' | 'all_current' | 'all_future';
    selectedProjects: number[];
    role: 'member' | 'manager' | 'viewer';
    customRate?: number;
    allCurrentProjects: boolean;
    allFutureProjects: boolean;
}

interface AssignProjectsModalProps {
    open: boolean;
    onClose: () => void;
    userId: number;
    userName: string;
    onSuccess: () => void;
}

export default function AssignProjectsModal({ open, onClose, userId, userName, onSuccess }: AssignProjectsModalProps) {
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [availableProjects, setAvailableProjects] = useState<Project[]>([]);
    const [assignmentData, setAssignmentData] = useState<AssignmentData>({
        type: 'specific',
        selectedProjects: [],
        role: 'member',
        customRate: undefined,
        allCurrentProjects: false,
        allFutureProjects: false,
    });

    useEffect(() => {
        if (open) {
            fetchAvailableProjects();
        }
    }, [open, userId]);

    const fetchAvailableProjects = async () => {
        try {
            setLoading(true);
            const response = await axios.get(`/api/users/${userId}/projects/available`);
            setAvailableProjects(response.data.data);
        } catch (error) {
            toast.error('Failed to load available projects');
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async () => {
        try {
            setSubmitting(true);

            const assignments = [];

            if (assignmentData.type === 'all_current') {
                assignments.push({
                    all_current_projects: true,
                    role: assignmentData.role,
                    custom_rate: assignmentData.customRate || null,
                });
            } else if (assignmentData.type === 'all_future') {
                assignments.push({
                    all_future_projects: true,
                    role: assignmentData.role,
                    custom_rate: assignmentData.customRate || null,
                });
            } else if (assignmentData.selectedProjects.length > 0) {
                assignments.push({
                    project_ids: assignmentData.selectedProjects,
                    role: assignmentData.role,
                    custom_rate: assignmentData.customRate || null,
                });
            } else {
                toast.error('Please select at least one project');
                return;
            }

            await axios.post(`/api/users/${userId}/projects`, { assignments });

            toast.success('Projects assigned successfully');
            onSuccess();
            onClose();
            resetForm();
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to assign projects');
        } finally {
            setSubmitting(false);
        }
    };

    const resetForm = () => {
        setAssignmentData({
            type: 'specific',
            selectedProjects: [],
            role: 'member',
            customRate: undefined,
            allCurrentProjects: false,
            allFutureProjects: false,
        });
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

    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Asignar Proyectos a {userName}</DialogTitle>
                    <DialogDescription>Elige qué proyectos puede acceder este usuario y su rol dentro de cada proyecto.</DialogDescription>
                </DialogHeader>

                <div className="space-y-6 py-4">
                    <Tabs value={assignmentData.type} onValueChange={(value) => setAssignmentData({ ...assignmentData, type: value as any })}>
                        <TabsList className="grid w-full grid-cols-3">
                            <TabsTrigger value="specific">Proyectos Específicos</TabsTrigger>
                            <TabsTrigger value="all_current">Todos los Actuales</TabsTrigger>
                            <TabsTrigger value="all_future">Todos los Futuros</TabsTrigger>
                        </TabsList>

                        <TabsContent value="specific" className="space-y-4">
                            {loading ? (
                                <div className="space-y-2">
                                    <Skeleton className="h-10 w-full" />
                                    <Skeleton className="h-10 w-full" />
                                    <Skeleton className="h-10 w-full" />
                                </div>
                            ) : availableProjects.length > 0 ? (
                                <ProjectSelector
                                    projects={availableProjects}
                                    selectedProjects={assignmentData.selectedProjects}
                                    onSelectionChange={(selected) => setAssignmentData({ ...assignmentData, selectedProjects: selected })}
                                />
                            ) : (
                                <Alert>
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        No hay proyectos disponibles para asignar. El usuario puede ya tener acceso a todos los proyectos.
                                    </AlertDescription>
                                </Alert>
                            )}
                        </TabsContent>

                        <TabsContent value="all_current" className="space-y-4">
                            <Alert>
                                <Infinity className="h-4 w-4" />
                                <AlertDescription>
                                    Otorgar acceso a todos los proyectos existentes en el espacio de trabajo. El usuario tendrá acceso a todos los
                                    proyectos actuales inmediatamente.
                                </AlertDescription>
                            </Alert>
                        </TabsContent>

                        <TabsContent value="all_future" className="space-y-4">
                            <Alert>
                                <Clock className="h-4 w-4" />
                                <AlertDescription>
                                    Otorgar acceso automáticamente a todos los proyectos creados en el futuro. Esto es útil para gerentes o
                                    administradores.
                                </AlertDescription>
                            </Alert>
                        </TabsContent>
                    </Tabs>

                    <div className="space-y-4">
                        <div>
                            <Label>Rol</Label>
                            <RadioGroup
                                value={assignmentData.role}
                                onValueChange={(value) => setAssignmentData({ ...assignmentData, role: value as any })}
                                className="mt-2"
                            >
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="member" id="member" />
                                    <Label htmlFor="member" className="flex cursor-pointer items-center gap-2">
                                        <Briefcase className="h-4 w-4" />
                                        Miembro
                                        <span className="text-muted-foreground ml-2 text-sm">
                                            Puede registrar tiempo y gestionar sus propias entradas
                                        </span>
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="manager" id="manager" />
                                    <Label htmlFor="manager" className="flex cursor-pointer items-center gap-2">
                                        <Users className="h-4 w-4" />
                                        Gerente
                                        <span className="text-muted-foreground ml-2 text-sm">Puede gestionar el proyecto y ver todos los datos</span>
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="viewer" id="viewer" />
                                    <Label htmlFor="viewer" className="flex cursor-pointer items-center gap-2">
                                        <Eye className="h-4 w-4" />
                                        Visor
                                        <span className="text-muted-foreground ml-2 text-sm">Solo puede ver los datos del proyecto</span>
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>

                        <div>
                            <Label htmlFor="customRate" className="flex items-center gap-2">
                                <DollarSign className="h-4 w-4" />
                                Tarifa por Hora Personalizada (Opcional)
                            </Label>
                            <Input
                                id="customRate"
                                type="number"
                                placeholder="Dejar vacío para usar la tarifa predeterminada"
                                value={assignmentData.customRate || ''}
                                onChange={(e) =>
                                    setAssignmentData({
                                        ...assignmentData,
                                        customRate: e.target.value ? parseFloat(e.target.value) : undefined,
                                    })
                                }
                                className="mt-2"
                                min="0"
                                step="0.01"
                            />
                            <p className="text-muted-foreground mt-1 text-sm">
                                Anular la tarifa de facturación predeterminada para este usuario en los proyectos asignados
                            </p>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={submitting}>
                        Cancelar
                    </Button>
                    <Button onClick={handleSubmit} disabled={submitting || loading}>
                        {submitting ? 'Asignando...' : 'Asignar Proyectos'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
