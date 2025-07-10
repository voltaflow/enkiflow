import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFeature } from '@/composables/useFeature';
import { usePermissions } from '@/hooks/usePermissions';
import { AlertCircle, Check, X } from 'lucide-react';
import React, { useMemo, useState } from 'react';

interface GlobalRoleManagerProps {
    userId: number;
    userName: string;
    currentRole: string;
    spaceId: string;
    onRoleChange?: (newRole: string) => void;
    readOnly?: boolean;
}

export const GlobalRoleManager: React.FC<GlobalRoleManagerProps> = ({ userId, userName, currentRole, spaceId, onRoleChange, readOnly = false }) => {
    const hasFeature = useFeature('project_permissions');
    const { options, loading, error, isPermissionGrantedByRole } = usePermissions();
    const [selectedRole, setSelectedRole] = useState(currentRole);
    const [isSaving, setIsSaving] = useState(false);

    // Group permissions by category
    const permissionsByCategory = useMemo(() => {
        if (!options) return {};

        const grouped: Record<string, { permission: any; granted: boolean }[]> = {};

        Object.entries(options.permissions).forEach(([category, permissions]) => {
            grouped[category] = permissions.map((perm) => ({
                permission: perm,
                granted: isPermissionGrantedByRole(selectedRole, perm.value),
            }));
        });

        return grouped;
    }, [options, selectedRole, isPermissionGrantedByRole]);

    const handleRoleChange = (newRole: string) => {
        setSelectedRole(newRole);
    };

    const handleSave = async () => {
        if (!onRoleChange || selectedRole === currentRole) return;

        setIsSaving(true);
        try {
            await onRoleChange(selectedRole);
        } finally {
            setIsSaving(false);
        }
    };

    if (!hasFeature) {
        return null;
    }

    if (loading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-10 w-full" />
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

    const hasChanges = selectedRole !== currentRole;
    const selectedRoleInfo = options?.roles.find((r) => r.value === selectedRole);

    return (
        <div className="space-y-6">
            {/* Role Selector */}
            <div className="space-y-2">
                <label className="text-sm font-medium">Rol Global en el Espacio</label>
                <div className="flex items-center gap-4">
                    <Select value={selectedRole} onValueChange={handleRoleChange} disabled={readOnly}>
                        <SelectTrigger className="w-64">
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

                    {!readOnly && hasChanges && (
                        <div className="flex items-center gap-2">
                            <Button size="sm" onClick={handleSave} disabled={isSaving}>
                                {isSaving ? 'Guardando...' : 'Guardar Cambios'}
                            </Button>
                            <Button size="sm" variant="outline" onClick={() => setSelectedRole(currentRole)}>
                                Cancelar
                            </Button>
                        </div>
                    )}
                </div>

                {selectedRoleInfo && <p className="text-muted-foreground text-sm">{selectedRoleInfo.description}</p>}
            </div>

            {/* Preview Notice */}
            {hasChanges && (
                <Alert>
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>
                        Vista previa de permisos. Los cambios no se guardarán hasta que hagas clic en "Guardar Cambios".
                    </AlertDescription>
                </Alert>
            )}

            {/* Permissions Table */}
            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[300px]">Permiso</TableHead>
                            <TableHead className="w-[100px] text-center">Estado</TableHead>
                            <TableHead>Descripción</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {Object.entries(permissionsByCategory).map(([category, permissions]) => (
                            <React.Fragment key={category}>
                                <TableRow className="bg-muted/50">
                                    <TableCell colSpan={3} className="font-medium">
                                        {category}
                                    </TableCell>
                                </TableRow>
                                {permissions.map(({ permission, granted }) => (
                                    <TableRow key={permission.value}>
                                        <TableCell className="font-mono text-sm">{permission.label}</TableCell>
                                        <TableCell className="text-center">
                                            {granted ? (
                                                <Badge variant="default" className="gap-1">
                                                    <Check className="h-3 w-3" />
                                                    Permitido
                                                </Badge>
                                            ) : (
                                                <Badge variant="secondary" className="gap-1">
                                                    <X className="h-3 w-3" />
                                                    Denegado
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-sm">{permission.description}</TableCell>
                                    </TableRow>
                                ))}
                            </React.Fragment>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
};
