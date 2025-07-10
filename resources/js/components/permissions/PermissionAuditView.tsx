import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Skeleton } from '@/components/ui/skeleton';
import { useFeature } from '@/composables/useFeature';
import { usePermissions } from '@/hooks/usePermissions';
import { AlertCircle, Building2, Check, ChevronDown, ChevronRight, Key, Shield, User, X } from 'lucide-react';
import React, { useEffect, useMemo, useState } from 'react';

interface PermissionAuditViewProps {
    userId: number;
    projectId?: number;
    userName?: string;
    showHeader?: boolean;
    userSpaceRole?: string;
    userSpacePermissions?: string[];
}

interface PermissionSource {
    type: 'space_role' | 'project_role' | 'explicit_grant' | 'explicit_revoke';
    source: string;
    level: number;
    description: string;
}

interface AuditItem {
    permission: string;
    label: string;
    description: string;
    granted: boolean;
    sources: PermissionSource[];
}

export const PermissionAuditView: React.FC<PermissionAuditViewProps> = ({ 
    userId, 
    projectId, 
    userName, 
    showHeader = true,
    userSpaceRole = 'member',
    userSpacePermissions = []
}) => {
    const hasFeature = useFeature('project_permissions');
    const { options, userPermissions, loading, error, fetchUserProjectPermissions, isPermissionGrantedByRole, getPermissionByValue, getRoleByValue } =
        usePermissions();

    const [expandedCategories, setExpandedCategories] = useState<Set<string>>(new Set());
    const [spaceRole] = useState<string>(userSpaceRole);
    
    // Define space permission categories when not in project context
    const getSpacePermissionCategories = () => {
        return {
            'Gestión del Espacio': [
                { value: 'manage_space', label: 'Administrar espacio', description: 'Puede cambiar configuración del espacio' },
                { value: 'view_space', label: 'Ver espacio', description: 'Puede ver información del espacio' },
                { value: 'delete_space', label: 'Eliminar espacio', description: 'Puede eliminar el espacio completo' },
            ],
            'Gestión de Usuarios': [
                { value: 'invite_users', label: 'Invitar usuarios', description: 'Puede invitar nuevos usuarios' },
                { value: 'remove_users', label: 'Eliminar usuarios', description: 'Puede eliminar usuarios del espacio' },
                { value: 'manage_user_roles', label: 'Gestionar roles', description: 'Puede cambiar roles de usuarios' },
            ],
            'Facturación': [
                { value: 'manage_billing', label: 'Gestionar facturación', description: 'Puede gestionar suscripción y pagos' },
                { value: 'view_invoices', label: 'Ver facturas', description: 'Puede ver facturas del espacio' },
            ],
            'Proyectos': [
                { value: 'create_projects', label: 'Crear proyectos', description: 'Puede crear nuevos proyectos' },
                { value: 'edit_projects', label: 'Editar proyectos', description: 'Puede editar proyectos existentes' },
                { value: 'delete_projects', label: 'Eliminar proyectos', description: 'Puede eliminar proyectos' },
                { value: 'view_all_projects', label: 'Ver todos los proyectos', description: 'Puede ver todos los proyectos del espacio' },
            ],
            'Tareas': [
                { value: 'create_tasks', label: 'Crear tareas', description: 'Puede crear nuevas tareas' },
                { value: 'edit_any_task', label: 'Editar cualquier tarea', description: 'Puede editar cualquier tarea' },
                { value: 'edit_own_tasks', label: 'Editar tareas propias', description: 'Puede editar solo sus tareas' },
                { value: 'delete_any_task', label: 'Eliminar cualquier tarea', description: 'Puede eliminar cualquier tarea' },
                { value: 'delete_own_tasks', label: 'Eliminar tareas propias', description: 'Puede eliminar solo sus tareas' },
                { value: 'view_all_tasks', label: 'Ver todas las tareas', description: 'Puede ver todas las tareas' },
            ],
            'Comentarios': [
                { value: 'create_comments', label: 'Crear comentarios', description: 'Puede crear comentarios' },
                { value: 'edit_any_comment', label: 'Editar cualquier comentario', description: 'Puede editar cualquier comentario' },
                { value: 'edit_own_comments', label: 'Editar comentarios propios', description: 'Puede editar sus comentarios' },
                { value: 'delete_any_comment', label: 'Eliminar cualquier comentario', description: 'Puede eliminar cualquier comentario' },
                { value: 'delete_own_comments', label: 'Eliminar comentarios propios', description: 'Puede eliminar sus comentarios' },
            ],
            'Otros': [
                { value: 'manage_tags', label: 'Gestionar etiquetas', description: 'Puede crear y editar etiquetas' },
                { value: 'view_statistics', label: 'Ver estadísticas', description: 'Puede ver estadísticas y reportes' },
            ],
        };
    };
    
    // Get space role label
    const getSpaceRoleLabel = (role: string) => {
        const labels: Record<string, string> = {
            owner: 'Propietario',
            admin: 'Administrador',
            manager: 'Gerente',
            member: 'Miembro',
            guest: 'Invitado',
        };
        return labels[role] || role;
    };

    useEffect(() => {
        if (projectId && userId && hasFeature) {
            fetchUserProjectPermissions(projectId, userId).catch(() => {
                // Handle error
            });
        }
    }, [projectId, userId, hasFeature, fetchUserProjectPermissions]);

    // Build audit trail for each permission
    const auditData = useMemo(() => {
        if (!hasFeature) return {};
        
        // For space-level view, we still want to show space permissions
        if (!projectId && userSpacePermissions.length === 0) return {};
        
        // For project-level view, we need options
        if (projectId && !options) return {};

        const grouped: Record<string, AuditItem[]> = {};

        // Get permissions from space level if no project
        const permissionCategories = projectId && options ? options.permissions : getSpacePermissionCategories();
        
        Object.entries(permissionCategories).forEach(([category, permissions]) => {
            grouped[category] = permissions.map((perm) => {
                const sources: PermissionSource[] = [];
                let finalGranted = false;

                // Check space permissions (lowest priority)
                const hasSpacePermission = userSpacePermissions.includes(perm.value);
                if (hasSpacePermission) {
                    sources.push({
                        type: 'space_role',
                        source: `Rol de Espacio: ${getSpaceRoleLabel(spaceRole)}`,
                        level: 1,
                        description: 'Heredado del rol global en el espacio',
                    });
                    finalGranted = true;
                }

                // Check project role (medium priority)
                if (userPermissions) {
                    const grantedByProjectRole = isPermissionGrantedByRole(userPermissions.role, perm.value);
                    if (grantedByProjectRole) {
                        sources.push({
                            type: 'project_role',
                            source: `Rol de Proyecto: ${getRoleByValue(userPermissions.role)?.label || userPermissions.role}`,
                            level: 2,
                            description: 'Otorgado por el rol en este proyecto',
                        });
                        finalGranted = true;
                    } else if (hasSpacePermission) {
                        // Project role doesn't grant it, but space role does
                        finalGranted = hasSpacePermission;
                    }

                    // Check explicit permissions (highest priority)
                    const explicit = userPermissions.explicit_permissions?.[perm.value];
                    if (explicit === true) {
                        sources.push({
                            type: 'explicit_grant',
                            source: 'Permiso Explícito',
                            level: 3,
                            description: 'Otorgado específicamente para este proyecto',
                        });
                        finalGranted = true;
                    } else if (explicit === false) {
                        sources.push({
                            type: 'explicit_revoke',
                            source: 'Revocación Explícita',
                            level: 3,
                            description: 'Revocado específicamente para este proyecto',
                        });
                        finalGranted = false;
                    }
                }

                return {
                    permission: perm.value,
                    label: perm.label,
                    description: perm.description,
                    granted: finalGranted,
                    sources: sources.sort((a, b) => b.level - a.level),
                };
            });
        });

        return grouped;
    }, [options, userPermissions, spaceRole, userSpacePermissions, isPermissionGrantedByRole, getRoleByValue, projectId, hasFeature]);

    const toggleCategory = (category: string) => {
        setExpandedCategories((prev) => {
            const next = new Set(prev);
            if (next.has(category)) {
                next.delete(category);
            } else {
                next.add(category);
            }
            return next;
        });
    };

    const expandAll = () => {
        if (options) {
            setExpandedCategories(new Set(Object.keys(options.permissions)));
        }
    };

    const collapseAll = () => {
        setExpandedCategories(new Set());
    };

    if (!hasFeature) {
        return null;
    }

    if (loading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-32 w-full" />
                <Skeleton className="h-64 w-full" />
            </div>
        );
    }

    if (error) {
        return (
            <Alert variant="destructive">
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>{error}</AlertDescription>
            </Alert>
        );
    }

    const getSourceIcon = (type: PermissionSource['type']) => {
        switch (type) {
            case 'space_role':
                return <Building2 className="h-4 w-4" />;
            case 'project_role':
                return <Shield className="h-4 w-4" />;
            case 'explicit_grant':
            case 'explicit_revoke':
                return <Key className="h-4 w-4" />;
        }
    };

    const getSourceColor = (type: PermissionSource['type']) => {
        switch (type) {
            case 'space_role':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            case 'project_role':
                return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
            case 'explicit_grant':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'explicit_revoke':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        }
    };

    return (
        <div className="space-y-4">
            {showHeader && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <User className="h-5 w-5" />
                            Auditoría de Permisos
                        </CardTitle>
                        <CardDescription>
                            {userName ? `Permisos efectivos de ${userName}` : 'Tus permisos efectivos'}
                            {projectId ? ' en este proyecto' : ' en el espacio'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Badge variant="outline" className="gap-1">
                                    <Building2 className="h-3 w-3" />
                                    Rol de Espacio: {getSpaceRoleLabel(spaceRole)}
                                </Badge>
                                {userPermissions && projectId && (
                                    <Badge variant="outline" className="gap-1">
                                        <Shield className="h-3 w-3" />
                                        Rol de Proyecto: {getRoleByValue(userPermissions.role)?.label || userPermissions.role}
                                    </Badge>
                                )}
                            </div>
                            <div className="flex gap-2">
                                <Button size="sm" variant="outline" onClick={expandAll}>
                                    Expandir Todo
                                </Button>
                                <Button size="sm" variant="outline" onClick={collapseAll}>
                                    Colapsar Todo
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            <div className="space-y-4">
                {Object.entries(auditData).map(([category, items]) => {
                    const isExpanded = expandedCategories.has(category);
                    const grantedCount = items.filter((item) => item.granted).length;

                    return (
                        <Card key={category}>
                            <Collapsible open={isExpanded} onOpenChange={() => toggleCategory(category)}>
                                <CollapsibleTrigger asChild>
                                    <CardHeader className="cursor-pointer">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                {isExpanded ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                                <CardTitle className="text-base">{category}</CardTitle>
                                            </div>
                                            <Badge variant="secondary">
                                                {grantedCount} de {items.length} permisos
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <CardContent className="space-y-3 pt-0">
                                        {items.map((item) => (
                                            <div key={item.permission} className="space-y-2 rounded-lg border p-3">
                                                <div className="flex items-start justify-between">
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-2">
                                                            <span className="text-sm font-medium">{item.label}</span>
                                                            {item.granted ? (
                                                                <Badge variant="default" className="h-5 gap-1">
                                                                    <Check className="h-3 w-3" />
                                                                    Permitido
                                                                </Badge>
                                                            ) : (
                                                                <Badge variant="secondary" className="h-5 gap-1">
                                                                    <X className="h-3 w-3" />
                                                                    Denegado
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <p className="text-muted-foreground text-xs">{item.description}</p>
                                                    </div>
                                                </div>

                                                {item.sources.length > 0 && (
                                                    <div className="space-y-1 border-t pt-2">
                                                        <p className="text-muted-foreground mb-1 text-xs font-medium">Origen del permiso:</p>
                                                        {item.sources.map((source, idx) => (
                                                            <div key={idx} className="flex items-center gap-2 text-xs">
                                                                <div className={`rounded-full p-1 ${getSourceColor(source.type)}`}>
                                                                    {getSourceIcon(source.type)}
                                                                </div>
                                                                <span className="font-medium">{source.source}</span>
                                                                <span className="text-muted-foreground">• {source.description}</span>
                                                                {idx === 0 && item.sources.length > 1 && (
                                                                    <Badge variant="outline" className="ml-auto h-4 text-xs">
                                                                        Prioridad más alta
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </CardContent>
                                </CollapsibleContent>
                            </Collapsible>
                        </Card>
                    );
                })}
            </div>

            <Alert>
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>
                    {projectId ? (
                        <>Los permisos se evalúan en orden de prioridad: Permisos explícitos {'>'}
                        Rol de proyecto {'>'} Rol de espacio. Los permisos explícitos siempre tienen precedencia sobre los roles.</>
                    ) : (
                        <>Los permisos mostrados son los otorgados por tu rol de espacio. Estos permisos aplican globalmente 
                        en todo el espacio de trabajo.</>
                    )}
                </AlertDescription>
            </Alert>
        </div>
    );
};
