import axios from 'axios';
import { useCallback, useEffect, useState } from 'react';

export interface ProjectRole {
    value: string;
    label: string;
    description: string;
    level?: number;
}

export interface ProjectPermission {
    value: string;
    label: string;
    description: string;
}

export interface PermissionGroup {
    [groupName: string]: ProjectPermission[];
}

export interface UserProjectPermissions {
    user_id: number;
    user_name: string;
    user_email: string;
    project_id: number;
    project_name: string;
    role: string;
    is_active: boolean;
    expires_at: string | null;
    explicit_permissions: Record<string, boolean | null>;
    effective_permissions: string[];
    created_at: string;
    updated_at: string;
}

export interface PermissionOptions {
    roles: ProjectRole[];
    permissions: PermissionGroup;
    permission_details: ProjectPermission[];
}

interface UsePermissionsReturn {
    options: PermissionOptions | null;
    userPermissions: UserProjectPermissions | null;
    loading: boolean;
    error: string | null;
    fetchPermissionOptions: () => Promise<void>;
    fetchUserProjectPermissions: (projectId: number, userId: number) => Promise<UserProjectPermissions>;
    updateUserRole: (projectId: number, userId: number, role: string) => Promise<void>;
    updateUserPermissions: (projectId: number, userId: number, permissions: string[], action: 'grant' | 'revoke' | 'reset') => Promise<void>;
    addUserToProject: (projectId: number, userId: number, role: string, expiresAt?: string, notes?: string) => Promise<void>;
    removeUserFromProject: (projectId: number, userId: number) => Promise<void>;
    isPermissionGrantedByRole: (role: string, permission: string) => boolean;
    getRoleByValue: (value: string) => ProjectRole | undefined;
    getPermissionByValue: (value: string) => ProjectPermission | undefined;
}

const permissionCache = new Map<string, UserProjectPermissions>();
let optionsCache: PermissionOptions | null = null;

export function usePermissions(): UsePermissionsReturn {
    const [options, setOptions] = useState<PermissionOptions | null>(optionsCache);
    const [userPermissions, setUserPermissions] = useState<UserProjectPermissions | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const fetchPermissionOptions = useCallback(async () => {
        if (optionsCache) {
            setOptions(optionsCache);
            return;
        }

        setLoading(true);
        setError(null);

        try {
            // Use a dummy project ID since options should be the same for all projects
            const response = await axios.get<{ data: PermissionOptions }>('/api/projects/1/permissions/options');
            optionsCache = response.data.data;
            setOptions(response.data.data);
        } catch (err) {
            setError('Failed to load permission options');
        } finally {
            setLoading(false);
        }
    }, []);

    const fetchUserProjectPermissions = useCallback(async (projectId: number, userId: number) => {
        const cacheKey = `${projectId}-${userId}`;
        const cached = permissionCache.get(cacheKey);

        if (cached) {
            setUserPermissions(cached);
            return cached;
        }

        setLoading(true);
        setError(null);

        try {
            const response = await axios.get<{ data: UserProjectPermissions }>(`/api/projects/${projectId}/permissions/${userId}`);
            const data = response.data.data;
            permissionCache.set(cacheKey, data);
            setUserPermissions(data);
            return data;
        } catch (err: any) {
            if (err.response?.status === 404) {
                // User has no permissions for this project yet
                setUserPermissions(null);
            } else {
                setError('Failed to load user permissions');
            }
            throw err;
        } finally {
            setLoading(false);
        }
    }, []);

    const updateUserRole = useCallback(
        async (projectId: number, userId: number, role: string) => {
            setLoading(true);
            setError(null);

            try {
                await axios.put(`/api/projects/${projectId}/permissions/${userId}/role`, { role });

                // Clear cache and refetch
                const cacheKey = `${projectId}-${userId}`;
                permissionCache.delete(cacheKey);
                await fetchUserProjectPermissions(projectId, userId);
            } catch (err) {
                setError('Failed to update user role');
                throw err;
            } finally {
                setLoading(false);
            }
        },
        [fetchUserProjectPermissions],
    );

    const updateUserPermissions = useCallback(
        async (projectId: number, userId: number, permissions: string[], action: 'grant' | 'revoke' | 'reset') => {
            setLoading(true);
            setError(null);

            try {
                await axios.put(`/api/projects/${projectId}/permissions/${userId}/permissions`, {
                    permissions,
                    action,
                });

                // Clear cache and refetch
                const cacheKey = `${projectId}-${userId}`;
                permissionCache.delete(cacheKey);
                await fetchUserProjectPermissions(projectId, userId);
            } catch (err) {
                setError('Failed to update permissions');
                throw err;
            } finally {
                setLoading(false);
            }
        },
        [fetchUserProjectPermissions],
    );

    const addUserToProject = useCallback(async (projectId: number, userId: number, role: string, expiresAt?: string, notes?: string) => {
        setLoading(true);
        setError(null);

        try {
            await axios.post(`/api/projects/${projectId}/permissions/users`, {
                user_id: userId,
                role,
                expires_at: expiresAt,
                notes,
            });
        } catch (err) {
            setError('Failed to add user to project');
            throw err;
        } finally {
            setLoading(false);
        }
    }, []);

    const removeUserFromProject = useCallback(async (projectId: number, userId: number) => {
        setLoading(true);
        setError(null);

        try {
            await axios.delete(`/api/projects/${projectId}/permissions/${userId}`);

            // Clear cache
            const cacheKey = `${projectId}-${userId}`;
            permissionCache.delete(cacheKey);
            setUserPermissions(null);
        } catch (err) {
            setError('Failed to remove user from project');
            throw err;
        } finally {
            setLoading(false);
        }
    }, []);

    const isPermissionGrantedByRole = useCallback((role: string, permission: string): boolean => {
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

        return roleDefaults[role]?.includes(permission) || false;
    }, []);

    const getRoleByValue = useCallback(
        (value: string): ProjectRole | undefined => {
            return options?.roles.find((role) => role.value === value);
        },
        [options],
    );

    const getPermissionByValue = useCallback(
        (value: string): ProjectPermission | undefined => {
            return options?.permission_details.find((perm) => perm.value === value);
        },
        [options],
    );

    // Load options on mount if not already loaded
    useEffect(() => {
        if (!options && !loading && !error) {
            fetchPermissionOptions();
        }
    }, [options, loading, error, fetchPermissionOptions]);

    return {
        options,
        userPermissions,
        loading,
        error,
        fetchPermissionOptions,
        fetchUserProjectPermissions,
        updateUserRole,
        updateUserPermissions,
        addUserToProject,
        removeUserFromProject,
        isPermissionGrantedByRole,
        getRoleByValue,
        getPermissionByValue,
    };
}
