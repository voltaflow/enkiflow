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
        const projectId = value === 'none' ? null : parseInt(value);
        onProjectChange(projectId);
        // Reset task when project changes
        if (projectId !== selectedProjectId) {
            onTaskChange(null);
        }
    };

    const handleTaskChange = (value: string) => {
        const taskId = value === 'none' ? null : parseInt(value);
        onTaskChange(taskId);
    };

    return (
        <div className="space-y-4">
            {/* Project Selector */}
            <div className="space-y-2">
                <Label htmlFor="project-select">Proyecto</Label>
                <Select
                    value={selectedProjectId?.toString() || 'none'}
                    onValueChange={handleProjectChange}
                    disabled={disabled}
                >
                    <SelectTrigger id="project-select" className="w-full">
                        <SelectValue placeholder="Seleccionar proyecto" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">Sin proyecto</SelectItem>

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
                    </SelectContent>
                </Select>
            </div>

            {/* Task Selector */}
            {selectedProjectId && (
                <div className="space-y-2">
                    <Label htmlFor="task-select">Tarea (opcional)</Label>
                    <Select
                        value={selectedTaskId?.toString() || 'none'}
                        onValueChange={handleTaskChange}
                        disabled={disabled || availableTasks.length === 0}
                    >
                        <SelectTrigger id="task-select" className="w-full">
                            <SelectValue placeholder="Seleccionar tarea" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">Sin tarea específica</SelectItem>
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
                </div>
            )}
        </div>
    );
}