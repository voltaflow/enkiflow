import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { AlertCircle } from 'lucide-react';
import { useState } from 'react';

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

interface AddTimeModalProps {
    isOpen: boolean;
    onClose: () => void;
    projects: Project[];
    tasks: Task[];
    date: Date;
    onSubmit: (data: {
        project_id: number | null;
        task_id: number | null;
        description: string;
        duration: string;
        started_at: string;
        ended_at: string;
    }) => Promise<void>;
}

export function AddTimeModal({ isOpen, onClose, projects, tasks, date, onSubmit }: AddTimeModalProps) {
    const [selectedProjectId, setSelectedProjectId] = useState<number | null>(projects.length > 0 ? projects[0].id : null);
    const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
    const [description, setDescription] = useState('');
    const [startTime, setStartTime] = useState('09:00');
    const [endTime, setEndTime] = useState('10:00');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [validationError, setValidationError] = useState<string | null>(null);

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

        // Check if duration is more than 12 hours
        const [hours] = duration.split(':').map(Number);
        if (hours > 12) {
            setValidationError(`La duración es de ${duration} horas. Por favor verifica que sea correcto.`);
            // Don't return here, let the user submit if they want
        }

        setIsSubmitting(true);
        try {
            const dateStr = format(date, 'yyyy-MM-dd');
            // Create Date objects and convert to ISO format for Laravel
            const startDate = new Date(`${dateStr}T${startTime}:00`);
            const endDate = new Date(`${dateStr}T${endTime}:00`);
            const startDateTime = startDate.toISOString();
            const endDateTime = endDate.toISOString();

            await onSubmit({
                project_id: selectedProjectId,
                task_id: selectedTaskId,
                description,
                duration,
                started_at: startDateTime,
                ended_at: endDateTime,
            });

            // Reset form
            setSelectedProjectId(projects.length > 0 ? projects[0].id : null);
            setSelectedTaskId(null);
            setDescription('');
            setStartTime('09:00');
            setEndTime('10:00');
            setValidationError(null);
            onClose();
        } catch (error: any) {
            if (error.message) {
                setValidationError(error.message);
            } else {
                setValidationError('Error al agregar entrada de tiempo. Por favor intenta de nuevo.');
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Agregar tiempo - {format(date, "d 'de' MMMM, yyyy", { locale: es })}</DialogTitle>
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
                        <Label htmlFor="project">
                            Proyecto <span className="text-red-500">*</span>
                        </Label>
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
                                {projects.map((project) => (
                                    <SelectItem key={project.id} value={project.id.toString()}>
                                        <div className="flex items-center gap-2">
                                            {project.color && <div className="h-3 w-3 rounded-full" style={{ backgroundColor: project.color }} />}
                                            <span>{project.name}</span>
                                        </div>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Task Selector */}
                    {selectedProjectId && availableTasks.length > 0 && (
                        <div className="space-y-2">
                            <Label htmlFor="task">
                                Tarea <span className="text-red-500">*</span>
                            </Label>
                            <Select value={selectedTaskId?.toString() || ''} onValueChange={(value) => setSelectedTaskId(parseInt(value))}>
                                <SelectTrigger id="task">
                                    <SelectValue placeholder="Seleccionar tarea" />
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
                    )}

                    {/* Description */}
                    <div className="space-y-2">
                        <Label htmlFor="description">
                            Descripción
                            <span className="text-muted-foreground ml-2 text-xs">({description.length}/255)</span>
                        </Label>
                        <Textarea
                            id="description"
                            placeholder="¿En qué trabajaste?"
                            value={description}
                            onChange={(e) => setDescription(e.target.value.slice(0, 255))}
                            rows={3}
                            maxLength={255}
                        />
                    </div>

                    {/* Time Range */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="start-time">Hora inicio</Label>
                            <Input id="start-time" type="time" value={startTime} onChange={(e) => setStartTime(e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="end-time">Hora fin</Label>
                            <Input id="end-time" type="time" value={endTime} onChange={(e) => setEndTime(e.target.value)} />
                        </div>
                    </div>

                    {/* Duration Display */}
                    <div className="py-2 text-center">
                        <span className="text-muted-foreground text-sm">Duración: </span>
                        <span className="font-semibold">{calculateDuration()}</span>
                        {(() => {
                            const [hours] = calculateDuration().split(':').map(Number);
                            if (hours > 12) {
                                return <div className="mt-1 text-xs text-amber-600 dark:text-amber-500">⚠️ Duración mayor a 12 horas</div>;
                            }
                            return null;
                        })()}
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={isSubmitting}>
                        Cancelar
                    </Button>
                    <Button onClick={handleSubmit} disabled={isSubmitting || !selectedProjectId || (availableTasks.length > 0 && !selectedTaskId)}>
                        {isSubmitting ? 'Guardando...' : 'Guardar'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
