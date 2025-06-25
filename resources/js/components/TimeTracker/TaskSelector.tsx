import React, { useState, useMemo } from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Star } from 'lucide-react';

interface Project {
    id: number;
    name: string;
    client_id?: number;
    client_name?: string;
    color?: string;
}

interface Task {
    id: number;
    title: string;
    project_id: number;
}

interface Favorite {
    projectId: number;
    taskId?: number;
}

interface TaskSelectorProps {
    projects: Project[];
    tasks: Task[];
    selectedProjectId: number | null;
    selectedTaskId: number | null;
    disabled: boolean;
    favorites?: Favorite[];
    onProjectChange: (projectId: number | null) => void;
    onTaskChange: (taskId: number | null) => void;
}

export function TaskSelector({
    projects,
    tasks,
    selectedProjectId,
    selectedTaskId,
    disabled,
    favorites = [],
    onProjectChange,
    onTaskChange
}: TaskSelectorProps) {
    const [searchQuery, setSearchQuery] = useState('');
    
    // Use controlled state from parent
    const projectValue = selectedProjectId ? selectedProjectId.toString() : '';
    
    // Auto-select first project if none selected and projects are available
    React.useEffect(() => {
        if (!selectedProjectId && projects.length > 0) {
            onProjectChange(projects[0].id);
        }
    }, [projects, selectedProjectId]);
    
    // Removed debug logs

    // Filter tasks based on selected project
    const availableTasks = useMemo(() => {
        if (!selectedProjectId) return [];
        return tasks.filter(task => task.project_id === selectedProjectId);
    }, [tasks, selectedProjectId]);

    // Filter projects based on search
    const filteredProjects = useMemo(() => {
        if (!searchQuery) return projects;
        const query = searchQuery.toLowerCase();
        return projects.filter(project =>
            project.name.toLowerCase().includes(query) ||
            project.client_name?.toLowerCase().includes(query)
        );
    }, [projects, searchQuery]);

    // Check if a project/task combo is a favorite
    const isFavorite = (projectId: number, taskId?: number) => {
        return favorites.some(fav =>
            fav.projectId === projectId &&
            fav.taskId === taskId
        );
    };

    const handleProjectChange = (value: string) => {
        if (value && value !== '') {
            const projectId = parseInt(value);
            onProjectChange(projectId);
            // Reset task when project changes
            if (projectId !== selectedProjectId) {
                onTaskChange(null);
            }
        }
    };

    const handleTaskChange = (value: string) => {
        const taskId = value === '' ? null : parseInt(value);
        onTaskChange(taskId);
    };

    return (
        <div className="space-y-4">
            {/* Project Selector */}
            <div className="space-y-2 min-h-[68px]">
                <Label htmlFor="project-select">Proyecto <span className="text-red-500">*</span></Label>
                <Select
                    value={projectValue}
                    onValueChange={handleProjectChange}
                    disabled={disabled || projects.length === 0}
                >
                    <SelectTrigger id="project-select" className="w-full h-10">
                        <SelectValue placeholder={projects.length === 0 ? "No hay proyectos disponibles" : "Seleccionar proyecto"} />
                    </SelectTrigger>
                    <SelectContent className="max-h-[300px] overflow-y-auto">
                        {projects.length === 0 ? (
                            <div className="px-2 py-4 text-sm text-muted-foreground text-center">
                                No hay proyectos activos disponibles
                            </div>
                        ) : (
                            <>
                                {/* Favorites Section */}
                                {favorites.length > 0 && (
                                    <div className="px-2 py-1.5 text-xs font-semibold text-muted-foreground">
                                        Favoritos
                                    </div>
                                )}
                                {favorites.map(fav => {
                                    const project = projects.find(p => p.id === fav.projectId);
                                    if (!project) return null;
                                    return (
                                        <SelectItem
                                            key={`fav-${fav.projectId}`}
                                            value={project.id.toString()}
                                        >
                                            <div className="flex items-center gap-2">
                                                <Star className="h-3 w-3 text-yellow-500 fill-yellow-500" />
                                                {project.color && (
                                                    <div
                                                        className="w-3 h-3 rounded-full"
                                                        style={{ backgroundColor: project.color }}
                                                    />
                                                )}
                                                <span>{project.name}</span>
                                                {project.client_name && (
                                                    <span className="text-muted-foreground text-sm">
                                                        ({project.client_name})
                                                    </span>
                                                )}
                                            </div>
                                        </SelectItem>
                                    );
                                })}

                                {/* All Projects Section */}
                                <div className="px-2 py-1.5 text-xs font-semibold text-muted-foreground">
                                    Todos los proyectos
                                </div>
                                {filteredProjects.map(project => (
                                    <SelectItem
                                        key={project.id}
                                        value={project.id.toString()}
                                    >
                                        <div className="flex items-center gap-2">
                                            {project.color && (
                                                <div
                                                    className="w-3 h-3 rounded-full"
                                                    style={{ backgroundColor: project.color }}
                                                />
                                            )}
                                            <span>{project.name}</span>
                                            {project.client_name && (
                                                <span className="text-muted-foreground text-sm">
                                                    ({project.client_name})
                                                </span>
                                            )}
                                        </div>
                                    </SelectItem>
                                ))}
                            </>
                        )}
                    </SelectContent>
                </Select>
            </div>

            {/* Task Selector - Always rendered to maintain layout stability */}
            <div className={`space-y-2 min-h-[68px] transition-opacity duration-200 ${selectedProjectId ? 'opacity-100' : 'opacity-0 pointer-events-none'}`}>
                <Label htmlFor="task-select">Tarea {selectedProjectId && availableTasks.length > 0 && <span className="text-red-500">*</span>}</Label>
                {!selectedProjectId ? (
                    <div className="h-10" /> 
                ) : availableTasks.length === 0 ? (
                    <p className="text-sm text-muted-foreground italic h-10 flex items-center">
                        No hay tareas disponibles para este proyecto
                    </p>
                ) : (
                    <Select
                        value={selectedTaskId ? selectedTaskId.toString() : ''}
                        onValueChange={handleTaskChange}
                        disabled={disabled}
                    >
                        <SelectTrigger id="task-select" className="w-full h-10">
                            <SelectValue placeholder="Seleccionar tarea" />
                        </SelectTrigger>
                        <SelectContent className="max-h-[300px] overflow-y-auto">
                                {availableTasks.map(task => (
                                <SelectItem
                                    key={task.id}
                                    value={task.id.toString()}
                                >
                                    <div className="flex items-center gap-2">
                                        {isFavorite(selectedProjectId, task.id) && (
                                            <Star className="h-3 w-3 text-yellow-500 fill-yellow-500" />
                                        )}
                                        <span>{task.title}</span>
                                    </div>
                                </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                </div>
        </div>
    );
}