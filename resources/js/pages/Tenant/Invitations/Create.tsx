import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import React from 'react';

interface Role {
    value: string;
    label: string;
    description: string;
}

interface Props {
    availableRoles: Role[];
    canManageRoles: boolean;
}

export default function Create({ availableRoles, canManageRoles }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        role: 'member',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('tenant.invitations.store'));
    };

    return (
        <AppLayout>
            <Head title="Nueva Invitaci�n" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-4">
                        <Link href={route('tenant.invitations.index')}>
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Nueva Invitaci�n</h1>
                            <p className="text-muted-foreground mt-1">Invita a un nuevo miembro a tu espacio de trabajo</p>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Detalles de la Invitaci�n</CardTitle>
                                <CardDescription>El usuario recibir� un correo electr�nico con un enlace para unirse</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="email">Correo Electr�nico *</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="usuario@ejemplo.com"
                                        required
                                    />
                                    {errors.email && <p className="text-destructive text-sm">{errors.email}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="role">Rol *</Label>
                                    {canManageRoles ? (
                                        <>
                                            <Select value={data.role} onValueChange={(value) => setData('role', value)}>
                                                <SelectTrigger id="role">
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
                                            <p className="text-muted-foreground text-sm">
                                                {availableRoles.find((r) => r.value === data.role)?.description}
                                            </p>
                                        </>
                                    ) : (
                                        <div>
                                            <Input value={availableRoles.find((r) => r.value === data.role)?.label || 'Miembro'} disabled />
                                            <p className="text-muted-foreground mt-1 text-sm">
                                                Solo los administradores pueden asignar roles diferentes
                                            </p>
                                        </div>
                                    )}
                                    {errors.role && <p className="text-destructive text-sm">{errors.role}</p>}
                                </div>

                                <Alert>
                                    <AlertDescription>
                                        La invitaci�n ser� v�lida por 7 d�as. Si el usuario no la acepta en ese tiempo, deber�s enviar una nueva
                                        invitaci�n.
                                    </AlertDescription>
                                </Alert>
                            </CardContent>
                        </Card>

                        <div className="flex gap-4">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Enviando...' : 'Enviar Invitaci�n'}
                            </Button>
                            <Link href={route('tenant.invitations.index')}>
                                <Button variant="outline" type="button">
                                    Cancelar
                                </Button>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
