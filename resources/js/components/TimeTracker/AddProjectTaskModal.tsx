import React, { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-react';

interface Project {
    id: number;
    name: string;
    color?: string;
}

interface Task {
    id: number;
    title: string;
    project_id: number;
}

interface AddProjectTaskModalProps {
    isOpen: boolean;
    onClose: () => void;
    projects: Project[];
    tasks: Task[];
    existingProjectTaskCombinations: Set<string>;
    onSubmit: (data: {
        project_id: number;
        task_id: number | null;
        description: string;
    }) => Promise<void>;
}

export function AddProjectTaskModal({
    isOpen,
    onClose,
    projects,
    tasks,
    existingProjectTaskCombinations,
    onSubmit
}: AddProjectTaskModalProps) {
    const [selectedProjectId, setSelectedProjectId] = useState<number | null>(null);
    const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [validationError, setValidationError] = useState<string | null>(null);

    const availableProjects = projects.filter(project => {
        // Show all projects initially
        if (!selectedProjectId) return true;
        return project.id === selectedProjectId;
    });

    const availableTasks = selectedProjectId
        ? tasks.filter(task => task.project_id === selectedProjectId)
        : [];

    const handleSubmit = async () => {
        // Clear previous errors
        setValidationError(null);
        
        // Validate required fields
        if (!selectedProjectId) {
            setValidationError('Por favor selecciona un proyecto');
            return;
        }
        
        if (!selectedTaskId && availableTasks.length > 0) {
            setValidationError('Por favor selecciona una tarea');
            return;
        }
        
        // Check if this combination already exists
        const combinationKey = `${selectedProjectId}-${selectedTaskId || 0}`;
        if (existingProjectTaskCombinations.has(combinationKey)) {
            setValidationError('Esta combinación de proyecto/tarea ya existe en la semana');
            return;
        }

        setIsSubmitting(true);
        try {
            await onSubmit({
                project_id: selectedProjectId,
                task_id: selectedTaskId,
                description: ''
            });
            
            // Reset form
            setSelectedProjectId(null);
            setSelectedTaskId(null);
            setValidationError(null);
            onClose();
        } catch (error) {
            console.error('Error adding project/task:', error);
            setValidationError('Error al agregar proyecto/tarea. Por favor intenta de nuevo.');
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleClose = () => {
        if (!isSubmitting) {
            // Reset form
            setSelectedProjectId(null);
            setSelectedTaskId(null);
            setValidationError(null);
            onClose();
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>
                        Agregar proyecto/tarea a la semana
                    </DialogTitle>
                </DialogHeader>
                
                <div className="space-y-4 py-4">
                    {/* Validation Error Alert */}
                    {validationError && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>{validationError}</AlertDescription>
                        </Alert>
                    )}
                    
                    {/* Project Selector */}
                    <div className="space-y-2">
                        <Label htmlFor="project">Proyecto <span className="text-red-500">*</span></Label>
                        <Select
                            value={selectedProjectId?.toString() || ''}
                            onValueChange={(value) => {
                                setSelectedProjectId(parseInt(value));
                                setSelectedTaskId(null);
                            }}
                        >
                            <SelectTrigger id="project">
                                <SelectValue placeholder="Seleccionar proyecto" />
                            </SelectTrigger>
                            <SelectContent>
                                {projects.map(project => (
                                    <SelectItem key={project.id} value={project.id.toString()}>
                                        <div className="flex items-center gap-2">
                                            {project.color && (
                                                <div
                                                    className="w-3 h-3 rounded-full"
                                                    style={{ backgroundColor: project.color }}
                                                />
                                            )}
                                            <span>{project.name}</span>
                                        </div>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Task Selector */}
                    {selectedProjectId && (
                        <div className="space-y-2">
                            <Label htmlFor="task">
                                Tarea 
                                {availableTasks.length > 0 && <span className="text-red-500"> *</span>}
                            </Label>
                            <Select
                                value={selectedTaskId?.toString() || ''}
                                onValueChange={(value) => setSelectedTaskId(parseInt(value))}
                                disabled={availableTasks.length === 0}
                            >
                                <SelectTrigger id="task">
                                    <SelectValue placeholder={
                                        availableTasks.length === 0 
                                            ? "No hay tareas disponibles" 
                                            : "Seleccionar tarea"
                                    } />
                                </SelectTrigger>
                                <SelectContent>
                                    {availableTasks.map(task => (
                                        <SelectItem key={task.id} value={task.id.toString()}>
                                            {task.title}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    {/* Removed description field as requested - only project and task needed */}
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={handleClose}
                        disabled={isSubmitting}
                    >
                        Cancelar
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        disabled={isSubmitting || !selectedProjectId || (availableTasks.length > 0 && !selectedTaskId)}
                    >
                        {isSubmitting ? 'Agregando...' : 'Agregar'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}