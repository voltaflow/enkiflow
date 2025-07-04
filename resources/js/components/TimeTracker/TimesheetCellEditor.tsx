import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Textarea } from '@/components/ui/textarea';
import { formatDurationHHMM, parseDurationHHMM } from '@/lib/time-utils';
import { Trash2 } from 'lucide-react';
import React, { useEffect, useState } from 'react';

interface TimesheetCellEditorProps {
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
    duration: number; // in seconds
    description?: string;
    onSave: (duration: number, description: string) => void;
    onDelete?: () => void;
    children: React.ReactNode;
    hasExistingEntry?: boolean;
}

export function TimesheetCellEditor({
    isOpen,
    onOpenChange,
    duration,
    description = '',
    onSave,
    onDelete,
    children,
    hasExistingEntry = false,
}: TimesheetCellEditorProps) {
    const [timeValue, setTimeValue] = useState('');
    const [descriptionValue, setDescriptionValue] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        if (isOpen) {
            setTimeValue(formatDurationHHMM(duration));
            setDescriptionValue(description);
            setError('');
        }
    }, [isOpen, duration, description]);

    const validateTime = (value: string): boolean => {
        // Allow empty value (will be treated as 00:00)
        if (!value.trim()) return true;

        // Check format HH:MM
        const regex = /^([0-9]{1,2}):([0-9]{2})$/;
        const match = value.match(regex);

        if (!match) {
            setError('Formato debe ser HH:MM');
            return false;
        }

        const hours = parseInt(match[1], 10);
        const minutes = parseInt(match[2], 10);

        if (hours > 23) {
            setError('Las horas no pueden ser mayores a 23');
            return false;
        }

        if (minutes > 59) {
            setError('Los minutos no pueden ser mayores a 59');
            return false;
        }

        setError('');
        return true;
    };

    const handleTimeChange = (value: string) => {
        // Allow typing numbers and colon
        const cleaned = value.replace(/[^0-9:]/g, '');

        // Auto-format: add colon after 2 digits if not present
        if (cleaned.length === 2 && !cleaned.includes(':')) {
            setTimeValue(cleaned + ':');
        } else if (cleaned.length <= 5) {
            // Max HH:MM
            setTimeValue(cleaned);
        }
    };

    const handleSave = () => {
        if (!validateTime(timeValue)) return;

        const seconds = parseDurationHHMM(timeValue || '00:00');
        onSave(seconds, descriptionValue);
        onOpenChange(false);
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && e.ctrlKey) {
            handleSave();
        } else if (e.key === 'Escape') {
            onOpenChange(false);
        }
    };

    return (
        <Popover open={isOpen} onOpenChange={onOpenChange}>
            <PopoverTrigger asChild>{children}</PopoverTrigger>
            <PopoverContent className="w-80" align="center" onKeyDown={handleKeyDown}>
                <div className="space-y-4">
                    {hasExistingEntry && <div className="text-muted-foreground bg-muted/50 rounded p-2 text-sm">✏️ Editando entrada existente</div>}
                    <div className="space-y-2">
                        <Label htmlFor="time">Tiempo (HH:MM)</Label>
                        <Input
                            id="time"
                            type="text"
                            placeholder="00:00"
                            value={timeValue}
                            onChange={(e) => handleTimeChange(e.target.value)}
                            className={error ? 'border-red-500' : ''}
                            autoFocus
                        />
                        {error && <p className="text-sm text-red-500">{error}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">Descripción</Label>
                        <Textarea
                            id="description"
                            placeholder="¿En qué trabajaste?"
                            value={descriptionValue}
                            onChange={(e) => setDescriptionValue(e.target.value)}
                            rows={3}
                            className="resize-none"
                        />
                    </div>

                    <div className="flex justify-between">
                        {hasExistingEntry && onDelete && (
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={() => {
                                    onDelete();
                                    onOpenChange(false);
                                }}
                            >
                                <Trash2 className="mr-1 h-4 w-4" />
                                Eliminar
                            </Button>
                        )}
                        <div className="ml-auto flex gap-2">
                            <Button variant="outline" size="sm" onClick={() => onOpenChange(false)}>
                                Cancelar
                            </Button>
                            <Button size="sm" onClick={handleSave} disabled={!!error}>
                                Guardar
                            </Button>
                        </div>
                    </div>

                    <p className="text-muted-foreground text-xs">Tip: Ctrl+Enter para guardar, Esc para cancelar</p>
                </div>
            </PopoverContent>
        </Popover>
    );
}
