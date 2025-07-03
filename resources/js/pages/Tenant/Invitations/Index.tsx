import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    Plus, 
    Mail, 
    Clock, 
    User,
    RefreshCw,
    Trash2
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';

interface Invitation {
    id: number;
    email: string;
    role: string;
    role_label: string;
    expires_at: string;
    created_at: string;
    invited_by: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    invitations: Invitation[];
    canInviteUsers: boolean;
}

export default function Index({ invitations, canInviteUsers }: Props) {
    const handleResend = (invitation: Invitation) => {
        router.post(route('tenant.invitations.resend', invitation.id), {}, {
            preserveScroll: true,
        });
    };

    const handleRevoke = (invitation: Invitation) => {
        if (confirm(`¿Estás seguro de que deseas revocar la invitación para ${invitation.email}?`)) {
            router.delete(route('tenant.invitations.destroy', invitation.id), {
                preserveScroll: true,
            });
        }
    };

    const formatDate = (dateString: string) => {
        return formatDistanceToNow(new Date(dateString), { 
            addSuffix: true,
            locale: es 
        });
    };

    return (
        <AppLayout>
            <Head title="Invitaciones" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Invitaciones Pendientes</h1>
                            <p className="text-muted-foreground mt-1">
                                Gestiona las invitaciones enviadas a nuevos miembros
                            </p>
                        </div>
                        {canInviteUsers && (
                            <Link href={route('tenant.invitations.create')}>
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nueva Invitación
                                </Button>
                            </Link>
                        )}
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Invitaciones Activas</CardTitle>
                            <CardDescription>
                                Las invitaciones expiran después de 7 días si no son aceptadas
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {invitations.length === 0 ? (
                                <div className="text-center py-12">
                                    <Mail className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                    <h3 className="text-lg font-medium">No hay invitaciones pendientes</h3>
                                    <p className="text-muted-foreground mt-2">
                                        Las invitaciones enviadas aparecerán aquí
                                    </p>
                                    {canInviteUsers && (
                                        <Link href={route('tenant.invitations.create')}>
                                            <Button className="mt-4">
                                                <Plus className="mr-2 h-4 w-4" />
                                                Enviar Primera Invitación
                                            </Button>
                                        </Link>
                                    )}
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {invitations.map((invitation) => (
                                        <div
                                            key={invitation.id}
                                            className="border rounded-lg p-4 hover:bg-accent/50 transition-colors"
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-3 mb-2">
                                                        <h3 className="text-lg font-semibold">{invitation.email}</h3>
                                                        <Badge variant="secondary">
                                                            {invitation.role_label}
                                                        </Badge>
                                                    </div>
                                                    
                                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm text-muted-foreground">
                                                        <div className="flex items-center gap-1">
                                                            <User className="h-3 w-3" />
                                                            <span>
                                                                Invitado por: {invitation.invited_by?.name || 'Sistema'}
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-1">
                                                            <Clock className="h-3 w-3" />
                                                            <span>
                                                                Enviada {formatDate(invitation.created_at)}
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-1">
                                                            <Clock className="h-3 w-3" />
                                                            <span>
                                                                Expira {formatDate(invitation.expires_at)}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="sm">
                                                            Acciones
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem onClick={() => handleResend(invitation)}>
                                                            <RefreshCw className="mr-2 h-4 w-4" />
                                                            Reenviar
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            onClick={() => handleRevoke(invitation)}
                                                            className="text-destructive"
                                                        >
                                                            <Trash2 className="mr-2 h-4 w-4" />
                                                            Revocar
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}