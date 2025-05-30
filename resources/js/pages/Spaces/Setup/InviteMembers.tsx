import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';

interface InviteMembersProps {
    name: string;
    subdomain: string;
    plan: string;
}

interface InviteItem {
    email: string;
    role: string;
}

export default function InviteMembers({ name, subdomain, plan }: InviteMembersProps) {
    const [loading, setLoading] = useState(false);
    const [invites, setInvites] = useState<InviteItem[]>([{ email: '', role: 'member' }]);
<<<<<<< HEAD
    const [errors, setErrors] = useState<Record<string, string>>({});

    const getError = (field: string): string | undefined => {
        return errors[field];
    };

    const addInvite = () => {
        setInvites([...invites, { email: '', role: 'member' }]);
    };

    const removeInvite = (index: number) => {
        const newInvites = [...invites];
        newInvites.splice(index, 1);
        setInvites(newInvites);
    };

    const updateInvite = (index: number, field: keyof InviteItem, value: string) => {
        const newInvites = [...invites];
        newInvites[index][field] = value;
        setInvites(newInvites);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        // Filter out empty invites and convert to plain objects
        const filteredInvites = invites.filter((invite) => invite.email.trim() !== '').map((invite) => ({ email: invite.email, role: invite.role }));

        setLoading(true);
        router.post(
            route('spaces.setup.confirm'),
            {
                invites: filteredInvites as any,
            },
            {
                onSuccess: () => {
                    setLoading(false);
                },
                onError: () => {
                    setLoading(false);
                },
            },
        );
    };

=======
    // Removed unused errors state - error handling might be done through Inertia

    const addInvite = () => {
        setInvites([...invites, { email: '', role: 'member' }]);
    };

    const removeInvite = (index: number) => {
        const newInvites = [...invites];
        newInvites.splice(index, 1);
        setInvites(newInvites);
    };

    const updateInvite = (index: number, field: keyof InviteItem, value: string) => {
        const newInvites = [...invites];
        newInvites[index][field] = value;
        setInvites(newInvites);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        // Filter out empty invites and convert to plain objects
        const filteredInvites = invites.filter((invite) => invite.email.trim() !== '').map((invite) => ({ email: invite.email, role: invite.role }));

        setLoading(true);
        router.post(
            route('spaces.setup.confirm'),
            {
                invites: filteredInvites,
            },
            {
                onSuccess: () => {
                    setLoading(false);
                },
                onError: () => {
                    setLoading(false);
                },
            },
        );
    };

>>>>>>> 83fa645f796570e19e5e8ef94c03f015ebf4a8b6
    return (
        <AppLayout>
            <Head title="Invitar Miembros" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="mb-8 text-center">
                        <h1 className="mb-2 text-3xl font-bold text-gray-900 dark:text-white">Invita a tu equipo</h1>
                        <p className="text-xl text-gray-600 dark:text-gray-400">Agrega miembros a tu nuevo espacio: {name}</p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Invitar Miembros</CardTitle>
                            <CardDescription>
                                Invita a tu equipo a unirse a tu espacio. Puedes omitir este paso si prefieres invitarlos más tarde.
                            </CardDescription>
                        </CardHeader>
                        <form onSubmit={submit}>
                            <CardContent className="space-y-6">
                                <div className="bg-muted mb-6 rounded-md p-4">
                                    <div className="font-medium">Resumen del Espacio</div>
                                    <div className="mt-2 grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <span className="text-muted-foreground">Nombre:</span> {name}
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Subdominio:</span> {subdomain}.example.com
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Plan:</span> {plan.charAt(0).toUpperCase() + plan.slice(1)}
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    {invites.map((invite, index) => (
                                        <div key={index} className="flex items-start gap-2">
                                            <div className="flex-1">
                                                <Label htmlFor={`email-${index}`} className="sr-only">
                                                    Email
                                                </Label>
                                                <Input
                                                    id={`email-${index}`}
                                                    type="email"
                                                    value={invite.email}
                                                    onChange={(e) => updateInvite(index, 'email', e.target.value)}
                                                    placeholder="email@example.com"
                                                />
<<<<<<< HEAD
                                                {getError(`invites.${index}.email`) && (
                                                    <div className="text-sm text-red-500">{getError(`invites.${index}.email`)}</div>
                                                )}
=======
>>>>>>> 83fa645f796570e19e5e8ef94c03f015ebf4a8b6
                                            </div>
                                            <div className="w-32">
                                                <Label htmlFor={`role-${index}`} className="sr-only">
                                                    Rol
                                                </Label>
                                                <select
                                                    id={`role-${index}`}
                                                    value={invite.role}
                                                    onChange={(e) => updateInvite(index, 'role', e.target.value)}
                                                    className="border-input bg-background h-10 w-full rounded-md border px-3"
                                                >
                                                    <option value="admin">Admin</option>
                                                    <option value="manager">Gerente</option>
                                                    <option value="member">Miembro</option>
                                                    <option value="guest">Invitado</option>
                                                </select>
<<<<<<< HEAD
                                                {getError(`invites.${index}.role`) && (
                                                    <div className="text-sm text-red-500">{getError(`invites.${index}.role`)}</div>
                                                )}
=======
>>>>>>> 83fa645f796570e19e5e8ef94c03f015ebf4a8b6
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="shrink-0"
                                                onClick={() => removeInvite(index)}
                                                disabled={invites.length === 1 && invite.email === ''}
                                            >
                                                <Trash2 className="h-5 w-5" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>

                                <Button type="button" variant="outline" className="w-full" onClick={addInvite}>
                                    Agregar Otro Invitado
                                </Button>
                            </CardContent>
                            <CardFooter className="flex justify-between">
                                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                    Volver
                                </Button>
                                <Button type="submit" disabled={loading}>
                                    {loading ? 'Procesando...' : 'Continuar'}
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
