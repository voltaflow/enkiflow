import { GlobalRoleManager } from '@/components/permissions/GlobalRoleManager';
import { PermissionAuditView } from '@/components/permissions/PermissionAuditView';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useFeature } from '@/composables/useFeature';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { AlertCircle, Key, Search, Shield, Users } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface SpaceUser {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

interface PageProps {
    users: SpaceUser[];
    space: {
        id: string;
        name: string;
    };
    currentUserId: number;
    currentUserRole: string;
    currentUserPermissions: string[];
}

export default function Permissions({ users, space, currentUserId, currentUserRole, currentUserPermissions }: PageProps) {
    const hasFeature = useFeature('project_permissions');
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedUser, setSelectedUser] = useState<SpaceUser | null>(null);
    const [activeTab, setActiveTab] = useState('users');

    const filteredUsers = users.filter(
        (user) => user.name.toLowerCase().includes(searchQuery.toLowerCase()) || user.email.toLowerCase().includes(searchQuery.toLowerCase()),
    );

    const handleRoleChange = async (userId: number, newRole: string) => {
        try {
            await axios.put(`/api/spaces/${space.id}/users/${userId}/role`, {
                role: newRole,
            });
            toast.success('Role updated successfully');
            // Reload page to refresh data
            window.location.reload();
        } catch (error) {
            toast.error('Failed to update role');
        }
    };

    if (!hasFeature) {
        return (
            <AppLayout>
                <Head title="Permissions" />
                <div className="container mx-auto py-8">
                    <Alert>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>El sistema avanzado de permisos no está habilitado para este espacio.</AlertDescription>
                    </Alert>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Permissions" />

            <div className="container mx-auto space-y-8 py-8">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Gestión de Permisos</h1>
                    <p className="text-muted-foreground mt-2">Administra roles y permisos de usuarios en {space.name}</p>
                </div>

                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList>
                        <TabsTrigger value="users">
                            <Users className="mr-2 h-4 w-4" />
                            Usuarios y Roles
                        </TabsTrigger>
                        <TabsTrigger value="audit">
                            <Key className="mr-2 h-4 w-4" />
                            Mi Auditoría
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="users" className="space-y-6">
                        {/* Search */}
                        <div className="flex items-center gap-4">
                            <div className="relative max-w-md flex-1">
                                <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                                <Input
                                    placeholder="Buscar usuarios..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                        </div>

                        {/* Users Table */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Usuarios del Espacio</CardTitle>
                                <CardDescription>{users.length} usuarios con acceso a este espacio</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Usuario</TableHead>
                                            <TableHead>Rol Global</TableHead>
                                            <TableHead>Miembro Desde</TableHead>
                                            <TableHead>Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {filteredUsers.map((user) => (
                                            <TableRow key={user.id}>
                                                <TableCell>
                                                    <div className="flex items-center gap-3">
                                                        <Avatar>
                                                            <AvatarFallback>
                                                                {user.name
                                                                    .split(' ')
                                                                    .map((n) => n[0])
                                                                    .join('')
                                                                    .toUpperCase()}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div>
                                                            <p className="font-medium">{user.name}</p>
                                                            <p className="text-muted-foreground text-sm">{user.email}</p>
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" className="gap-1">
                                                        <Shield className="h-3 w-3" />
                                                        {user.role}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {new Date(user.created_at).toLocaleDateString('es-ES')}
                                                </TableCell>
                                                <TableCell>
                                                    <Button size="sm" variant="outline" onClick={() => setSelectedUser(user)}>
                                                        Ver Permisos
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        {/* Selected User Permissions */}
                        {selectedUser && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Permisos de {selectedUser.name}</CardTitle>
                                    <CardDescription>Gestiona el rol global y permisos en el espacio</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <GlobalRoleManager
                                        userId={selectedUser.id}
                                        userName={selectedUser.name}
                                        currentRole={selectedUser.role}
                                        spaceId={space.id}
                                        onRoleChange={(newRole) => handleRoleChange(selectedUser.id, newRole)}
                                        readOnly={selectedUser.id === currentUserId}
                                    />
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>

                    <TabsContent value="audit" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Mi Auditoría de Permisos</CardTitle>
                                <CardDescription>Vista detallada de todos tus permisos en este espacio</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <PermissionAuditView 
                                    userId={currentUserId} 
                                    showHeader={false}
                                    userSpaceRole={currentUserRole}
                                    userSpacePermissions={currentUserPermissions}
                                />
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
