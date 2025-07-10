import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { CheckSquare, Search, Square } from 'lucide-react';
import { useState } from 'react';

interface Project {
    id: number;
    name: string;
    client?: string;
    status: 'active' | 'completed' | 'archived';
}

interface ProjectSelectorProps {
    projects: Project[];
    selectedProjects: number[];
    onSelectionChange: (selected: number[]) => void;
}

export default function ProjectSelector({ projects, selectedProjects, onSelectionChange }: ProjectSelectorProps) {
    const [searchTerm, setSearchTerm] = useState('');

    const filteredProjects = projects.filter(
        (project) =>
            project.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (project.client && project.client.toLowerCase().includes(searchTerm.toLowerCase())),
    );

    const toggleProject = (projectId: number) => {
        if (selectedProjects.includes(projectId)) {
            onSelectionChange(selectedProjects.filter((id) => id !== projectId));
        } else {
            onSelectionChange([...selectedProjects, projectId]);
        }
    };

    const toggleAll = () => {
        if (selectedProjects.length === filteredProjects.length) {
            onSelectionChange([]);
        } else {
            onSelectionChange(filteredProjects.map((p) => p.id));
        }
    };

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'active':
                return 'default';
            case 'completed':
                return 'secondary';
            case 'archived':
                return 'outline';
            default:
                return 'default';
        }
    };

    return (
        <div className="space-y-4">
            <div className="relative">
                <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                <Input placeholder="Buscar proyectos..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} className="pl-9" />
            </div>

            <div className="flex items-center justify-between border-b pb-2">
                <Label className="text-sm font-medium">
                    {filteredProjects.length} proyecto{filteredProjects.length !== 1 ? 's' : ''} disponible{filteredProjects.length !== 1 ? 's' : ''}
                </Label>
                <button onClick={toggleAll} className="text-primary flex items-center gap-2 text-sm hover:underline">
                    {selectedProjects.length === filteredProjects.length ? (
                        <>
                            <CheckSquare className="h-4 w-4" />
                            Deseleccionar todos
                        </>
                    ) : (
                        <>
                            <Square className="h-4 w-4" />
                            Seleccionar todos
                        </>
                    )}
                </button>
            </div>

            <ScrollArea className="h-[300px] w-full rounded-md border">
                <div className="space-y-2 p-4">
                    {filteredProjects.map((project) => (
                        <div
                            key={project.id}
                            className={cn(
                                'flex items-center space-x-3 rounded-lg p-3 transition-colors',
                                'hover:bg-muted/50 cursor-pointer',
                                selectedProjects.includes(project.id) && 'bg-muted/30',
                            )}
                            onClick={() => toggleProject(project.id)}
                        >
                            <Checkbox
                                checked={selectedProjects.includes(project.id)}
                                onCheckedChange={() => toggleProject(project.id)}
                                onClick={(e) => e.stopPropagation()}
                            />
                            <div className="flex-1 space-y-1">
                                <div className="flex items-center gap-2">
                                    <p className="font-medium">{project.name}</p>
                                    <Badge variant={getStatusBadgeVariant(project.status)}>{project.status}</Badge>
                                </div>
                                {project.client && <p className="text-muted-foreground text-sm">Cliente: {project.client}</p>}
                            </div>
                        </div>
                    ))}
                    {filteredProjects.length === 0 && <p className="text-muted-foreground py-8 text-center">No se encontraron proyectos</p>}
                </div>
            </ScrollArea>
        </div>
    );
}
