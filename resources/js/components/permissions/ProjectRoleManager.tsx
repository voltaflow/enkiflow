import { Alert, AlertDescription } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { useFeature } from '@/composables/useFeature';
import { usePermissions } from '@/hooks/usePermissions';
import axios from 'axios';
import { AlertCircle, Clock, Info } from 'lucide-react';
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

interface PermissionState {
    value: string;
    granted: boolean;
    explicit: boolean | null;
    inheritedFromRole: boolean;
}

export const ProjectRoleManager: React.FC<ProjectRoleManagerProps> = ({
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
    const {
        options,
        loading: optionsLoading,
        error: optionsError,
        isPermissionGrantedByRole,
        getRoleByValue,
        getPermissionByValue,
    } = usePermissions();

    // Local state for this modal instance
    const [userPermissions, setUserPermissions] = useState<any>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [selectedRole, setSelectedRole] = useState<string>('member');
    const [inheritSpacePermissions, setInheritSpacePermissions] = useState(true);
    const [permissionOverrides, setPermissionOverrides] = useState<Record<string, boolean | null>>({});
    const [isSaving, setIsSaving] = useState(false);
    const [hasChanges, setHasChanges] = useState(false);

    // Fetch user permissions locally
    const fetchUserPermissions = useCallback(async () => {
        if (!open || !userId || !projectId) return;

        setLoading(true);
        setError(null);

        try {
            const response = await axios.get(`/api/projects/${projectId}/permissions/${userId}`);
            const data = response.data.data;
            setUserPermissions(data);
            setSelectedRole(data.role);
            setPermissionOverrides(data.explicit_permissions || {});
        } catch (err: any) {
            if (err.response?.status === 404) {
                // User has no permissions yet, set defaults
                setSelectedRole('member');
                setPermissionOverrides({});
            } else {
                setError('Failed to load user permissions');
                console.error('Error fetching user permissions:', err);
            }
        } finally {
            setLoading(false);
        }
    }, [open, userId, projectId]);

    // Load user permissions when modal opens
    useEffect(() => {
        if (open) {
            fetchUserPermissions();
        } else {
            // Reset state when modal closes
            setTimeout(() => {
                setUserPermissions(null);
                setSelectedRole('member');
                setPermissionOverrides({});
                setHasChanges(false);
                setIsSaving(false);
                setError(null);
            }, 300);
        }
    }, [open, fetchUserPermissions]);

    // Remove the duplicate useEffect that was updating state when permissions loaded

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
        setHasChanges(true);
    };

    const handlePermissionToggle = (permission: string, value: boolean | null) => {
        setPermissionOverrides((prev) => ({
            ...prev,
            [permission]: value,
        }));
        setHasChanges(true);
    };

    const handleSave = async () => {
        if (isSaving) return;

        setIsSaving(true);

        try {
            // Update role if changed
            if (userPermissions && selectedRole !== userPermissions.role) {
                await axios.put(`/api/projects/${projectId}/permissions/${userId}/role`, { role: selectedRole });
            }

            // Process permission overrides
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

            // Success - close modal
            if (onSave) {
                onSave();
            }
            onOpenChange(false);
        } catch (err) {
            console.error('Error saving permissions:', err);
            setError('Error al guardar los permisos');
        } finally {
            setIsSaving(false);
        }
    };

    const handleCancel = () => {
        if (isSaving) return;

        // Reset to original values if we have them
        if (userPermissions) {
            setSelectedRole(userPermissions.role);
            setPermissionOverrides(userPermissions.explicit_permissions || {});
        }
        setHasChanges(false);
        onOpenChange(false);
    };

    if (!hasFeature) {
        return null;
    }

    const selectedRoleInfo = getRoleByValue(selectedRole);
    const hasTemporaryAccess = userPermissions?.expires_at !== null;

    const isLoading = loading || optionsLoading;
    const displayError = error || optionsError;

    return (
        <Sheet
            open={open}
            onOpenChange={(newOpen) => {
                if (isSaving) return;

                if (!newOpen) {
                    handleCancel();
                } else {
                    onOpenChange(newOpen);
                }
            }}
        >
            <SheetContent
                className="w-[600px] max-w-full"
                onPointerDownOutside={(e) => {
                    if (isSaving) {
                        e.preventDefault();
                    }
                }}
                onEscapeKeyDown={(e) => {
                    if (isSaving) {
                        e.preventDefault();
                    }
                }}
            >
                <SheetHeader>
                    <SheetTitle>Permisos de Proyecto</SheetTitle>
                    <SheetDescription>
                        Gestionar rol y permisos de {userName} en {projectName}
                    </SheetDescription>
                </SheetHeader>

                {isLoading ? (
                    <div className="space-y-4 py-6">
                        <Skeleton className="h-20 w-full" />
                        <Skeleton className="h-10 w-full" />
                        <Skeleton className="h-64 w-full" />
                    </div>
                ) : displayError ? (
                    <Alert variant="destructive" className="my-6">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{displayError}</AlertDescription>
                    </Alert>
                ) : (
                    <ScrollArea className="h-[calc(100vh-200px)] pr-4">
                        <div className="space-y-6 py-6">
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
                                        <SelectValue />
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
                                                        const isIndeterminate = !isExplicit && perm.inheritedFromRole !== perm.granted;

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

                <SheetFooter className="gap-2">
                    <Button variant="outline" onClick={handleCancel} disabled={isSaving}>
                        Cancelar
                    </Button>
                    <Button onClick={handleSave} disabled={!hasChanges || isSaving}>
                        {isSaving ? 'Guardando...' : 'Guardar Cambios'}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
};
