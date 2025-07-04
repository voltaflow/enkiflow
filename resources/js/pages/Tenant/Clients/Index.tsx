import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Building2, Globe, Mail, MapPin, MoreHorizontal, Phone, Plus, Search, User } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Client {
    id: number;
    name: string;
    slug: string;
    email?: string;
    phone?: string;
    website?: string;
    city?: string;
    state?: string;
    country?: string;
    contact_name?: string;
    contact_email?: string;
    is_active: boolean;
    projects_count?: number;
    created_at: string;
    deleted_at?: string;
}

interface Props {
    clients: {
        data: Client[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters: {
        term?: string;
        status?: string;
        include_archived?: boolean;
    };
}

export default function Index({ clients, filters }: Props) {
    const [search, setSearch] = useState(filters.term || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [includeArchived, setIncludeArchived] = useState(filters.include_archived || false);

    // Búsqueda automática con debounce
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== filters.term) {
                router.get(
                    route('tenant.clients.index'),
                    {
                        search,
                        status: status !== 'all' ? status : undefined,
                        archived: includeArchived || undefined,
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                    },
                );
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [search, status, includeArchived, filters]);

    const handleStatusChange = (value: string) => {
        setStatus(value);
        router.get(
            route('tenant.clients.index'),
            {
                search: search || undefined,
                status: value !== 'all' ? value : undefined,
                archived: includeArchived || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const toggleClientStatus = (client: Client) => {
        router.post(
            route('tenant.clients.toggle-status', client.id),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const archiveClient = (client: Client) => {
        if (confirm(`¿Estás seguro de que deseas archivar a ${client.name}?`)) {
            router.delete(route('tenant.clients.destroy', client.id), {
                preserveScroll: true,
            });
        }
    };

    const restoreClient = (client: Client) => {
        router.post(
            route('tenant.clients.restore', client.id),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <AppLayout>
            <Head title="Clientes" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Clientes</h1>
                            <p className="text-muted-foreground mt-1">Gestiona tus clientes y su información de contacto</p>
                        </div>
                        <Link href={route('tenant.clients.create')}>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Cliente
                            </Button>
                        </Link>
                    </div>

                    <Card>
                        <CardHeader className="pb-3">
                            <div className="flex flex-col gap-4 sm:flex-row">
                                <div className="relative flex-1">
                                    <Search className="text-muted-foreground absolute top-2.5 left-2 h-4 w-4" />
                                    <Input
                                        placeholder="Buscar por nombre, email o teléfono..."
                                        className="pl-8"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                    />
                                </div>
                                <Select value={status} onValueChange={handleStatusChange}>
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Filtrar por estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="active">Activos</SelectItem>
                                        <SelectItem value="inactive">Inactivos</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {clients.data.length === 0 ? (
                                <div className="py-12 text-center">
                                    <Building2 className="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                                    <h3 className="text-lg font-medium">No se encontraron clientes</h3>
                                    <p className="text-muted-foreground mt-2">
                                        {search || status !== 'all'
                                            ? 'Intenta cambiar los filtros de búsqueda'
                                            : 'Comienza creando tu primer cliente'}
                                    </p>
                                    {!search && status === 'all' && (
                                        <Link href={route('tenant.clients.create')}>
                                            <Button className="mt-4">
                                                <Plus className="mr-2 h-4 w-4" />
                                                Crear Cliente
                                            </Button>
                                        </Link>
                                    )}
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    {clients.data.map((client) => (
                                        <div
                                            key={client.id}
                                            className={`hover:bg-accent/50 rounded-lg border p-4 transition-colors ${
                                                client.deleted_at ? 'opacity-60' : ''
                                            }`}
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <div className="mb-2 flex items-center gap-3">
                                                        <Link
                                                            href={route('tenant.clients.show', client.id)}
                                                            className="text-lg font-semibold hover:underline"
                                                        >
                                                            {client.name}
                                                        </Link>
                                                        {!client.is_active && <Badge variant="secondary">Inactivo</Badge>}
                                                        {client.deleted_at && <Badge variant="destructive">Archivado</Badge>}
                                                    </div>

                                                    <div className="text-muted-foreground grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                                        {client.contact_name && (
                                                            <div className="flex items-center gap-1">
                                                                <User className="h-3 w-3" />
                                                                <span>{client.contact_name}</span>
                                                            </div>
                                                        )}
                                                        {client.email && (
                                                            <div className="flex items-center gap-1">
                                                                <Mail className="h-3 w-3" />
                                                                <a href={`mailto:${client.email}`} className="hover:underline">
                                                                    {client.email}
                                                                </a>
                                                            </div>
                                                        )}
                                                        {client.phone && (
                                                            <div className="flex items-center gap-1">
                                                                <Phone className="h-3 w-3" />
                                                                <a href={`tel:${client.phone}`} className="hover:underline">
                                                                    {client.phone}
                                                                </a>
                                                            </div>
                                                        )}
                                                        {client.website && (
                                                            <div className="flex items-center gap-1">
                                                                <Globe className="h-3 w-3" />
                                                                <a
                                                                    href={client.website}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    className="hover:underline"
                                                                >
                                                                    Sitio web
                                                                </a>
                                                            </div>
                                                        )}
                                                        {(client.city || client.country) && (
                                                            <div className="flex items-center gap-1">
                                                                <MapPin className="h-3 w-3" />
                                                                <span>{[client.city, client.state, client.country].filter(Boolean).join(', ')}</span>
                                                            </div>
                                                        )}
                                                    </div>

                                                    {client.projects_count !== undefined && client.projects_count > 0 && (
                                                        <div className="text-muted-foreground mt-2 text-sm">
                                                            {client.projects_count} {client.projects_count === 1 ? 'proyecto' : 'proyectos'}
                                                        </div>
                                                    )}
                                                </div>

                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem asChild>
                                                            <Link href={route('tenant.clients.show', client.id)}>Ver detalles</Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem asChild>
                                                            <Link href={route('tenant.clients.edit', client.id)}>Editar</Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        {!client.deleted_at && (
                                                            <>
                                                                <DropdownMenuItem onClick={() => toggleClientStatus(client)}>
                                                                    {client.is_active ? 'Desactivar' : 'Activar'}
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem onClick={() => archiveClient(client)} className="text-destructive">
                                                                    Archivar
                                                                </DropdownMenuItem>
                                                            </>
                                                        )}
                                                        {client.deleted_at && (
                                                            <DropdownMenuItem onClick={() => restoreClient(client)}>Restaurar</DropdownMenuItem>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {clients.last_page > 1 && (
                                <div className="mt-6 flex justify-center">
                                    <div className="flex gap-2">
                                        {clients.current_page > 1 && (
                                            <Button variant="outline" size="sm" onClick={() => router.get(clients.prev_page_url!)}>
                                                Anterior
                                            </Button>
                                        )}
                                        <span className="text-muted-foreground flex items-center px-3 text-sm">
                                            Página {clients.current_page} de {clients.last_page}
                                        </span>
                                        {clients.current_page < clients.last_page && (
                                            <Button variant="outline" size="sm" onClick={() => router.get(clients.next_page_url!)}>
                                                Siguiente
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
