import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useFeature } from '@/composables/useFeature';
import { usePermissions } from '@/hooks/usePermissions';
import axios from 'axios';
import { AlertCircle, Check, Copy, FileText, Info, Layers, Save, Shield, Users, X } from 'lucide-react';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

interface AdvancedPermissionManagerProps {
    projectId?: number;
    projectName?: string;
    onSave?: () => void;
}

interface PermissionTemplate {
    id: string;
    name: string;
    description: string;
    icon: React.ReactNode;
    permissions: string[];
    role: string;
}

interface BatchUser {
    id: number;
    name: string;
    email: string;
    currentRole?: string;
    selected: boolean;
}

const permissionTemplates: PermissionTemplate[] = [
    {
        id: 'developer',
        name: 'Desarrollador',
        description: 'Acceso completo a código y tareas técnicas',
        icon: <FileText className="h-4 w-4" />,
        role: 'editor',
        permissions: [
            'can_edit_content',
            'can_view_reports',
            'can_track_time',
            'can_view_all_time_entries',
            'can_manage_integrations',
        ],
    },
    {
        id: 'designer',
        name: 'Diseñador',
        description: 'Acceso a contenido visual y reportes',
        icon: <Layers className="h-4 w-4" />,
        role: 'editor',
        permissions: ['can_edit_content', 'can_view_reports', 'can_track_time'],
    },
    {
        id: 'client',
        name: 'Cliente',
        description: 'Solo visualización y reportes básicos',
        icon: <Users className="h-4 w-4" />,
        role: 'viewer',
        permissions: ['can_view_reports'],
    },
    {
        id: 'accountant',
        name: 'Contador',
        description: 'Acceso a presupuestos y reportes financieros',
        icon: <Shield className="h-4 w-4" />,
        role: 'viewer',
        permissions: ['can_view_reports', 'can_view_budget', 'can_export_data'],
    },
    {
        id: 'project_manager',
        name: 'Jefe de Proyecto',
        description: 'Gestión completa del proyecto',
        icon: <Users className="h-4 w-4" />,
        role: 'manager',
        permissions: [
            'can_manage_members',
            'can_edit_content',
            'can_delete_content',
            'can_view_reports',
            'can_view_budget',
            'can_export_data',
            'can_track_time',
            'can_view_all_time_entries',
        ],
    },
];

export const AdvancedPermissionManager: React.FC<AdvancedPermissionManagerProps> = ({ projectId, projectName, onSave }) => {
    const hasFeature = useFeature('project_permissions');
    const { options, fetchPermissionOptions, getRoleByValue, getPermissionByValue } = usePermissions();

    const [activeTab, setActiveTab] = useState('templates');
    const [selectedTemplate, setSelectedTemplate] = useState<string | null>(null);
    const [selectedUsers, setSelectedUsers] = useState<number[]>([]);
    const [availableUsers, setAvailableUsers] = useState<BatchUser[]>([]);
    const [loading, setLoading] = useState(false);
    const [applyingTemplate, setApplyingTemplate] = useState(false);
    const [showConfirmDialog, setShowConfirmDialog] = useState(false);
    const [customPermissions, setCustomPermissions] = useState<Record<string, boolean>>({});
    const [customRole, setCustomRole] = useState<string>('member');

    useEffect(() => {
        if (hasFeature && projectId) {
            fetchPermissionOptions();
            fetchProjectUsers();
        }
    }, [hasFeature, projectId]);

    const fetchProjectUsers = async () => {
        if (!projectId) return;

        setLoading(true);
        try {
            const response = await axios.get(`/api/projects/${projectId}/members`);
            const members = response.data.data.members.map((member: any) => ({
                id: member.id,
                name: member.name,
                email: member.email,
                currentRole: member.role,
                selected: false,
            }));
            setAvailableUsers(members);
        } catch (error) {
            toast.error('Error al cargar usuarios del proyecto');
        } finally {
            setLoading(false);
        }
    };

    const toggleUserSelection = (userId: number) => {
        setSelectedUsers((prev) => {
            if (prev.includes(userId)) {
                return prev.filter((id) => id !== userId);
            }
            return [...prev, userId];
        });
    };

    const selectAllUsers = () => {
        setSelectedUsers(availableUsers.map((user) => user.id));
    };

    const deselectAllUsers = () => {
        setSelectedUsers([]);
    };

    const applyTemplate = async () => {
        if (!selectedTemplate || selectedUsers.length === 0 || !projectId) return;

        const template = permissionTemplates.find((t) => t.id === selectedTemplate);
        if (!template) return;

        setApplyingTemplate(true);
        try {
            // Apply template to each selected user
            const promises = selectedUsers.map(async (userId) => {
                // First update role
                await axios.put(`/api/projects/${projectId}/permissions/${userId}/role`, {
                    role: template.role,
                });

                // Then apply specific permissions
                await axios.put(`/api/projects/${projectId}/permissions/${userId}/permissions`, {
                    permissions: template.permissions,
                    action: 'grant',
                });
            });

            await Promise.all(promises);

            toast.success(`Plantilla "${template.name}" aplicada a ${selectedUsers.length} usuario(s)`);
            setShowConfirmDialog(false);
            setSelectedTemplate(null);
            setSelectedUsers([]);
            if (onSave) onSave();
        } catch (error) {
            toast.error('Error al aplicar la plantilla');
        } finally {
            setApplyingTemplate(false);
        }
    };

    const applyCustomPermissions = async () => {
        if (selectedUsers.length === 0 || !projectId) return;

        setApplyingTemplate(true);
        try {
            const grantPermissions = Object.entries(customPermissions)
                .filter(([_, granted]) => granted)
                .map(([perm]) => perm);

            const promises = selectedUsers.map(async (userId) => {
                // Update role
                await axios.put(`/api/projects/${projectId}/permissions/${userId}/role`, {
                    role: customRole,
                });

                // Apply permissions
                if (grantPermissions.length > 0) {
                    await axios.put(`/api/projects/${projectId}/permissions/${userId}/permissions`, {
                        permissions: grantPermissions,
                        action: 'grant',
                    });
                }
            });

            await Promise.all(promises);

            toast.success(`Permisos personalizados aplicados a ${selectedUsers.length} usuario(s)`);
            setSelectedUsers([]);
            setCustomPermissions({});
            if (onSave) onSave();
        } catch (error) {
            toast.error('Error al aplicar permisos personalizados');
        } finally {
            setApplyingTemplate(false);
        }
    };

    const selectedTemplateData = useMemo(() => {
        return permissionTemplates.find((t) => t.id === selectedTemplate);
    }, [selectedTemplate]);

    if (!hasFeature || !projectId) {
        return null;
    }

    return (
        <Card className="w-full">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Shield className="h-5 w-5" />
                    Gestión Avanzada de Permisos
                </CardTitle>
                <CardDescription>
                    Aplica plantillas de permisos o configura permisos personalizados para múltiples usuarios en {projectName}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList className="mb-4 grid w-full grid-cols-2">
                        <TabsTrigger value="templates">Plantillas de Permisos</TabsTrigger>
                        <TabsTrigger value="custom">Permisos Personalizados</TabsTrigger>
                    </TabsList>

                    <TabsContent value="templates" className="space-y-4">
                        {/* Template Selection */}
                        <div className="space-y-4">
                            <Label>Selecciona una plantilla</Label>
                            <RadioGroup value={selectedTemplate || ''} onValueChange={setSelectedTemplate}>
                                <div className="grid gap-3">
                                    {permissionTemplates.map((template) => (
                                        <div key={template.id} className="relative">
                                            <RadioGroupItem value={template.id} id={template.id} className="peer sr-only" />
                                            <Label
                                                htmlFor={template.id}
                                                className="flex cursor-pointer items-start gap-3 rounded-lg border p-4 hover:bg-accent peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary"
                                            >
                                                <div className="rounded-full bg-primary/10 p-2">{template.icon}</div>
                                                <div className="flex-1 space-y-1">
                                                    <p className="font-medium">{template.name}</p>
                                                    <p className="text-sm text-muted-foreground">{template.description}</p>
                                                    <div className="flex items-center gap-2 pt-2">
                                                        <Badge variant="outline">Rol: {getRoleByValue(template.role)?.label || template.role}</Badge>
                                                        <Badge variant="secondary">{template.permissions.length} permisos</Badge>
                                                    </div>
                                                </div>
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                            </RadioGroup>
                        </div>

                        {/* Template Preview */}
                        {selectedTemplateData && (
                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertDescription>
                                    <div className="space-y-2">
                                        <p className="font-medium">Permisos que se otorgarán:</p>
                                        <div className="flex flex-wrap gap-2">
                                            {selectedTemplateData.permissions.map((perm) => {
                                                const permData = getPermissionByValue(perm);
                                                return (
                                                    <Badge key={perm} variant="default" className="text-xs">
                                                        {permData?.label || perm}
                                                    </Badge>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </AlertDescription>
                            </Alert>
                        )}

                        <Separator />

                        {/* User Selection */}
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <Label>Selecciona usuarios para aplicar la plantilla</Label>
                                <div className="flex gap-2">
                                    <Button size="sm" variant="outline" onClick={selectAllUsers}>
                                        Seleccionar todos
                                    </Button>
                                    <Button size="sm" variant="outline" onClick={deselectAllUsers}>
                                        Deseleccionar todos
                                    </Button>
                                </div>
                            </div>

                            {loading ? (
                                <div className="space-y-2">
                                    <Skeleton className="h-12 w-full" />
                                    <Skeleton className="h-12 w-full" />
                                    <Skeleton className="h-12 w-full" />
                                </div>
                            ) : (
                                <ScrollArea className="h-64 rounded-lg border p-4">
                                    <div className="space-y-2">
                                        {availableUsers.map((user) => (
                                            <div
                                                key={user.id}
                                                className="flex items-center gap-3 rounded-lg p-2 hover:bg-muted/50"
                                                onClick={() => toggleUserSelection(user.id)}
                                            >
                                                <Checkbox checked={selectedUsers.includes(user.id)} onCheckedChange={() => toggleUserSelection(user.id)} />
                                                <div className="flex-1">
                                                    <p className="font-medium">{user.name}</p>
                                                    <p className="text-sm text-muted-foreground">{user.email}</p>
                                                </div>
                                                {user.currentRole && (
                                                    <Badge variant="outline">Rol actual: {getRoleByValue(user.currentRole)?.label || user.currentRole}</Badge>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </ScrollArea>
                            )}
                        </div>

                        {/* Apply Button */}
                        <div className="flex justify-end gap-2">
                            <Button
                                onClick={() => setShowConfirmDialog(true)}
                                disabled={!selectedTemplate || selectedUsers.length === 0 || applyingTemplate}
                            >
                                <Save className="mr-2 h-4 w-4" />
                                Aplicar Plantilla ({selectedUsers.length} usuarios)
                            </Button>
                        </div>
                    </TabsContent>

                    <TabsContent value="custom" className="space-y-4">
                        {/* Custom Role Selection */}
                        <div className="space-y-2">
                            <Label>Rol base</Label>
                            <Select value={customRole} onValueChange={setCustomRole}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options?.roles.map((role) => (
                                        <SelectItem key={role.value} value={role.value}>
                                            {role.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Custom Permissions */}
                        <div className="space-y-4">
                            <Label>Permisos adicionales</Label>
                            <ScrollArea className="h-64 rounded-lg border p-4">
                                <div className="space-y-4">
                                    {Object.entries(options?.permissions || {}).map(([category, permissions]) => (
                                        <div key={category} className="space-y-2">
                                            <h4 className="text-sm font-medium">{category}</h4>
                                            <div className="space-y-2">
                                                {permissions.map((perm) => (
                                                    <div key={perm.value} className="flex items-start gap-3">
                                                        <Checkbox
                                                            id={`custom-${perm.value}`}
                                                            checked={customPermissions[perm.value] || false}
                                                            onCheckedChange={(checked) =>
                                                                setCustomPermissions((prev) => ({
                                                                    ...prev,
                                                                    [perm.value]: checked as boolean,
                                                                }))
                                                            }
                                                        />
                                                        <div className="flex-1 space-y-1">
                                                            <Label htmlFor={`custom-${perm.value}`} className="text-sm font-normal">
                                                                {perm.label}
                                                            </Label>
                                                            <p className="text-xs text-muted-foreground">{perm.description}</p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </ScrollArea>
                        </div>

                        <Separator />

                        {/* User Selection for Custom */}
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <Label>Selecciona usuarios</Label>
                                <div className="flex gap-2">
                                    <Button size="sm" variant="outline" onClick={selectAllUsers}>
                                        Seleccionar todos
                                    </Button>
                                    <Button size="sm" variant="outline" onClick={deselectAllUsers}>
                                        Deseleccionar todos
                                    </Button>
                                </div>
                            </div>

                            <ScrollArea className="h-48 rounded-lg border p-4">
                                <div className="space-y-2">
                                    {availableUsers.map((user) => (
                                        <div
                                            key={user.id}
                                            className="flex items-center gap-3 rounded-lg p-2 hover:bg-muted/50"
                                            onClick={() => toggleUserSelection(user.id)}
                                        >
                                            <Checkbox checked={selectedUsers.includes(user.id)} onCheckedChange={() => toggleUserSelection(user.id)} />
                                            <div className="flex-1">
                                                <p className="font-medium">{user.name}</p>
                                                <p className="text-sm text-muted-foreground">{user.email}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </ScrollArea>
                        </div>

                        {/* Apply Custom Button */}
                        <div className="flex justify-end">
                            <Button onClick={applyCustomPermissions} disabled={selectedUsers.length === 0 || applyingTemplate}>
                                <Save className="mr-2 h-4 w-4" />
                                Aplicar Permisos Personalizados ({selectedUsers.length} usuarios)
                            </Button>
                        </div>
                    </TabsContent>
                </Tabs>
            </CardContent>

            {/* Confirmation Dialog */}
            <Dialog open={showConfirmDialog} onOpenChange={setShowConfirmDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Confirmar aplicación de plantilla</DialogTitle>
                        <DialogDescription>
                            ¿Estás seguro de que deseas aplicar la plantilla "{selectedTemplateData?.name}" a {selectedUsers.length} usuario(s)?
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <Alert>
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>
                                Esta acción sobrescribirá los permisos actuales de los usuarios seleccionados con los permisos de la plantilla.
                            </AlertDescription>
                        </Alert>
                        {selectedTemplateData && (
                            <div className="space-y-2">
                                <p className="text-sm font-medium">Se aplicarán los siguientes cambios:</p>
                                <ul className="space-y-1 text-sm">
                                    <li className="flex items-center gap-2">
                                        <Check className="h-3 w-3 text-green-600" />
                                        Rol: {getRoleByValue(selectedTemplateData.role)?.label}
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Check className="h-3 w-3 text-green-600" />
                                        {selectedTemplateData.permissions.length} permisos específicos
                                    </li>
                                </ul>
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowConfirmDialog(false)}>
                            Cancelar
                        </Button>
                        <Button onClick={applyTemplate} disabled={applyingTemplate}>
                            {applyingTemplate ? 'Aplicando...' : 'Confirmar y Aplicar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
};