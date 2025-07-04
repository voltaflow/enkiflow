import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { Building2, Mail, Shield, User } from 'lucide-react';
import React from 'react';

interface Props {
    invitation: {
        email: string;
        token: string;
        role: string;
        role_label: string;
        expires_at: string;
    };
    space: {
        name: string;
        owner: string;
    };
}

export default function Register({ invitation, space }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('invitation.register', { token: invitation.token }));
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-MX', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AuthLayout title="Crear Cuenta" description={`Completa tu registro para unirte a ${space.name}`}>
            <Head title="Crear Cuenta" />

            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="bg-primary/10 mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full">
                        <User className="text-primary h-8 w-8" />
                    </div>
                    <CardTitle className="text-2xl">Crear tu cuenta</CardTitle>
                    <CardDescription>Completa tu información para unirte a {space.name}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="mb-6 space-y-4">
                        <div className="flex items-center gap-3">
                            <Building2 className="text-muted-foreground h-5 w-5" />
                            <div>
                                <p className="text-muted-foreground text-sm">Espacio de trabajo</p>
                                <p className="font-medium">{space.name}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <Mail className="text-muted-foreground h-5 w-5" />
                            <div>
                                <p className="text-muted-foreground text-sm">Correo electrónico</p>
                                <p className="font-medium">{invitation.email}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <Shield className="text-muted-foreground h-5 w-5" />
                            <div>
                                <p className="text-muted-foreground text-sm">Rol asignado</p>
                                <Badge variant="secondary" className="mt-1">
                                    {invitation.role_label}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="first_name">Nombre</Label>
                            <Input
                                id="first_name"
                                type="text"
                                value={data.first_name}
                                onChange={(e) => setData('first_name', e.target.value)}
                                required
                            />
                            {errors.first_name && <p className="text-destructive text-sm">{errors.first_name}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="last_name">Apellido</Label>
                            <Input
                                id="last_name"
                                type="text"
                                value={data.last_name}
                                onChange={(e) => setData('last_name', e.target.value)}
                                required
                            />
                            {errors.last_name && <p className="text-destructive text-sm">{errors.last_name}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password">Contraseña</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                required
                            />
                            {errors.password && <p className="text-destructive text-sm">{errors.password}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">Confirmar Contraseña</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                required
                            />
                        </div>

                        <Button type="submit" className="mt-6 w-full" disabled={processing}>
                            {processing ? 'Creando cuenta...' : 'Crear cuenta y unirme'}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </AuthLayout>
    );
}
