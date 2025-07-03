import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    is_owner: boolean;
    joined_at: string;
}

interface UsersIndexProps {
    users: User[];
    canManageRoles: boolean;
    canInviteUsers: boolean;
    availableRoles: { value: string; label: string; description: string }[];
}

export default function Index({ users, canManageRoles, canInviteUsers, availableRoles }: PageProps<UsersIndexProps>) {
    const getRoleBadgeColor = (role: string) => {
        switch (role) {
            case 'owner':
                return 'bg-purple-600';
            case 'admin':
                return 'bg-red-600';
            case 'manager':
                return 'bg-amber-600';
            case 'member':
                return 'bg-blue-600';
            case 'guest':
                return 'bg-slate-600';
            default:
                return 'bg-gray-600';
        }
    };

    const updateUserRole = (userId: number, newRole: string) => {
        // Submit form to update user role
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = route('tenant.users.update', userId);
        form.style.display = 'none';

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PUT';
        form.appendChild(methodInput);

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        form.appendChild(tokenInput);

        const roleInput = document.createElement('input');
        roleInput.type = 'hidden';
        roleInput.name = 'role';
        roleInput.value = newRole;
        form.appendChild(roleInput);

        document.body.appendChild(form);
        form.submit();
    };

    const confirmRemoveUser = (userId: number, name: string) => {
        if (confirm(`¿Estás seguro de que quieres eliminar a ${name} del espacio?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = route('tenant.users.destroy', userId);
            form.style.display = 'none';

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            form.appendChild(tokenInput);

            document.body.appendChild(form);
            form.submit();
        }
    };

    return (
        <AppLayout>
            <Head title="Usuarios del Espacio" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Usuarios del Espacio</h1>
                        {canInviteUsers && (
                            <div className="flex gap-2">
                                <Button asChild variant="outline">
                                    <Link href={route('tenant.invitations.index')}>Ver Invitaciones</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={route('tenant.invitations.create')}>Invitar Usuario</Link>
                                </Button>
                            </div>
                        )}
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Miembros del Espacio</CardTitle>
                            <CardDescription>Gestiona los usuarios que tienen acceso a este espacio y sus permisos.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                                Nombre
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                                Email
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                                Rol
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                                Miembro Desde
                                            </th>
                                            {canManageRoles && (
                                                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300">
                                                    Acciones
                                                </th>
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                        {users.map((user) => (
                                            <tr key={user.id}>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                        {user.name}
                                                        {user.is_owner && (
                                                            <span className="ml-2 rounded-full bg-purple-100 px-2 py-1 text-xs text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                                Propietario
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-500 dark:text-gray-400">{user.email}</div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <Badge className={getRoleBadgeColor(user.role)}>{user.role_label}</Badge>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-500 dark:text-gray-400">{user.joined_at}</div>
                                                </td>
                                                {canManageRoles && (
                                                    <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                                        {!user.is_owner && (
                                                            <div className="flex gap-2">
                                                                {canManageRoles && (
                                                                    <select
                                                                        className="rounded border p-1 text-sm dark:border-gray-700 dark:bg-gray-800"
                                                                        value={user.role}
                                                                        onChange={(e) => updateUserRole(user.id, e.target.value)}
                                                                        disabled={user.is_owner}
                                                                    >
                                                                        {availableRoles.map((role) => (
                                                                            <option key={role.value} value={role.value}>
                                                                                {role.label}
                                                                            </option>
                                                                        ))}
                                                                    </select>
                                                                )}
                                                                <Button
                                                                    variant="destructive"
                                                                    size="sm"
                                                                    onClick={() => confirmRemoveUser(user.id, user.name)}
                                                                >
                                                                    Eliminar
                                                                </Button>
                                                            </div>
                                                        )}
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
