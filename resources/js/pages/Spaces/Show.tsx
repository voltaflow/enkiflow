import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Edit, Plus, Trash2 } from 'lucide-react';

interface SpaceUser {
    id: number;
    name: string;
    email: string;
    pivot: {
        role: string;
    };
}

interface Domain {
    id: number;
    domain: string;
}

interface Space {
    id: string;
    name: string;
    owner_id: number;
    data: {
        plan: string;
    };
    users: SpaceUser[];
    domains: Domain[];
    owner: {
        id: number;
        name: string;
        email: string;
    };
}

interface ShowProps {
    space: Space;
    is_owner: boolean;
    member_count: number;
}

export default function Show({ space, is_owner, member_count }: ShowProps) {
    const [isInviteOpen, setIsInviteOpen] = useState(false);
    const [isConfirmDeleteOpen, setIsConfirmDeleteOpen] = useState(false);
    
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Espacios',
            href: route('spaces.index'),
        },
        {
            title: space.name,
            href: route('spaces.show', space.id),
        },
    ];

    const inviteForm = useForm({
        email: '',
        role: 'member',
    });

    const deleteForm = useForm({});

    const handleInviteSubmit = (e: FormEvent) => {
        e.preventDefault();
        inviteForm.post(route('spaces.invite', space.id), {
            onSuccess: () => {
                inviteForm.reset();
                setIsInviteOpen(false);
            },
        });
    };

    const handleDelete = () => {
        deleteForm.delete(route('spaces.destroy', space.id));
    };

    const handleRemoveUser = (userId: number) => {
        if (confirm('¿Estás seguro de que quieres eliminar a este usuario del espacio?')) {
            window.location.href = route('spaces.users.destroy', [space.id, userId]);
        }
    };

    // Get the primary domain (first one in the list)
    const primaryDomain = space.domains && space.domains.length > 0 
        ? space.domains[0].domain 
        : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Espacio: ${space.name}`} />
            
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-semibold">{space.name}</h1>
                        <p className="text-muted-foreground">
                            ID: {space.id}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {is_owner && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={route('spaces.edit', space.id)}>
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar
                                    </Link>
                                </Button>
                                <Button 
                                    variant="destructive" 
                                    onClick={() => setIsConfirmDeleteOpen(true)}
                                >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Eliminar
                                </Button>
                            </>
                        )}
                        <Button asChild>
                            <a href={primaryDomain ? `http://${primaryDomain}` : `/${space.id}`}>
                                Acceder al Espacio
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Space Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles del Espacio</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Plan</h3>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {space.data.plan === 'free' ? 'Gratuito' : 'Premium'}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Propietario</h3>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {space.owner.name} ({space.owner.email})
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Miembros</h3>
                                <p className="mt-1 text-gray-900 dark:text-white">
                                    {member_count} {member_count === 1 ? 'miembro' : 'miembros'}
                                </p>
                            </div>
                            {primaryDomain && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Dominio</h3>
                                    <p className="mt-1 text-gray-900 dark:text-white">
                                        <a 
                                            href={`http://${primaryDomain}`} 
                                            target="_blank" 
                                            rel="noopener noreferrer"
                                            className="text-blue-600 dark:text-blue-400 hover:underline"
                                        >
                                            {primaryDomain}
                                        </a>
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Members Management */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Miembros</CardTitle>
                                <CardDescription>
                                    Gestiona los miembros de este espacio
                                </CardDescription>
                            </div>
                            {is_owner && (
                                <Dialog open={isInviteOpen} onOpenChange={setIsInviteOpen}>
                                    <DialogTrigger asChild>
                                        <Button size="sm">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Invitar
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Invitar Usuario</DialogTitle>
                                            <DialogDescription>
                                                Invita a un usuario a unirse a este espacio.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <form onSubmit={handleInviteSubmit}>
                                            <div className="space-y-4 py-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="email">Email</Label>
                                                    <Input
                                                        id="email"
                                                        placeholder="usuario@ejemplo.com"
                                                        type="email"
                                                        value={inviteForm.data.email}
                                                        onChange={(e) => inviteForm.setData('email', e.target.value)}
                                                        required
                                                    />
                                                    <InputError message={inviteForm.errors.email} />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="role">Rol</Label>
                                                    <Select 
                                                        value={inviteForm.data.role}
                                                        onValueChange={(value) => inviteForm.setData('role', value)}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Seleccionar rol" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="admin">Administrador</SelectItem>
                                                            <SelectItem value="member">Miembro</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError message={inviteForm.errors.role} />
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button 
                                                    type="button" 
                                                    variant="outline" 
                                                    onClick={() => setIsInviteOpen(false)}
                                                    disabled={inviteForm.processing}
                                                >
                                                    Cancelar
                                                </Button>
                                                <Button type="submit" disabled={inviteForm.processing}>
                                                    Invitar
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            )}
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {space.users.map(user => (
                                    <div key={user.id} className="flex items-center justify-between border-b pb-3 last:border-b-0 last:pb-0">
                                        <div>
                                            <div className="font-medium">{user.name}</div>
                                            <div className="text-sm text-muted-foreground">{user.email}</div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge className={user.pivot.role === 'admin' ? 'bg-blue-500' : 'bg-gray-500'}>
                                                {user.pivot.role === 'admin' ? 'Admin' : 'Miembro'}
                                            </Badge>
                                            {is_owner && user.id !== space.owner_id && (
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm" 
                                                    onClick={() => handleRemoveUser(user.id)}
                                                >
                                                    <Trash2 className="h-4 w-4 text-red-500" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Delete Confirmation Dialog */}
            <Dialog open={isConfirmDeleteOpen} onOpenChange={setIsConfirmDeleteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Confirmar Eliminación</DialogTitle>
                        <DialogDescription>
                            ¿Estás seguro de que quieres eliminar el espacio "{space.name}"? Esta acción no se puede deshacer.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button 
                            type="button" 
                            variant="outline" 
                            onClick={() => setIsConfirmDeleteOpen(false)}
                            disabled={deleteForm.processing}
                        >
                            Cancelar
                        </Button>
                        <Button 
                            variant="destructive" 
                            onClick={handleDelete}
                            disabled={deleteForm.processing}
                        >
                            Eliminar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}