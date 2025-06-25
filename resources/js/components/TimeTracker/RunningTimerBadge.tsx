import React, { useEffect, useState } from 'react';
import { useTimeEntryStore } from '@/stores/timeEntryStore';
import { Link } from '@inertiajs/react';
import { Clock, Pause, Play } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

export function RunningTimerBadge() {
    const {
        state,
        formattedDuration,
        status,
        hasActiveTimer,
        pauseTimer,
        resumeTimer,
        stopTimer,
    } = useTimeEntryStore();

    const [isVisible, setIsVisible] = useState(false);


    // Show/hide badge based on timer state
    useEffect(() => {
        setIsVisible(hasActiveTimer || state.currentEntry.is_paused);
    }, [hasActiveTimer, state.currentEntry.is_paused]);

    // Debug: Always show for testing
    // if (!isVisible) {
    //     return null;
    // }

    const isRunning = status === 'running';
    const isPaused = status === 'paused';

    return (
        <TooltipProvider>
            <div className="flex items-center gap-2">
                {/* Timer Display */}
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link
                            href="/time"
                            className="flex items-center gap-2 px-3 py-1.5 rounded-md bg-primary/10 hover:bg-primary/20 transition-colors"
                        >
                            <Clock className={`h-4 w-4 ${isRunning ? 'text-primary animate-pulse' : 'text-muted-foreground'}`} />
                            <span className="font-mono text-sm font-medium">
                                {formattedDuration || '00:00:00'}
                            </span>
                            {state.currentEntry.project_id && (
                                <span className="text-xs text-muted-foreground max-w-[120px] truncate">
                                    Proyecto
                                </span>
                            )}
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent>
                        <div className="space-y-1">
                            <p className="font-medium">Timer activo</p>
                            {state.currentEntry.description && (
                                <p className="text-xs text-muted-foreground">
                                    {state.currentEntry.description}
                                </p>
                            )}
                            {state.currentEntry.project_id && (
                                <p className="text-xs text-muted-foreground">
                                    Proyecto ID: {state.currentEntry.project_id}
                                </p>
                            )}
                            {state.currentEntry.task_id && (
                                <p className="text-xs text-muted-foreground">
                                    Tarea ID: {state.currentEntry.task_id}
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground pt-1">
                                Click para ir al tracker de tiempo
                            </p>
                        </div>
                    </TooltipContent>
                </Tooltip>

                {/* Control Buttons */}
                <div className="flex items-center gap-1">
                    {isRunning && (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-7 w-7"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        pauseTimer();
                                    }}
                                >
                                    <Pause className="h-3.5 w-3.5" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Pausar timer</TooltipContent>
                        </Tooltip>
                    )}

                    {isPaused && (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-7 w-7"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        resumeTimer();
                                    }}
                                >
                                    <Play className="h-3.5 w-3.5" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Reanudar timer</TooltipContent>
                        </Tooltip>
                    )}

                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                size="icon"
                                variant="ghost"
                                className="h-7 w-7 hover:bg-destructive/10 hover:text-destructive"
                                onClick={(e) => {
                                    e.preventDefault();
                                    if (confirm('¿Estás seguro de que quieres detener el timer?')) {
                                        stopTimer();
                                    }
                                }}
                            >
                                <div className="h-3.5 w-3.5 rounded-full bg-destructive" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Detener timer</TooltipContent>
                    </Tooltip>
                </div>

                {/* Status Badge */}
                <Badge 
                    variant={isRunning ? 'default' : 'secondary'} 
                    className="text-xs"
                >
                    {isRunning ? 'Activo' : 'Pausado'}
                </Badge>
            </div>
        </TooltipProvider>
    );
}