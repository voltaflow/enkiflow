import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Building2, Plus, Users } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Espacios',
        href: route('spaces.index'),
    },
];

interface SpaceUser {
    id: number;
    name: string;
    email: string;
    pivot: {
        role: string;
    };
}

interface Domain {
    id: number;
    domain: string;
}

interface Space {
    id: string;
    name: string;
    owner_id: number;
    plan: string | null;
    users: SpaceUser[];
    domains: Domain[];
    owner: {
        id: number;
        name: string;
        email: string;
    };
}

interface IndexProps {
    owned_spaces: Space[];
    member_spaces: Space[];
}

export default function Index({ owned_spaces, member_spaces }: IndexProps) {
    // Function to get URL for a space
    const getSpaceUrl = (space: Space) => {
        const domain = space.domains && space.domains.length > 0 
            ? space.domains[0].domain 
            : null;
            
        if (domain) {
            return `http://${domain}`;
        }
        
        return route('spaces.show', space.id);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Espacios" />
            
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-semibold">Mis Espacios</h1>
                    <Button asChild>
                        <Link href={route('spaces.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo Espacio
                        </Link>
                    </Button>
                </div>

                {/* Owned Spaces */}
                <div>
                    <h2 className="text-lg font-medium mb-4">Espacios que administro</h2>
                    
                    {owned_spaces.length === 0 ? (
                        <div className="text-center p-8 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <Building2 className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-base font-semibold text-gray-900 dark:text-white">
                                No has creado ningún espacio aún
                            </h3>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Comienza creando tu primer espacio de trabajo.
                            </p>
                            <div className="mt-6">
                                <Button asChild>
                                    <Link href={route('spaces.create')}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Crear un espacio
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {owned_spaces.map((space) => (
                                <Card key={space.id} className="overflow-hidden">
                                    <CardHeader className="pb-2">
                                        <div className="flex justify-between items-start">
                                            <CardTitle className="text-lg truncate">{space.name}</CardTitle>
                                            <Badge className="bg-blue-500">Propietario</Badge>
                                        </div>
                                        <CardDescription>
                                            Plan: {space.plan === 'free' ? 'Gratuito' : 'Premium'}
                                        </CardDescription>
                                    </CardHeader>
                                    
                                    <CardContent className="pb-2">
                                        <div className="flex items-center text-sm text-muted-foreground mb-2">
                                            <Users className="mr-2 h-4 w-4" />
                                            <span>{space.users.length} miembros</span>
                                        </div>
                                        
                                        {space.domains && space.domains.length > 0 && (
                                            <div className="text-sm mb-1">
                                                <span className="font-medium">Dominio: </span>
                                                <a 
                                                    href={`http://${space.domains[0].domain}`} 
                                                    target="_blank" 
                                                    rel="noopener noreferrer"
                                                    className="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    {space.domains[0].domain}
                                                </a>
                                            </div>
                                        )}
                                    </CardContent>
                                    
                                    <CardFooter className="grid grid-cols-2 gap-2 pt-4">
                                        <Button 
                                            variant="default" 
                                            className="w-full" 
                                            asChild
                                        >
                                            <a href={getSpaceUrl(space)}>
                                                Acceder
                                            </a>
                                        </Button>
                                        <Button 
                                            variant="outline" 
                                            className="w-full" 
                                            asChild
                                        >
                                            <Link href={route('spaces.show', space.id)}>
                                                Administrar
                                            </Link>
                                        </Button>
                                    </CardFooter>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>

                {/* Member Spaces */}
                {member_spaces.length > 0 && (
                    <div>
                        <h2 className="text-lg font-medium mb-4">Espacios donde soy miembro</h2>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {member_spaces.map((space) => (
                                <Card key={space.id} className="overflow-hidden">
                                    <CardHeader className="pb-2">
                                        <div className="flex justify-between items-start">
                                            <CardTitle className="text-lg truncate">{space.name}</CardTitle>
                                            <Badge className="bg-gray-500">Miembro</Badge>
                                        </div>
                                        <CardDescription>
                                            Propietario: {space.owner.name}
                                        </CardDescription>
                                    </CardHeader>
                                    
                                    <CardContent className="pb-2">
                                        {space.domains && space.domains.length > 0 && (
                                            <div className="text-sm mb-1">
                                                <span className="font-medium">Dominio: </span>
                                                <a 
                                                    href={`http://${space.domains[0].domain}`} 
                                                    target="_blank" 
                                                    rel="noopener noreferrer"
                                                    className="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    {space.domains[0].domain}
                                                </a>
                                            </div>
                                        )}
                                    </CardContent>
                                    
                                    <CardFooter>
                                        <Button 
                                            variant="default" 
                                            className="w-full" 
                                            asChild
                                        >
                                            <a href={getSpaceUrl(space)}>
                                                Acceder
                                            </a>
                                        </Button>
                                    </CardFooter>
                                </Card>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}