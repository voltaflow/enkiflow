import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Check, Users } from 'lucide-react';
import { useState } from 'react';

interface InviteItem {
    email: string;
    role: string;
}

interface ConfirmProps {
    name: string;
    subdomain: string;
    plan: string;
    invites: InviteItem[];
}

export default function Confirm({ name, subdomain, plan, invites }: ConfirmProps) {
    const [loading, setLoading] = useState(false);

    const { post } = useForm();

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        setLoading(true);
        post(route('spaces.setup.store'));
    };

    const formatRole = (role: string) => {
        switch (role) {
            case 'admin':
                return 'Administrador';
            case 'manager':
                return 'Gerente';
            case 'member':
                return 'Miembro';
            case 'guest':
                return 'Invitado';
            default:
                return role;
        }
    };

    return (
        <AppLayout>
            <Head title="Confirmar Creación de Espacio" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-8 text-center">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">Confirma los Detalles</h1>
                        <p className="text-xl text-gray-600 dark:text-gray-400">Revisa la información antes de crear tu nuevo espacio</p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Resumen del Espacio</CardTitle>
                            <CardDescription>A continuación se muestra un resumen de la configuración de tu nuevo espacio.</CardDescription>
                        </CardHeader>
                        <form onSubmit={submit}>
                            <CardContent className="space-y-6">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="bg-muted rounded-md p-4">
                                        <h3 className="mb-2 font-semibold">Información del Espacio</h3>
                                        <div className="space-y-2">
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Nombre:</span>
                                                <span className="font-medium">{name}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Subdominio:</span>
                                                <span className="font-medium">{subdomain}.example.com</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Plan:</span>
                                                <span className="font-medium">{plan.charAt(0).toUpperCase() + plan.slice(1)}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="bg-muted rounded-md p-4">
                                        <h3 className="mb-2 font-semibold">Miembros Invitados</h3>
                                        {invites.length > 0 ? (
                                            <div className="space-y-2">
                                                {invites.map((invite, index) => (
                                                    <div key={index} className="flex justify-between">
                                                        <span className="mr-2 truncate">{invite.email}</span>
                                                        <span className="text-muted-foreground text-sm">{formatRole(invite.role)}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="text-muted-foreground flex flex-col items-center justify-center py-4">
                                                <Users className="mb-2 h-8 w-8" />
                                                <p>No se han invitado miembros</p>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="rounded-md border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950">
                                    <div className="flex gap-2">
                                        <Check className="mt-0.5 h-5 w-5 text-green-600 dark:text-green-400" />
                                        <div>
                                            <h3 className="font-medium text-green-800 dark:text-green-300">Todo listo para comenzar</h3>
                                            <p className="text-sm text-green-700 dark:text-green-400">
                                                Tu nuevo espacio estará disponible inmediatamente después de la creación. No se requiere tarjeta de
                                                crédito para el período de prueba.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter className="flex justify-between">
                                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                    Volver
                                </Button>
                                <Button type="submit" disabled={loading}>
                                    {loading ? 'Creando espacio...' : 'Crear Espacio'}
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
