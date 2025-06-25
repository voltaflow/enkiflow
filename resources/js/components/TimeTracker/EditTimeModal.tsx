import React, { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { format } from 'date-fns';

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

interface TimeEntry {
    id: number;
    description: string;
    started_at: string;
    stopped_at: string | null;
    ended_at?: string | null;
    duration: number;
    is_billable: boolean;
    task_id: number | null;
    project_id: number | null;
    task?: Task;
    project?: Project;
}

interface EditTimeModalProps {
    isOpen: boolean;
    onClose: () => void;
    projects: Project[];
    tasks: Task[];
    entry: TimeEntry | null;
    onSubmit: (data: {
        id: number;
        project_id: number | null;
        task_id: number | null;
        description: string;
        duration: string;
        started_at: string;
        ended_at: string;
    }) => Promise<void>;
}

export function EditTimeModal({
    isOpen,
    onClose,
    projects,
    tasks,
    entry,
    onSubmit
}: EditTimeModalProps) {
    const [selectedProjectId, setSelectedProjectId] = useState<number | null>(null);
    const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
    const [description, setDescription] = useState('');
    const [startTime, setStartTime] = useState('09:00');
    const [endTime, setEndTime] = useState('10:00');
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (entry) {
            setSelectedProjectId(entry.project_id);
            setSelectedTaskId(entry.task_id);
            setDescription(entry.description || '');
            
            // Parse times from the entry
            const startDate = new Date(entry.started_at);
            setStartTime(format(startDate, 'HH:mm'));
            
            const endDate = entry.ended_at || entry.stopped_at ? new Date(entry.ended_at || entry.stopped_at!) : new Date(startDate.getTime() + entry.duration * 1000);
            setEndTime(format(endDate, 'HH:mm'));
        }
    }, [entry]);

    const availableTasks = selectedProjectId
        ? tasks.filter(task => task.project_id === selectedProjectId)
        : [];

    const calculateDuration = () => {
        const [startHours, startMinutes] = startTime.split(':').map(Number);
        const [endHours, endMinutes] = endTime.split(':').map(Number);
        
        const startTotalMinutes = startHours * 60 + startMinutes;
        const endTotalMinutes = endHours * 60 + endMinutes;
        
        const durationMinutes = endTotalMinutes - startTotalMinutes;
        
        if (durationMinutes <= 0) return '00:00';
        
        const hours = Math.floor(durationMinutes / 60);
        const minutes = durationMinutes % 60;
        
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
    };

    const handleSubmit = async () => {
        if (!entry) return;
        
        // Validate required fields
        if (!selectedProjectId) {
            alert('Por favor selecciona un proyecto');
            return;
        }
        
        if (!selectedTaskId && availableTasks.length > 0) {
            alert('Por favor selecciona una tarea');
            return;
        }
        
        const duration = calculateDuration();
        if (duration === '00:00') {
            alert('La hora de fin debe ser posterior a la hora de inicio');
            return;
        }

        setIsSubmitting(true);
        try {
            const dateStr = format(new Date(entry.started_at), 'yyyy-MM-dd');
            await onSubmit({
                id: entry.id,
                project_id: selectedProjectId,
                task_id: selectedTaskId,
                description,
                duration,
                started_at: `${dateStr} ${startTime}:00`,
                ended_at: `${dateStr} ${endTime}:00`
            });
            onClose();
        } catch (error) {
            console.error('Error updating time entry:', error);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Editar entrada de tiempo</DialogTitle>
                </DialogHeader>
                
                <div className="space-y-4 py-4">
                    <div className="space-y-2">
                        <Label htmlFor="project">
                            Proyecto
                            <span className="text-red-500 ml-1">*</span>
                        </Label>
                        <Select
                            value={selectedProjectId?.toString() || ''}
                            onValueChange={(value) => {
                                setSelectedProjectId(Number(value));
                                setSelectedTaskId(null);
                            }}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona un proyecto" />
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
                                            {project.name}
                                        </div>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="task">
                            Tarea
                            {availableTasks.length > 0 && <span className="text-red-500 ml-1">*</span>}
                        </Label>
                        <Select
                            value={selectedTaskId?.toString() || ''}
                            onValueChange={(value) => setSelectedTaskId(Number(value))}
                            disabled={!selectedProjectId || availableTasks.length === 0}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={availableTasks.length === 0 ? "Sin tareas disponibles" : "Selecciona una tarea"} />
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

                    <div className="space-y-2">
                        <Label htmlFor="description">Descripción</Label>
                        <Textarea
                            id="description"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="¿En qué trabajaste?"
                            rows={3}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="start-time">Hora de inicio</Label>
                            <Input
                                id="start-time"
                                type="time"
                                value={startTime}
                                onChange={(e) => setStartTime(e.target.value)}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="end-time">Hora de fin</Label>
                            <Input
                                id="end-time"
                                type="time"
                                value={endTime}
                                onChange={(e) => setEndTime(e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="text-sm text-muted-foreground">
                        Duración: {calculateDuration()}
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={isSubmitting}>
                        Cancelar
                    </Button>
                    <Button onClick={handleSubmit} disabled={isSubmitting}>
                        {isSubmitting ? 'Guardando...' : 'Guardar cambios'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}