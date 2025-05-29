import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';

interface InviteUserProps {
    availableRoles: {
        value: string;
        label: string;
        description: string;
    }[];
    canManageRoles: boolean;
}

export default function Invite({ availableRoles, canManageRoles }: InviteUserProps) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        role: availableRoles.length > 0 ? availableRoles[0].value : '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('tenant.users.store'));
    };

    return (
        <AppLayout>
            <Head title="Invitar Usuario" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Invitar un nuevo usuario</CardTitle>
                            <CardDescription>Invita a un usuario a unirse a tu espacio. El usuario debe tener una cuenta existente.</CardDescription>
                        </CardHeader>
                        <form onSubmit={submit}>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email del usuario</Label>
                                    <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                                    {errors.email && <div className="text-sm text-red-500">{errors.email}</div>}
                                </div>

                                {canManageRoles && (
                                    <div className="space-y-2">
                                        <Label htmlFor="role">Rol del usuario</Label>
                                        <select
                                            id="role"
                                            className="w-full rounded border border-gray-300 p-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            value={data.role}
                                            onChange={(e) => setData('role', e.target.value)}
                                            required
                                        >
                                            {availableRoles.map((role) => (
                                                <option key={role.value} value={role.value}>
                                                    {role.label}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.role && <div className="text-sm text-red-500">{errors.role}</div>}

                                        {data.role && (
                                            <div className="mt-2 rounded bg-gray-100 p-3 dark:bg-gray-800">
                                                <h4 className="mb-1 font-medium">Permisos del rol</h4>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                    {availableRoles.find((r) => r.value === data.role)?.description}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                            <CardFooter className="flex justify-between">
                                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                    Cancelar
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Invitar Usuario
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
