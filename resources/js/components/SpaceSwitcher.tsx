import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Building2, ChevronDown, LogOut, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useHotkeys } from 'react-hotkeys-hook';

// Definir la estructura de un espacio
interface Space {
    id: string;
    name: string;
    domains: { domain: string }[];
}

interface SpaceSwitcherProps {
    currentSpace: Space;
    spaces: Space[];
}

// Componente principal
export default function SpaceSwitcher({ currentSpace, spaces }: SpaceSwitcherProps) {
    // Estado para la búsqueda y el popover
    const [searchQuery, setSearchQuery] = useState('');
    const [filteredSpaces, setFilteredSpaces] = useState(spaces);
    const [isOpen, setIsOpen] = useState(false);
    
    // Atajo de teclado Ctrl+K para abrir el selector
    useHotkeys('ctrl+k', (e) => {
        e.preventDefault();
        setIsOpen(true);
    });

    // Filtrar espacios cuando cambia la búsqueda
    useEffect(() => {
        if (searchQuery) {
            setFilteredSpaces(spaces.filter((space) => space.name.toLowerCase().includes(searchQuery.toLowerCase())));
        } else {
            setFilteredSpaces(spaces);
        }
    }, [searchQuery, spaces]);

    // Función para obtener la URL de un espacio
    const getSpaceUrl = (space: Space) => {
        return route('teleport', { space: space.id });
    };

    // Renderizar el componente
    return (
        <Popover open={isOpen} onOpenChange={setIsOpen}>
            {/* Botón que muestra el espacio actual */}
            <PopoverTrigger asChild>
                <Button variant="outline" className="flex items-center gap-2">
                    <Building2 className="h-4 w-4" />
                    <span className="max-w-[150px] truncate">{currentSpace.name}</span>
                    <ChevronDown className="h-4 w-4" />
                </Button>
            </PopoverTrigger>

            {/* Menú desplegable con la lista de espacios */}
            <PopoverContent className="w-80 p-0" align="start">
                {/* Buscador de espacios */}
                <div className="p-2">
                    <div className="relative">
                        <Search className="absolute top-1/2 left-2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            placeholder="Buscar espacio... (Ctrl+K)"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="pl-8"
                            autoFocus
                        />
                    </div>
                </div>

                {/* Lista de espacios */}
                <div className="max-h-[300px] overflow-y-auto">
                    {filteredSpaces.map((space) => (
                        <a
                            key={space.id}
                            href={getSpaceUrl(space)}
                            className={`flex items-center gap-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-800 ${
                                currentSpace.id === space.id ? 'bg-gray-100 dark:bg-gray-800' : ''
                            }`}
                        >
                            {/* Icono del espacio */}
                            <div className="bg-primary/10 text-primary flex h-8 w-8 items-center justify-center rounded-md">
                                {space.name.charAt(0).toUpperCase()}
                            </div>

                            {/* Nombre del espacio */}
                            <div className="flex-1 truncate">{space.name}</div>
                        </a>
                    ))}
                </div>

                {/* Enlaces de administración */}
                <div className="border-t p-2">
                    <div className="flex flex-col gap-1">
                        <Button asChild variant="ghost" className="w-full justify-start">
                            <a href={route('spaces.index')}>Administrar todos los espacios</a>
                        </Button>
                        <Button asChild variant="ghost" className="w-full justify-start text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20">
                            <a href={route('logout.all')} onClick={(e) => {
                                e.preventDefault();
                                const form = document.getElementById('logout-all-form') as HTMLFormElement;
                                form?.submit();
                            }}>
                                <LogOut className="h-4 w-4 mr-2" />
                                Cerrar sesión en todos los espacios
                            </a>
                        </Button>
                    </div>
                </div>
                
                {/* Formulario oculto para logout global */}
                <form id="logout-all-form" action={route('logout.all')} method="POST" className="hidden">
                    <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''} />
                </form>
            </PopoverContent>
        </Popover>
    );
}
