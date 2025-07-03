import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { XCircle } from 'lucide-react';

interface Props {
    error: string;
}

export default function Invalid({ error }: Props) {
    return (
        <AuthLayout title="Invitación Inválida" description="No se puede procesar esta invitación">
            <Head title="Invitaci�n Inv�lida" />

            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 h-16 w-16 rounded-full bg-destructive/10 flex items-center justify-center">
                        <XCircle className="h-8 w-8 text-destructive" />
                    </div>
                    <CardTitle className="text-2xl">Invitaci�n Inv�lida</CardTitle>
                    <CardDescription>
                        No se puede procesar esta invitaci�n
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <Alert variant="destructive">
                        <AlertDescription>
                            {error}
                        </AlertDescription>
                    </Alert>

                    <div className="space-y-4">
                        <p className="text-sm text-muted-foreground text-center">
                            Si crees que esto es un error, contacta al administrador del espacio
                            o solicita que te env�en una nueva invitaci�n.
                        </p>

                        <Link href={route('login')}>
                            <Button variant="outline" className="w-full">
                                Ir al Inicio de Sesi�n
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </AuthLayout>
    );
}