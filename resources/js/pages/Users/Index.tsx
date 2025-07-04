import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Clock, MoreHorizontal, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    status: 'active' | 'invited' | 'archived';
    capacity_hours: number;
    cost_rate?: number;
    billable_rate?: number;
    joined_at: string;
}

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        search?: string;
        role?: string;
        status?: string;
    };
    availableRoles: Array<{
        value: string;
        label: string;
    }>;
    canInviteUsers: boolean;
    canEditUsers: boolean;
    canDeleteUsers: boolean;
}

export default function Index({ users, filters, availableRoles, canInviteUsers, canEditUsers, canDeleteUsers }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [roleFilter, setRoleFilter] = useState(filters.role || 'all');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [userToDelete, setUserToDelete] = useState<User | null>(null);

    // Búsqueda automática con debounce
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== filters.search) {
                router.get(
                    route('users.index'),
                    {
                        search,
                        role: roleFilter === 'all' ? undefined : roleFilter || undefined,
                        status: statusFilter === 'all' ? undefined : statusFilter || undefined,
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                    },
                );
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [search]);

    const handleFilterChange = (type: 'role' | 'status', value: string) => {
        if (type === 'role') {
            setRoleFilter(value);
        } else {
            setStatusFilter(value);
        }

        router.get(
            route('users.index'),
            {
                search: search || undefined,
                role: type === 'role' ? (value === 'all' ? undefined : value) : roleFilter === 'all' ? undefined : roleFilter,
                status: type === 'status' ? (value === 'all' ? undefined : value) : statusFilter === 'all' ? undefined : statusFilter,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getRoleBadgeColor = (role: string) => {
        const colors = {
            owner: 'bg-purple-100 text-purple-800',
            admin: 'bg-red-100 text-red-800',
            manager: 'bg-blue-100 text-blue-800',
            member: 'bg-green-100 text-green-800',
            guest: 'bg-gray-100 text-gray-800',
        };
        return colors[role as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const getStatusBadgeColor = (status: string) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            invited: 'bg-yellow-100 text-yellow-800',
            archived: 'bg-gray-100 text-gray-800',
        };
        return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const getRoleLabel = (role: string) => {
        const labels = {
            owner: 'Propietario',
            admin: 'Administrador',
            manager: 'Gerente',
            member: 'Miembro',
            guest: 'Invitado',
        };
        return labels[role as keyof typeof labels] || role;
    };

    const getStatusLabel = (status: string) => {
        const labels = {
            active: 'Activo',
            invited: 'Invitado',
            archived: 'Archivado',
        };
        return labels[status as keyof typeof labels] || status;
    };

    const handleEdit = (user: User) => {
        router.visit(route('users.show', user.id));
    };

    const handleDeleteClick = (user: User) => {
        setUserToDelete(user);
        setDeleteModalOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (userToDelete) {
            router.delete(route('users.destroy', userToDelete.id), {
                onSuccess: () => {
                    setDeleteModalOpen(false);
                    setUserToDelete(null);
                },
            });
        }
    };

    return (
        <AppLayout>
            <Head title="Usuarios del Espacio" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Usuarios del Espacio</h1>
                            <p className="text-muted-foreground mt-1">Gestiona los usuarios y sus permisos en este espacio</p>
                        </div>
                        {canInviteUsers && (
                            <Button asChild>
                                <Link href={route('tenant.users.invite')}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Invitar Usuario
                                </Link>
                            </Button>
                        )}
                    </div>

                    {/* Filters */}
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex flex-col gap-4 md:flex-row">
                                <div className="flex-1">
                                    <div className="relative">
                                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                        <Input
                                            type="text"
                                            placeholder="Buscar usuarios..."
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                </div>
                                <Select value={roleFilter} onValueChange={(value) => handleFilterChange('role', value)}>
                                    <SelectTrigger className="w-full md:w-48">
                                        <SelectValue placeholder="Todos los roles" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los roles</SelectItem>
                                        {availableRoles.map((role) => (
                                            <SelectItem key={role.value} value={role.value}>
                                                {role.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={statusFilter} onValueChange={(value) => handleFilterChange('status', value)}>
                                    <SelectTrigger className="w-full md:w-48">
                                        <SelectValue placeholder="Todos los estados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los estados</SelectItem>
                                        <SelectItem value="active">Activos</SelectItem>
                                        <SelectItem value="invited">Invitados</SelectItem>
                                        <SelectItem value="archived">Archivados</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Users Table */}
                    <Card>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="bg-muted/50 border-b">
                                            <th className="p-4 text-left font-medium">Usuario</th>
                                            <th className="p-4 text-left font-medium">Rol</th>
                                            <th className="p-4 text-left font-medium">Estado</th>
                                            <th className="p-4 text-left font-medium">Disponibilidad</th>
                                            {(canEditUsers || canDeleteUsers) && <th className="p-4 text-right font-medium">Acciones</th>}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {users.data.map((user) => (
                                            <tr key={user.id} className="hover:bg-muted/50 border-b transition-colors">
                                                <td className="p-4">
                                                    <div className="flex items-center gap-3">
                                                        <Avatar>
                                                            <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
                                                        </Avatar>
                                                        <div>
                                                            <div className="font-medium">{user.name}</div>
                                                            <div className="text-muted-foreground text-sm">{user.email}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    <Badge className={getRoleBadgeColor(user.role)} variant="secondary">
                                                        {getRoleLabel(user.role)}
                                                    </Badge>
                                                </td>
                                                <td className="p-4">
                                                    <Badge className={getStatusBadgeColor(user.status)} variant="secondary">
                                                        <div className="flex items-center gap-1">
                                                            <div
                                                                className={`h-2 w-2 rounded-full ${
                                                                    user.status === 'active'
                                                                        ? 'bg-green-600'
                                                                        : user.status === 'invited'
                                                                          ? 'bg-yellow-600'
                                                                          : 'bg-gray-600'
                                                                }`}
                                                            />
                                                            {getStatusLabel(user.status)}
                                                        </div>
                                                    </Badge>
                                                </td>
                                                <td className="p-4">
                                                    <div className="text-muted-foreground flex items-center gap-1 text-sm">
                                                        <Clock className="h-4 w-4" />
                                                        {user.capacity_hours}h/semana
                                                    </div>
                                                </td>
                                                {(canEditUsers || canDeleteUsers) && (
                                                    <td className="p-4 text-right">
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <Button variant="ghost" size="sm">
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
                                                                {canEditUsers && (
                                                                    <DropdownMenuItem onClick={() => handleEdit(user)}>
                                                                        <Pencil className="mr-2 h-4 w-4" />
                                                                        Editar
                                                                    </DropdownMenuItem>
                                                                )}
                                                                {canEditUsers && canDeleteUsers && <DropdownMenuSeparator />}
                                                                {canDeleteUsers && (
                                                                    <DropdownMenuItem
                                                                        onClick={() => handleDeleteClick(user)}
                                                                        className="text-red-600 focus:text-red-600"
                                                                    >
                                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                                        Eliminar
                                                                    </DropdownMenuItem>
                                                                )}
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {users.last_page > 1 && (
                                <div className="border-t p-4">
                                    <div className="flex items-center justify-between">
                                        <p className="text-muted-foreground text-sm">
                                            Mostrando {users.from} a {users.to} de {users.total} resultados
                                        </p>
                                        <div className="flex gap-1">
                                            {users.links.map((link, index) => {
                                                if (index === 0 || index === users.links.length - 1) {
                                                    return (
                                                        <Button
                                                            key={index}
                                                            variant={link.active ? 'default' : 'outline'}
                                                            size="sm"
                                                            disabled={!link.url}
                                                            onClick={() => link.url && router.visit(link.url)}
                                                        >
                                                            {link.label.replace('&laquo;', '«').replace('&raquo;', '»')}
                                                        </Button>
                                                    );
                                                }
                                                return (
                                                    <Button
                                                        key={index}
                                                        variant={link.active ? 'default' : 'outline'}
                                                        size="sm"
                                                        disabled={!link.url}
                                                        onClick={() => link.url && router.visit(link.url)}
                                                    >
                                                        {link.label}
                                                    </Button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            <Dialog open={deleteModalOpen} onOpenChange={setDeleteModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>¿Estás seguro?</DialogTitle>
                        <DialogDescription>
                            Esta acción eliminará a <strong>{userToDelete?.name}</strong> del espacio. Esta acción no se puede deshacer.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteModalOpen(false)}>
                            Cancelar
                        </Button>
                        <Button variant="destructive" onClick={handleDeleteConfirm}>
                            Eliminar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
