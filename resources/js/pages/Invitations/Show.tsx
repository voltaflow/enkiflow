import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AuthLayout from '@/layouts/auth-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Building2, Clock, Mail, Shield } from 'lucide-react';

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
            minute: '2-digit',
        });
    };

    return (
        <AuthLayout title="Invitación" description="Has sido invitado a unirte a este espacio de trabajo">
            <Head title="Invitaci�n" />

            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="bg-primary/10 mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full">
                        <Mail className="text-primary h-8 w-8" />
                    </div>
                    <CardTitle className="text-2xl">Invitaci�n a {space.name}</CardTitle>
                    <CardDescription>Has sido invitado a unirte a este espacio de trabajo</CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="space-y-4">
                        <div className="flex items-center gap-3">
                            <Building2 className="text-muted-foreground h-5 w-5" />
                            <div>
                                <p className="text-muted-foreground text-sm">Espacio de trabajo</p>
                                <p className="font-medium">{space.name}</p>
                                <p className="text-muted-foreground text-xs">Administrado por {space.owner}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <Mail className="text-muted-foreground h-5 w-5" />
                            <div>
                                <p className="text-muted-foreground text-sm">Invitaci�n para</p>
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

                        <div className="flex items-center gap-3">
                            <Clock className="text-muted-foreground h-5 w-5" />
                            <div>
                                <p className="text-muted-foreground text-sm">V�lida hasta</p>
                                <p className="font-medium">{formatDate(invitation.expires_at)}</p>
                            </div>
                        </div>
                    </div>

                    {isAuthenticated && matchesCurrentUser ? (
                        <div className="space-y-4">
                            <Alert>
                                <AlertDescription>Haz clic en "Aceptar Invitaci�n" para unirte a {space.name}</AlertDescription>
                            </Alert>
                            <Button onClick={handleAccept} className="w-full">
                                Aceptar Invitaci�n
                            </Button>
                        </div>
                    ) : isAuthenticated && !matchesCurrentUser ? (
                        <div className="space-y-4">
                            <Alert variant="destructive">
                                <AlertDescription>
                                    Esta invitaci�n es para {invitation.email}. Por favor, cierra sesi�n e inicia con la cuenta correcta.
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
                                <AlertDescription>Ya tienes una cuenta. Inicia sesi�n para aceptar esta invitaci�n.</AlertDescription>
                            </Alert>
                            <Link href={route('login', { invitation: window.location.pathname.split('/').pop() })}>
                                <Button className="w-full">Iniciar Sesi�n</Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <Alert>
                                <AlertDescription>Necesitas crear una cuenta para aceptar esta invitaci�n.</AlertDescription>
                            </Alert>
                            <Link
                                href={route('invitation.register.form', {
                                    token: window.location.pathname.split('/').pop(),
                                })}
                            >
                                <Button className="w-full">Crear Cuenta</Button>
                            </Link>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AuthLayout>
    );
}
