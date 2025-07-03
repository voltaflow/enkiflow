import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Building2, Mail, Shield, Clock } from 'lucide-react';

interface Props {
    invitation: {
        email: string;
        role: string;
        role_label: string;
        expires_at: string;
    };
    space: {
        name: string;
        owner: string;
    };
    userExists: boolean;
    isAuthenticated: boolean;
    matchesCurrentUser: boolean;
}

export default function Show({ invitation, space, userExists, isAuthenticated, matchesCurrentUser }: Props) {
    const handleAccept = () => {
        router.post(route('invitation.accept', { token: window.location.pathname.split('/').pop() }));
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-MX', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <AuthLayout title="Invitación" description="Has sido invitado a unirte a este espacio de trabajo">
            <Head title="Invitaci�n" />

            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center">
                        <Mail className="h-8 w-8 text-primary" />
                    </div>
                    <CardTitle className="text-2xl">Invitaci�n a {space.name}</CardTitle>
                    <CardDescription>
                        Has sido invitado a unirte a este espacio de trabajo
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="space-y-4">
                        <div className="flex items-center gap-3">
                            <Building2 className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p className="text-sm text-muted-foreground">Espacio de trabajo</p>
                                <p className="font-medium">{space.name}</p>
                                <p className="text-xs text-muted-foreground">Administrado por {space.owner}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <Mail className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p className="text-sm text-muted-foreground">Invitaci�n para</p>
                                <p className="font-medium">{invitation.email}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <Shield className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p className="text-sm text-muted-foreground">Rol asignado</p>
                                <Badge variant="secondary" className="mt-1">
                                    {invitation.role_label}
                                </Badge>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <Clock className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p className="text-sm text-muted-foreground">V�lida hasta</p>
                                <p className="font-medium">{formatDate(invitation.expires_at)}</p>
                            </div>
                        </div>
                    </div>

                    {isAuthenticated && matchesCurrentUser ? (
                        <div className="space-y-4">
                            <Alert>
                                <AlertDescription>
                                    Haz clic en "Aceptar Invitaci�n" para unirte a {space.name}
                                </AlertDescription>
                            </Alert>
                            <Button onClick={handleAccept} className="w-full">
                                Aceptar Invitaci�n
                            </Button>
                        </div>
                    ) : isAuthenticated && !matchesCurrentUser ? (
                        <div className="space-y-4">
                            <Alert variant="destructive">
                                <AlertDescription>
                                    Esta invitaci�n es para {invitation.email}. 
                                    Por favor, cierra sesi�n e inicia con la cuenta correcta.
                                </AlertDescription>
                            </Alert>
                            <Link href={route('logout')} method="post" as="button" className="w-full">
                                <Button variant="outline" className="w-full">
                                    Cerrar Sesi�n
                                </Button>
                            </Link>
                        </div>
                    ) : userExists ? (
                        <div className="space-y-4">
                            <Alert>
                                <AlertDescription>
                                    Ya tienes una cuenta. Inicia sesi�n para aceptar esta invitaci�n.
                                </AlertDescription>
                            </Alert>
                            <Link href={route('login', { invitation: window.location.pathname.split('/').pop() })}>
                                <Button className="w-full">
                                    Iniciar Sesi�n
                                </Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <Alert>
                                <AlertDescription>
                                    Necesitas crear una cuenta para aceptar esta invitaci�n.
                                </AlertDescription>
                            </Alert>
                            <Link href={route('invitation.register.form', { 
                                token: window.location.pathname.split('/').pop()
                            })}>
                                <Button className="w-full">
                                    Crear Cuenta
                                </Button>
                            </Link>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AuthLayout>
    );
}