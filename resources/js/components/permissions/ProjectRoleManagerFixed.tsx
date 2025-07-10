import { Alert, AlertDescription } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { useFeature } from '@/composables/useFeature';
import axios from 'axios';
import { AlertCircle, Clock, Info, X } from 'lucide-react';
import React, { useCallback, useEffect, useMemo, useState } from 'react';

interface ProjectRoleManagerProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectId: number;
    projectName: string;
    userId: number;
    userName: string;
    userEmail: string;
    userAvatar?: string;
    onSave?: () => void;
}

interface Permission {
    value: string;
    label: string;
    description: string;
    category: string;
}

interface Role {
    value: string;
    label: string;
    description: string;
}

interface PermissionOptions {
    roles: Role[];
    permissions: Record<string, Permission[]>;
    permission_details: Permission[];
}

interface UserProjectPermissions {
    role: string;
    explicit_permissions: Record<string, boolean | null>;
    expires_at: string | null;
}

interface PermissionState {
    value: string;
    granted: boolean;
    explicit: boolean | null;
    inheritedFromRole: boolean;
}

const roleDefaults: Record<string, string[]> = {
    admin: [
        'can_manage_project',
        'can_manage_members',
        'can_edit_content',
        'can_delete_content',
        'can_view_reports',
        'can_view_budget',
        'can_export_data',
        'can_track_time',
        'can_view_all_time_entries',
        'can_manage_integrations',
    ],
    manager: [
        'can_manage_members',
        'can_edit_content',
        'can_delete_content',
        'can_view_reports',
        'can_view_budget',
        'can_export_data',
        'can_track_time',
        'can_view_all_time_entries',
    ],
    editor: ['can_edit_content', 'can_view_reports', 'can_track_time', 'can_view_all_time_entries'],
    member: ['can_edit_content', 'can_track_time'],
    viewer: [],
};

export const ProjectRoleManagerFixed: React.FC<ProjectRoleManagerProps> = ({
    open,
    onOpenChange,
    projectId,
    projectName,
    userId,
    userName,
    userEmail,
    userAvatar,
    onSave,
}) => {
    const hasFeature = useFeature('project_permissions');

    // Local state
    const [options, setOptions] = useState<PermissionOptions | null>(null);
    const [userPermissions, setUserPermissions] = useState<UserProjectPermissions | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [selectedRole, setSelectedRole] = useState<string>('member');
    const [inheritSpacePermissions, setInheritSpacePermissions] = useState(true);
    const [permissionOverrides, setPermissionOverrides] = useState<Record<string, boolean | null>>({});
    const [isSaving, setIsSaving] = useState(false);
    const [hasChanges, setHasChanges] = useState(false);

    // Helper functions
    const isPermissionGrantedByRole = useCallback((role: string, permission: string): boolean => {
        return roleDefaults[role]?.includes(permission) || false;
    }, []);

    const getRoleByValue = useCallback(
        (value: string): Role | undefined => {
            return options?.roles.find((role) => role.value === value);
        },
        [options],
    );

    const getPermissionByValue = useCallback(
        (value: string): Permission | undefined => {
            return options?.permission_details.find((perm) => perm.value === value);
        },
        [options],
    );

    // Fetch options
    const fetchOptions = useCallback(async () => {
        try {
            const response = await axios.get<{ data: PermissionOptions }>(`/api/projects/${projectId}/permissions/options`);
            setOptions(response.data.data);
            return true;
        } catch (err) {
            setError('Error al cargar opciones de permisos. Por favor, cierre y vuelva a intentar.');
            return false;
        }
    }, [projectId]);

    // Fetch user permissions
    const fetchUserPermissions = useCallback(async () => {
        if (!userId || !projectId) return;

        setLoading(true);
        setError(null);

        try {
            const response = await axios.get(`/api/projects/${projectId}/permissions/${userId}`);
            const data = response.data.data;
            setUserPermissions(data);
            setSelectedRole(data.role);
            setPermissionOverrides(data.explicit_permissions || {});
            setHasChanges(false);
        } catch (err: any) {
            if (err.response?.status === 404) {
                // User has no permissions yet, this is OK - they're being added
                setUserPermissions(null);
                setSelectedRole('member');
                setPermissionOverrides({});
                setHasChanges(false);
                // Don't show error, this is a valid state
            } else {
                setError('Error al cargar permisos del usuario');
            }
        } finally {
            setLoading(false);
        }
    }, [userId, projectId]);

    // Load data when modal opens
    useEffect(() => {
        if (open && hasFeature) {
            // Reset error state when opening
            setError(null);

            // Fetch options first if not loaded
            if (!options) {
                fetchOptions().then((success) => {
                    // Only fetch user permissions if options loaded successfully
                    if (success) {
                        fetchUserPermissions();
                    }
                });
            } else {
                // If options already loaded, just fetch user permissions
                fetchUserPermissions();
            }
        }
    }, [open, hasFeature]);

    // Calculate effective permissions
    const effectivePermissions = useMemo(() => {
        if (!options) return [];

        const permissions: PermissionState[] = [];

        options.permission_details.forEach((perm) => {
            const explicitValue = permissionOverrides[perm.value];
            const inheritedFromRole = isPermissionGrantedByRole(selectedRole, perm.value);

            let granted = inheritedFromRole;
            if (explicitValue !== null && explicitValue !== undefined) {
                granted = explicitValue;
            }

            permissions.push({
                value: perm.value,
                granted,
                explicit: explicitValue,
                inheritedFromRole,
            });
        });

        return permissions;
    }, [options, selectedRole, permissionOverrides, isPermissionGrantedByRole]);

    // Group permissions by category
    const permissionsByCategory = useMemo(() => {
        if (!options) return {};

        const grouped: Record<string, PermissionState[]> = {};

        Object.entries(options.permissions).forEach(([category, categoryPerms]) => {
            grouped[category] = effectivePermissions.filter((perm) => categoryPerms.some((cp) => cp.value === perm.value));
        });

        return grouped;
    }, [options, effectivePermissions]);

    const handleRoleChange = (newRole: string) => {
        setSelectedRole(newRole);
        // Check if this is a real change
        const originalRole = userPermissions?.role || 'member';
        setHasChanges(newRole !== originalRole || Object.keys(permissionOverrides).length > 0);
    };

    const handlePermissionToggle = (permission: string, value: boolean | null) => {
        setPermissionOverrides((prev) => {
            const newOverrides = { ...prev, [permission]: value };
            // Remove null values to clean up
            if (value === null) {
                delete newOverrides[permission];
            }

            // Check if we have any changes
            const hasOverrides = Object.keys(newOverrides).length > 0;
            const roleChanged = selectedRole !== (userPermissions?.role || 'member');
            setHasChanges(roleChanged || hasOverrides);

            return newOverrides;
        });
    };

    const handleSave = async () => {
        if (isSaving) return;

        setIsSaving(true);
        setError(null);

        try {
            // If user doesn't have permissions yet, add them to the project first
            if (!userPermissions) {
                await axios.post(`/api/projects/${projectId}/permissions/users`, {
                    user_id: userId,
                    role: selectedRole,
                });
            } else if (selectedRole !== userPermissions.role) {
                // Update role if changed
                await axios.put(`/api/projects/${projectId}/permissions/${userId}/role`, { role: selectedRole });
            }

            // Process permission overrides only if we have specific overrides
            if (Object.keys(permissionOverrides).length > 0) {
                const toGrant: string[] = [];
                const toRevoke: string[] = [];
                const toReset: string[] = [];

                Object.entries(permissionOverrides).forEach(([perm, value]) => {
                    const currentExplicit = userPermissions?.explicit_permissions?.[perm];

                    if (value === null && currentExplicit !== null && currentExplicit !== undefined) {
                        toReset.push(perm);
                    } else if (value === true && currentExplicit !== true) {
                        toGrant.push(perm);
                    } else if (value === false && currentExplicit !== false) {
                        toRevoke.push(perm);
                    }
                });

                // Apply permission changes
                if (toGrant.length > 0) {
                    await axios.put(`/api/projects/${projectId}/permissions/${userId}/permissions`, {
                        permissions: toGrant,
                        action: 'grant',
                    });
                }
                if (toRevoke.length > 0) {
                    await axios.put(`/api/projects/${projectId}/permissions/${userId}/permissions`, {
                        permissions: toRevoke,
                        action: 'revoke',
                    });
                }
                if (toReset.length > 0) {
                    await axios.put(`/api/projects/${projectId}/permissions/${userId}/permissions`, {
                        permissions: toReset,
                        action: 'reset',
                    });
                }
            }

            // Success
            setHasChanges(false);
            if (onSave) {
                onSave();
            }
            onOpenChange(false);
        } catch (err: any) {
            const errorMessage = err.response?.data?.message || 'Error al guardar los permisos';
            setError(errorMessage);
        } finally {
            setIsSaving(false);
        }
    };

    const handleCancel = () => {
        if (isSaving) return;
        onOpenChange(false);
    };

    if (!hasFeature || !open) {
        return null;
    }

    const selectedRoleInfo = getRoleByValue(selectedRole);
    const hasTemporaryAccess = userPermissions?.expires_at !== null;

    // Custom modal implementation without Dialog/Portal
    return (
        <>
            {/* Backdrop */}
            <div
                className="data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 z-50 bg-black/80"
                onClick={() => !isSaving && handleCancel()}
            />

            {/* Modal Content */}
            <div className="bg-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 fixed top-[50%] left-[50%] z-50 grid w-full max-w-[600px] translate-x-[-50%] translate-y-[-50%] gap-4 border p-6 shadow-lg duration-200 sm:rounded-lg">
                <div className="flex flex-col space-y-1.5 text-center sm:text-left">
                    <h2 className="text-lg leading-none font-semibold tracking-tight">Permisos de Proyecto</h2>
                    <p className="text-muted-foreground text-sm">
                        Gestionar rol y permisos de {userName} en {projectName}
                    </p>
                </div>

                {/* Close button */}
                <button
                    className="ring-offset-background focus:ring-ring data-[state=open]:bg-accent data-[state=open]:text-muted-foreground absolute top-4 right-4 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:pointer-events-none"
                    onClick={handleCancel}
                    disabled={isSaving}
                >
                    <X className="h-4 w-4" />
                    <span className="sr-only">Close</span>
                </button>

                {loading ? (
                    <div className="space-y-4 py-6">
                        <Skeleton className="h-20 w-full" />
                        <Skeleton className="h-10 w-full" />
                        <Skeleton className="h-64 w-full" />
                    </div>
                ) : error ? (
                    <Alert variant="destructive" className="my-6">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription className="flex items-center justify-between">
                            <span>{error}</span>
                            <Button size="sm" variant="outline" onClick={() => setError(null)} className="ml-4">
                                Cerrar
                            </Button>
                        </AlertDescription>
                    </Alert>
                ) : (
                    <ScrollArea className="max-h-[60vh] pr-4">
                        <div className="space-y-6 py-4">
                            {/* User Info */}
                            <div className="flex items-center gap-4">
                                <Avatar className="h-12 w-12">
                                    <AvatarImage src={userAvatar} alt={userName} />
                                    <AvatarFallback>
                                        {userName
                                            .split(' ')
                                            .map((n) => n[0])
                                            .join('')
                                            .toUpperCase()}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <p className="font-medium">{userName}</p>
                                    <p className="text-muted-foreground text-sm">{userEmail}</p>
                                </div>
                                {hasTemporaryAccess && (
                                    <Badge variant="outline" className="ml-auto gap-1">
                                        <Clock className="h-3 w-3" />
                                        Acceso Temporal
                                    </Badge>
                                )}
                            </div>

                            <Separator />

                            {/* Role Selector */}
                            <div className="space-y-2">
                                <Label htmlFor="role">Rol en el Proyecto</Label>
                                <Select value={selectedRole} onValueChange={handleRoleChange}>
                                    <SelectTrigger id="role">
                                        <SelectValue placeholder="Seleccione un rol" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options?.roles.map((role) => (
                                            <SelectItem key={role.value} value={role.value}>
                                                <div className="flex flex-col">
                                                    <span className="font-medium">{role.label}</span>
                                                    <span className="text-muted-foreground text-xs">{role.description}</span>
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {selectedRoleInfo && <p className="text-muted-foreground text-sm">{selectedRoleInfo.description}</p>}
                            </div>

                            {/* Inherit Space Permissions Toggle */}
                            <div className="flex items-center justify-between rounded-lg border p-4">
                                <div className="space-y-0.5">
                                    <Label htmlFor="inherit">Heredar permisos del espacio</Label>
                                    <p className="text-muted-foreground text-sm">Usa los permisos globales del usuario en este espacio</p>
                                </div>
                                <Switch id="inherit" checked={inheritSpacePermissions} onCheckedChange={setInheritSpacePermissions} />
                            </div>

                            {/* Permission Overrides */}
                            {!inheritSpacePermissions && (
                                <>
                                    <Alert>
                                        <Info className="h-4 w-4" />
                                        <AlertDescription>
                                            Los permisos marcados anulan los permisos por defecto del rol. Los permisos en gris heredan del rol
                                            seleccionado.
                                        </AlertDescription>
                                    </Alert>

                                    <div className="space-y-4">
                                        {Object.entries(permissionsByCategory).map(([category, permissions]) => (
                                            <div key={category} className="space-y-2">
                                                <h4 className="text-sm font-medium">{category}</h4>
                                                <div className="space-y-2">
                                                    {permissions.map((perm) => {
                                                        const permInfo = getPermissionByValue(perm.value);
                                                        if (!permInfo) return null;

                                                        const isExplicit = perm.explicit !== null && perm.explicit !== undefined;
                                                        const isChecked = perm.granted;

                                                        return (
                                                            <div key={perm.value} className="flex items-start gap-3 rounded-lg border p-3">
                                                                <Checkbox
                                                                    id={perm.value}
                                                                    checked={isChecked}
                                                                    onCheckedChange={(checked) => {
                                                                        if (checked === 'indeterminate') return;

                                                                        // Toggle between explicit grant, explicit revoke, and inherit
                                                                        if (!isExplicit) {
                                                                            handlePermissionToggle(perm.value, !perm.inheritedFromRole);
                                                                        } else if (perm.explicit === true) {
                                                                            handlePermissionToggle(perm.value, false);
                                                                        } else {
                                                                            handlePermissionToggle(perm.value, null);
                                                                        }
                                                                    }}
                                                                    className="mt-0.5"
                                                                />
                                                                <div className="flex-1 space-y-1">
                                                                    <Label
                                                                        htmlFor={perm.value}
                                                                        className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                                                    >
                                                                        {permInfo.label}
                                                                        {isExplicit && (
                                                                            <Badge variant="outline" className="ml-2 text-xs">
                                                                                {perm.explicit ? 'Otorgado' : 'Revocado'}
                                                                            </Badge>
                                                                        )}
                                                                    </Label>
                                                                    <p className="text-muted-foreground text-xs">{permInfo.description}</p>
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}

                            {/* Temporary Access Info */}
                            {hasTemporaryAccess && userPermissions && (
                                <Alert>
                                    <Clock className="h-4 w-4" />
                                    <AlertDescription>
                                        Este usuario tiene acceso temporal hasta{' '}
                                        {new Date(userPermissions.expires_at!).toLocaleDateString('es-ES', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })}
                                    </AlertDescription>
                                </Alert>
                            )}
                        </div>
                    </ScrollArea>
                )}

                <div className="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2">
                    <Button variant="outline" onClick={handleCancel} disabled={isSaving}>
                        Cancelar
                    </Button>
                    <Button onClick={handleSave} disabled={!hasChanges || isSaving}>
                        {isSaving ? 'Guardando...' : 'Guardar Cambios'}
                    </Button>
                </div>
            </div>
        </>
    );
};
