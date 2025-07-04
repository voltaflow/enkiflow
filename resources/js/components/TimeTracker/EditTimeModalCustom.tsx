import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { AlertCircle } from 'lucide-react';
import React, { useEffect, useState } from 'react';

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

interface EditTimeModalCustomProps {
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

export function EditTimeModalCustom({ isOpen, onClose, projects, tasks, entry, onSubmit }: EditTimeModalCustomProps) {
    const [selectedProjectId, setSelectedProjectId] = useState<number | null>(null);
    const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
    const [description, setDescription] = useState('');
    const [startTime, setStartTime] = useState('09:00');
    const [endTime, setEndTime] = useState('10:00');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [validationError, setValidationError] = useState<string | null>(null);

    // Reset submitting state when modal closes
    useEffect(() => {
        if (!isOpen) {
            setIsSubmitting(false);
        }
    }, [isOpen]);

    useEffect(() => {
        if (entry) {
            setSelectedProjectId(entry.project_id);
            setSelectedTaskId(entry.task_id);
            setDescription(entry.description || '');

            // Parse times from the entry
            // Extract time part safely without timezone conversion
            let startTimeStr = '09:00';
            let endTimeStr = '10:00';

            if (entry.started_at.includes('T')) {
                // ISO format: extract time from 2025-06-23T09:00:00.000000Z
                const timePart = entry.started_at.split('T')[1];
                startTimeStr = timePart.substring(0, 5); // Get HH:mm
            } else {
                // MySQL format: extract time from 2025-06-23 09:00:00
                const parts = entry.started_at.split(' ');
                if (parts.length > 1) {
                    startTimeStr = parts[1].substring(0, 5); // Get HH:mm
                }
            }

            // Calculate end time based on duration
            if (entry.ended_at || entry.stopped_at) {
                const endDateStr = entry.ended_at || entry.stopped_at!;
                if (endDateStr.includes('T')) {
                    const timePart = endDateStr.split('T')[1];
                    endTimeStr = timePart.substring(0, 5);
                } else {
                    const parts = endDateStr.split(' ');
                    if (parts.length > 1) {
                        endTimeStr = parts[1].substring(0, 5);
                    }
                }
            } else {
                // Calculate end time from start time + duration
                const [hours, minutes] = startTimeStr.split(':').map(Number);
                const totalMinutes = hours * 60 + minutes + Math.floor(entry.duration / 60);
                const endHours = Math.floor(totalMinutes / 60) % 24;
                const endMinutes = totalMinutes % 60;
                endTimeStr = `${endHours.toString().padStart(2, '0')}:${endMinutes.toString().padStart(2, '0')}`;
            }

            setStartTime(startTimeStr);
            setEndTime(endTimeStr);
        } else {
            // Reset form when entry is null
            setSelectedProjectId(null);
            setSelectedTaskId(null);
            setDescription('');
            setStartTime('09:00');
            setEndTime('10:00');
        }
        // Always reset submitting state when entry changes
        setIsSubmitting(false);
    }, [entry]);

    const availableTasks = selectedProjectId ? tasks.filter((task) => task.project_id === selectedProjectId) : [];

    const calculateDuration = () => {
        const [startHours, startMinutes] = startTime.split(':').map(Number);
        const [endHours, endMinutes] = endTime.split(':').map(Number);

        const startTotalMinutes = startHours * 60 + startMinutes;
        let endTotalMinutes = endHours * 60 + endMinutes;

        // If end time is less than start time, assume it's the next day
        if (endTotalMinutes < startTotalMinutes) {
            endTotalMinutes += 24 * 60; // Add 24 hours in minutes
        }

        const durationMinutes = endTotalMinutes - startTotalMinutes;

        if (durationMinutes <= 0) return '00:00';

        const hours = Math.floor(durationMinutes / 60);
        const minutes = durationMinutes % 60;

        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
    };

    const handleSubmit = async () => {
        if (!entry || !entry.id) {
            return;
        }

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

        const duration = calculateDuration();
        if (duration === '00:00') {
            setValidationError('La hora de fin debe ser posterior a la hora de inicio');
            return;
        }

        // Check if duration is more than 12 hours but don't block
        const [hours] = duration.split(':').map(Number);
        if (hours > 12) {
            setValidationError(`La duración es de ${duration} horas. Por favor verifica que sea correcto.`);
            // Don't return - let user submit if they want
        }

        // Double-check entry ID is valid before submitting
        if (typeof entry.id !== 'number' || entry.id <= 0) {
            setValidationError('Error: ID de entrada inválido');
            return;
        }

        setIsSubmitting(true);
        try {
            // Extract just the date part from the datetime string
            let dateStr: string;
            if (entry.started_at.includes('T')) {
                // ISO format: 2025-06-23T09:00:00.000000Z
                dateStr = entry.started_at.split('T')[0];
            } else {
                // MySQL format: 2025-06-23 09:00:00
                dateStr = entry.started_at.split(' ')[0];
            }

            await onSubmit({
                id: entry.id,
                project_id: selectedProjectId,
                task_id: selectedTaskId,
                description,
                duration,
                started_at: `${dateStr} ${startTime}:00`,
                ended_at: `${dateStr} ${endTime}:00`,
            });
            // Reset submitting state after successful update
            setIsSubmitting(false);
        } catch (error) {
            setIsSubmitting(false);
        }
    };

    const handleBackdropClick = (e: React.MouseEvent) => {
        if (e.target === e.currentTarget && !isSubmitting) {
            onClose();
        }
    };

    const handleClose = () => {
        if (!isSubmitting) {
            onClose();
        }
    };

    if (!isOpen || !entry) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center" onClick={handleBackdropClick}>
            {/* Backdrop */}
            <div className="fixed inset-0 bg-black/80 backdrop-blur-sm" />

            {/* Modal */}
            <div className="bg-background border-border relative mx-4 w-full max-w-lg rounded-lg border p-6 shadow-lg">
                <h2 className="text-foreground mb-4 text-lg font-semibold">Editar entrada de tiempo</h2>

                <div className="space-y-4">
                    {/* Validation Error Alert */}
                    {validationError && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>{validationError}</AlertDescription>
                        </Alert>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="project" className="text-foreground">
                            Proyecto
                            <span className="text-destructive ml-1">*</span>
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
                                {projects.map((project) => (
                                    <SelectItem key={project.id} value={project.id.toString()}>
                                        <div className="flex items-center gap-2">
                                            {project.color && <div className="h-3 w-3 rounded-full" style={{ backgroundColor: project.color }} />}
                                            {project.name}
                                        </div>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="task" className="text-foreground">
                            Tarea
                            {availableTasks.length > 0 && <span className="text-destructive ml-1">*</span>}
                        </Label>
                        <Select
                            value={selectedTaskId?.toString() || ''}
                            onValueChange={(value) => setSelectedTaskId(Number(value))}
                            disabled={!selectedProjectId || availableTasks.length === 0}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={availableTasks.length === 0 ? 'Sin tareas disponibles' : 'Selecciona una tarea'} />
                            </SelectTrigger>
                            <SelectContent>
                                {availableTasks.map((task) => (
                                    <SelectItem key={task.id} value={task.id.toString()}>
                                        {task.title}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description" className="text-foreground">
                            Descripción
                        </Label>
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
                            <Label htmlFor="start-time" className="text-foreground">
                                Hora de inicio
                            </Label>
                            <Input id="start-time" type="time" value={startTime} onChange={(e) => setStartTime(e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="end-time" className="text-foreground">
                                Hora de fin
                            </Label>
                            <Input id="end-time" type="time" value={endTime} onChange={(e) => setEndTime(e.target.value)} />
                        </div>
                    </div>

                    <div className="text-muted-foreground text-sm">
                        Duración: <span className="text-foreground font-medium">{calculateDuration()}</span>
                        {(() => {
                            const [hours] = calculateDuration().split(':').map(Number);
                            if (hours > 12) {
                                return <div className="mt-1 text-xs text-amber-600 dark:text-amber-500">⚠️ Duración mayor a 12 horas</div>;
                            }
                            return null;
                        })()}
                    </div>
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <Button variant="outline" onClick={handleClose} disabled={isSubmitting}>
                        Cancelar
                    </Button>
                    <Button onClick={handleSubmit} disabled={isSubmitting}>
                        {isSubmitting ? 'Guardando...' : 'Guardar cambios'}
                    </Button>
                </div>
            </div>
        </div>
    );
}
