import React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Play, Pause, StopCircle } from 'lucide-react';

interface TimerProps {
    initialTime?: number;
    isRunning: boolean;
    isPaused: boolean;
    showControls?: boolean;
    hasActiveTimer: boolean;
    formattedTime: string;
    canStartTimer?: boolean;
    startDisabledReason?: string;
    onStart: () => void;
    onPause: () => void;
    onResume: () => void;
    onStop: () => void;
}

export function Timer({
    isRunning,
    isPaused,
    showControls = true,
    hasActiveTimer,
    formattedTime,
    canStartTimer = true,
    startDisabledReason,
    onStart,
    onPause,
    onResume,
    onStop
}: TimerProps) {
    const canStart = !isRunning && !isPaused && !hasActiveTimer;
    const showStart = !isRunning && !isPaused;
    const showPause = isRunning && !isPaused;
    const showResume = !isRunning && isPaused;
    const showStop = isRunning || isPaused;

    return (
        <Card className="w-full">
            <CardContent className="pt-6">
                <div className="flex flex-col items-center space-y-4">
                    {/* Timer Display */}
                    <div className="text-6xl font-mono font-bold text-center timer-display">
                        {formattedTime}
                    </div>

                    {/* Controls */}
                    {showControls && (
                        <div className="flex items-center gap-2 timer-controls">
                            {showStart && (
                                <Button
                                    size="lg"
                                    onClick={onStart}
                                    disabled={hasActiveTimer || !canStartTimer}
                                    className="start-button"
                                    title={hasActiveTimer ? 'Ya hay otro temporizador activo' : (startDisabledReason || 'Iniciar temporizador')}
                                >
                                    <Play className="h-5 w-5 mr-2" />
                                    {hasActiveTimer ? 'Otro timer activo' : 'Iniciar'}
                                </Button>
                            )}

                            {showPause && (
                                <Button
                                    size="lg"
                                    variant="secondary"
                                    onClick={onPause}
                                    className="pause-button"
                                    title="Pausar temporizador"
                                >
                                    <Pause className="h-5 w-5 mr-2" />
                                    Pausar
                                </Button>
                            )}

                            {showResume && (
                                <Button
                                    size="lg"
                                    onClick={onResume}
                                    className="resume-button"
                                    title="Reanudar temporizador"
                                >
                                    <Play className="h-5 w-5 mr-2" />
                                    Reanudar
                                </Button>
                            )}

                            {showStop && (
                                <Button
                                    size="lg"
                                    variant="destructive"
                                    onClick={onStop}
                                    className="stop-button"
                                    title="Detener temporizador"
                                >
                                    <StopCircle className="h-5 w-5 mr-2" />
                                    Detener
                                </Button>
                            )}
                        </div>
                    )}

                    {/* Status indicator for active timer warning */}
                    {hasActiveTimer && showStart && (
                        <p className="text-sm text-muted-foreground text-center">
                            Solo se permite un temporizador activo a la vez.
                            Detén el temporizador actual para iniciar uno nuevo.
                        </p>
                    )}
                    
                    {/* Warning for missing task selection */}
                    {!hasActiveTimer && showStart && !canStartTimer && startDisabledReason && (
                        <p className="text-sm text-amber-600 dark:text-amber-500 text-center">
                            {startDisabledReason}
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}