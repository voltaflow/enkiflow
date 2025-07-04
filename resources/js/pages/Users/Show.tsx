import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import React, { useState } from 'react';
// import { Slider } from '@/components/ui/slider';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { cn } from '@/lib/utils';
import { Archive, ArrowLeft, Check, CheckCircle2, Clock, DollarSign, Mail, RotateCcw, Shield, Trash2, User, X, XCircle } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

interface Permission {
    value: string;
    label: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    status: 'active' | 'invited' | 'archived';
    capacity_hours: number;
    cost_rate?: number | null;
    billable_rate?: number | null;
    joined_at: string;
    effective_permissions: string[];
    custom_permissions?: string[] | null;
    additional_permissions?: string[] | null;
    revoked_permissions?: string[] | null;
}

interface Props {
    user: User;
    availableRoles: Array<{
        value: string;
        label: string;
        description?: string;
    }>;
    canEditUsers: boolean;
    canDeleteUsers: boolean;
    canManageRoles: boolean;
    canResetPasswords: boolean;
}

const permissionCategories = [
    {
        name: 'space',
        label: 'Gestión del Espacio',
        permissions: [
            { value: 'manage_space', label: 'Administrar espacio' },
            { value: 'view_space', label: 'Ver espacio' },
            { value: 'delete_space', label: 'Eliminar espacio' },
        ],
    },
    {
        name: 'users',
        label: 'Gestión de Usuarios',
        permissions: [
            { value: 'invite_users', label: 'Invitar usuarios' },
            { value: 'remove_users', label: 'Eliminar usuarios' },
            { value: 'manage_user_roles', label: 'Gestionar roles' },
        ],
    },
    {
        name: 'billing',
        label: 'Facturación',
        permissions: [
            { value: 'manage_billing', label: 'Gestionar facturación' },
            { value: 'view_invoices', label: 'Ver facturas' },
        ],
    },
    {
        name: 'projects',
        label: 'Proyectos',
        permissions: [
            { value: 'create_projects', label: 'Crear proyectos' },
            { value: 'edit_projects', label: 'Editar proyectos' },
            { value: 'delete_projects', label: 'Eliminar proyectos' },
            { value: 'view_all_projects', label: 'Ver todos los proyectos' },
        ],
    },
    {
        name: 'tasks',
        label: 'Tareas',
        permissions: [
            { value: 'create_tasks', label: 'Crear tareas' },
            { value: 'edit_any_task', label: 'Editar cualquier tarea' },
            { value: 'edit_own_tasks', label: 'Editar tareas propias' },
            { value: 'delete_any_task', label: 'Eliminar cualquier tarea' },
            { value: 'delete_own_tasks', label: 'Eliminar tareas propias' },
            { value: 'view_all_tasks', label: 'Ver todas las tareas' },
        ],
    },
    {
        name: 'comments',
        label: 'Comentarios',
        permissions: [
            { value: 'create_comments', label: 'Crear comentarios' },
            { value: 'edit_any_comment', label: 'Editar cualquier comentario' },
            { value: 'edit_own_comments', label: 'Editar comentarios propios' },
            { value: 'delete_any_comment', label: 'Eliminar cualquier comentario' },
            { value: 'delete_own_comments', label: 'Eliminar comentarios propios' },
        ],
    },
    {
        name: 'other',
        label: 'Otros',
        permissions: [
            { value: 'manage_tags', label: 'Gestionar etiquetas' },
            { value: 'view_statistics', label: 'Ver estadísticas' },
        ],
    },
];

export default function Show({ user: initialUser, availableRoles, canEditUsers, canDeleteUsers, canManageRoles, canResetPasswords }: Props) {
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [notification, setNotification] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
    const { props } = usePage<any>();
    const [user, setUser] = useState(initialUser);

    const { data, setData, put, processing } = useForm({
        role: user.role,
        capacity_hours: user.capacity_hours || 40,
        cost_rate: user.cost_rate || '',
        billable_rate: user.billable_rate || '',
        status: user.status || 'active',
    });

    // Sincronizar el estado del usuario cuando cambie el prop inicial
    useEffect(() => {
        setUser(initialUser);
        setData({
            role: initialUser.role,
            capacity_hours: initialUser.capacity_hours || 40,
            cost_rate: initialUser.cost_rate || '',
            billable_rate: initialUser.billable_rate || '',
            status: initialUser.status || 'active',
        });
    }, [initialUser]);

    // Manejar notificaciones flash de Laravel
    useEffect(() => {
        if (props.flash?.success) {
            setNotification({ type: 'success', message: props.flash.success });
            setTimeout(() => setNotification(null), 5000);
        }
        if (props.flash?.error || props.errors?.error) {
            setNotification({ type: 'error', message: props.flash?.error || props.errors?.error });
            setTimeout(() => setNotification(null), 5000);
        }
    }, [props.flash, props.errors]);

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getRoleLabel = (role: string) => {
        const labels: Record<string, string> = {
            owner: 'Propietario',
            admin: 'Administrador',
            manager: 'Gerente',
            member: 'Miembro',
            guest: 'Invitado',
        };
        return labels[role] || role;
    };

    const getStatusLabel = (status: string) => {
        const labels: Record<string, string> = {
            active: 'Activo',
            invited: 'Invitado',
            archived: 'Archivado',
        };
        return labels[status] || status;
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('users.update', user.id));
    };

    const handleRoleChange = (value: string) => {
        setData('role', value);
    };

    const handleUpdateRole = () => {
        setNotification({ type: 'success', message: 'Actualizando rol...' });
        router.put(route('users.update', user.id), 
            { role: data.role },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setUser({ ...user, role: data.role });
                    setNotification({ type: 'success', message: 'Rol actualizado correctamente' });
                },
                onError: () => {
                    setNotification({ type: 'error', message: 'Error al actualizar el rol' });
                }
            }
        );
    };

    const handleStatusToggle = () => {
        const newStatus = user.status === 'archived' ? 'active' : 'archived';
        setNotification({ type: 'success', message: newStatus === 'active' ? 'Restaurando usuario...' : 'Archivando usuario...' });
        
        router.put(route('users.update', user.id), 
            { status: newStatus },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setData('status', newStatus);
                    setUser({ ...user, status: newStatus });
                    setNotification({ 
                        type: 'success', 
                        message: newStatus === 'active' ? 'Usuario restaurado correctamente' : 'Usuario archivado correctamente' 
                    });
                },
                onError: () => {
                    setNotification({ type: 'error', message: 'Error al cambiar el estado del usuario' });
                }
            }
        );
    };

    const handleResetPassword = () => {
        setNotification({ type: 'success', message: 'Enviando enlace de restablecimiento...' });
        
        router.post(
            route('users.reset-password', user.id),
            {},
            {
                preserveScroll: true,
                onError: () => {
                    setNotification({ type: 'error', message: 'Error al enviar el enlace de restablecimiento' });
                },
            },
        );
    };

    const handleDelete = () => {
        router.delete(route('users.destroy', user.id), {
            onSuccess: () => {
                setDeleteModalOpen(false);
            },
        });
    };

    const hasPermission = (permissionValue: string) => {
        return user.effective_permissions.includes(permissionValue);
    };

    return (
        <AppLayout>
            <Head title={`Usuario: ${user.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Notification */}
                    {notification && (
                        <Alert className={cn(
                            "mb-6",
                            notification.type === 'success' && "border-green-500 bg-green-50",
                            notification.type === 'error' && "border-red-500 bg-red-50"
                        )}>
                            {notification.type === 'success' ? (
                                <CheckCircle2 className="h-4 w-4 text-green-600" />
                            ) : (
                                <XCircle className="h-4 w-4 text-red-600" />
                            )}
                            <AlertDescription className={cn(
                                notification.type === 'success' && "text-green-800",
                                notification.type === 'error' && "text-red-800"
                            )}>
                                {notification.message}
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* Header */}
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={route('users.index')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{user.name}</h1>
                            <p className="text-muted-foreground mt-1">Gestión de usuario</p>
                        </div>
                    </div>

                    {/* User Info Grid */}
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                        {/* Basic Profile */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <User className="h-5 w-5" />
                                    Perfil Básico
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-center">
                                    <Avatar className="h-24 w-24">
                                        <AvatarFallback className="text-2xl">{getInitials(user.name)}</AvatarFallback>
                                    </Avatar>
                                </div>
                                <div className="space-y-3">
                                    <div>
                                        <Label className="text-muted-foreground text-sm">Nombre</Label>
                                        <p className="font-medium">{user.name}</p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground text-sm">Email</Label>
                                        <p className="font-medium">{user.email}</p>
                                    </div>
                                    <div>
                                        <Label className="text-muted-foreground text-sm">Miembro desde</Label>
                                        <p className="font-medium">{formatDate(user.joined_at)}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Access & Security */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Shield className="h-5 w-5" />
                                    Acceso y Seguridad
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label className="text-muted-foreground text-sm">Estado</Label>
                                    <div className="mt-1 flex items-center gap-3">
                                        <Badge variant={data.status === 'active' ? 'default' : data.status === 'invited' ? 'secondary' : 'outline'}>
                                            {getStatusLabel(data.status)}
                                        </Badge>
                                        {canManageRoles && user.status !== 'invited' && (
                                            <Button variant="ghost" size="sm" onClick={handleStatusToggle} disabled={processing}>
                                                {user.status === 'archived' ? (
                                                    <>
                                                        <RotateCcw className="mr-1 h-4 w-4" />
                                                        Restaurar
                                                    </>
                                                ) : (
                                                    <>
                                                        <Archive className="mr-1 h-4 w-4" />
                                                        Archivar
                                                    </>
                                                )}
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                <div>
                                    <Label className="text-muted-foreground text-sm">Rol</Label>
                                    {canManageRoles ? (
                                        <>
                                            <div className="flex gap-2 mt-1">
                                                <Select value={data.role} onValueChange={handleRoleChange} disabled={processing}>
                                                    <SelectTrigger className="flex-1">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {availableRoles.map((role) => (
                                                            <SelectItem key={role.value} value={role.value}>
                                                                {role.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                {data.role !== user.role && (
                                                    <Button 
                                                        variant="default" 
                                                        size="sm"
                                                        onClick={handleUpdateRole}
                                                        disabled={processing}
                                                    >
                                                        Actualizar permisos
                                                    </Button>
                                                )}
                                            </div>
                                        </>
                                    ) : (
                                        <div className="mt-1">
                                            <Badge variant="secondary">{getRoleLabel(user.role)}</Badge>
                                        </div>
                                    )}
                                    {availableRoles.find((r) => r.value === data.role)?.description && (
                                        <p className="text-muted-foreground mt-2 text-sm">
                                            {availableRoles.find((r) => r.value === data.role)?.description}
                                        </p>
                                    )}
                                </div>

                                {canResetPasswords && (
                                    <div className="pt-4">
                                        <Button variant="outline" className="w-full" onClick={handleResetPassword} disabled={processing}>
                                            <Mail className="mr-2 h-4 w-4" />
                                            Enviar email de restablecimiento
                                        </Button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Availability & Rates */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Clock className="h-5 w-5" />
                                    Disponibilidad y Tarifas
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div>
                                        <Label>Disponibilidad semanal</Label>
                                        <div className="mt-2 space-y-2">
                                            <div className="flex items-center justify-between">
                                                <span className="text-sm font-medium">{data.capacity_hours} horas/semana</span>
                                                <div className="flex gap-2">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => setData('capacity_hours', 20)}
                                                        disabled={!canEditUsers || processing}
                                                    >
                                                        20h
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => setData('capacity_hours', 40)}
                                                        disabled={!canEditUsers || processing}
                                                    >
                                                        40h
                                                    </Button>
                                                </div>
                                            </div>
                                            <Input
                                                type="range"
                                                min="0"
                                                max="60"
                                                step="1"
                                                value={data.capacity_hours}
                                                onChange={(e) => setData('capacity_hours', parseInt(e.target.value))}
                                                disabled={!canEditUsers || processing}
                                                className="py-4"
                                            />
                                            <div className="text-muted-foreground flex justify-between text-xs">
                                                <span>0h</span>
                                                <span>20h</span>
                                                <span>40h</span>
                                                <span>60h</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="cost_rate">Tasa interna (costo/hora)</Label>
                                        <div className="relative mt-1">
                                            <DollarSign className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform" />
                                            <Input
                                                id="cost_rate"
                                                type="number"
                                                step="0.01"
                                                value={data.cost_rate}
                                                onChange={(e) => setData('cost_rate', e.target.value)}
                                                className="pl-10"
                                                disabled={!canEditUsers || processing}
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="billable_rate">Tasa facturable (precio/hora)</Label>
                                        <div className="relative mt-1">
                                            <DollarSign className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform" />
                                            <Input
                                                id="billable_rate"
                                                type="number"
                                                step="0.01"
                                                value={data.billable_rate}
                                                onChange={(e) => setData('billable_rate', e.target.value)}
                                                className="pl-10"
                                                disabled={!canEditUsers || processing}
                                            />
                                        </div>
                                    </div>

                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Save Changes Button */}
                    {canEditUsers && (
                        <Card>
                            <CardContent className="py-4">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-medium">Guardar cambios de disponibilidad y tarifas</p>
                                        <p className="text-sm text-muted-foreground">
                                            Los cambios en la disponibilidad semanal, tasa interna y tasa facturable requieren guardarse
                                        </p>
                                    </div>
                                    <Button 
                                        onClick={handleSubmit} 
                                        disabled={processing}
                                        size="lg"
                                    >
                                        Guardar Cambios
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Effective Permissions */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Permisos Efectivos</CardTitle>
                            <CardDescription>Permisos que tiene este usuario basados en su rol y configuración personalizada.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {permissionCategories.map((category) => (
                                    <div key={category.name} className="space-y-2">
                                        <h4 className="text-sm font-medium">{category.label}</h4>
                                        <ul className="space-y-1">
                                            {category.permissions.map((permission) => (
                                                <li key={permission.value} className="flex items-center text-sm">
                                                    {hasPermission(permission.value) ? (
                                                        <Check className="mr-2 h-4 w-4 text-green-600" />
                                                    ) : (
                                                        <X className="mr-2 h-4 w-4 text-gray-300" />
                                                    )}
                                                    <span
                                                        className={cn(hasPermission(permission.value) ? 'text-foreground' : 'text-muted-foreground')}
                                                    >
                                                        {permission.label}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Delete Button */}
                    {canDeleteUsers && (
                        <div className="flex justify-end">
                            <Button variant="destructive" onClick={() => setDeleteModalOpen(true)}>
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar Usuario
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            <Dialog open={deleteModalOpen} onOpenChange={setDeleteModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>¿Estás seguro?</DialogTitle>
                        <DialogDescription>
                            Esta acción eliminará a <strong>{user.name}</strong> del espacio. Esta acción no se puede deshacer.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteModalOpen(false)}>
                            Cancelar
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            Eliminar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
