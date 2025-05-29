import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { differenceInSeconds, parseISO } from 'date-fns';
import { Pause, Play, StopCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

interface TimeEntry {
    id: number;
    description: string;
    started_at: string;
    stopped_at: string | null;
    duration: number | null;
    formatted_duration: string | null;
    is_billable: boolean;
    task_id: number | null;
    project_id: number | null;
    category_id: number | null;
    task?: {
        id: number;
        title: string;
    };
    project?: {
        id: number;
        name: string;
    };
    category?: {
        id: number;
        name: string;
        color: string;
    };
}

interface TimerProps {
    timeEntry: TimeEntry;
    onStop: () => void;
}

export function Timer({ timeEntry, onStop }: TimerProps) {
    const [seconds, setSeconds] = useState<number>(0);
    const [isPaused, setIsPaused] = useState<boolean>(false);

    useEffect(() => {
        // Calculate initial seconds based on how long timer has been running
        const initialSeconds = differenceInSeconds(new Date(), parseISO(timeEntry.started_at));
        setSeconds(initialSeconds);

        // Set interval to update timer every second
        const interval = setInterval(() => {
            if (!isPaused) {
                setSeconds((prevSeconds) => prevSeconds + 1);
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [timeEntry.started_at, isPaused]);

    // Format seconds as HH:MM:SS
    const formatTime = (totalSeconds: number) => {
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    };

    const handlePauseToggle = () => {
        setIsPaused(!isPaused);
    };

    return (
        <div className="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
            <div className="flex-1">
                <div className="mb-1 font-medium">{timeEntry.description}</div>
                <div className="flex flex-wrap gap-2 text-sm">
                    {timeEntry.project && <span className="text-muted-foreground">{timeEntry.project.name}</span>}
                    {timeEntry.task && (
                        <>
                            <span className="text-muted-foreground">•</span>
                            <span className="text-muted-foreground">{timeEntry.task.title}</span>
                        </>
                    )}
                    {timeEntry.category && (
                        <>
                            <span className="text-muted-foreground">•</span>
                            <Badge
                                style={{
                                    backgroundColor: timeEntry.category.color,
                                    color: '#fff',
                                }}
                            >
                                {timeEntry.category.name}
                            </Badge>
                        </>
                    )}
                    {timeEntry.is_billable && (
                        <>
                            <span className="text-muted-foreground">•</span>
                            <Badge variant="default">Facturable</Badge>
                        </>
                    )}
                </div>
            </div>

            <div className="flex items-center gap-3">
                <div className="w-24 text-center font-mono text-xl font-semibold">{formatTime(seconds)}</div>

                <div className="flex items-center gap-1">
                    <Button variant="outline" size="icon" onClick={handlePauseToggle} title={isPaused ? 'Reanudar' : 'Pausar'}>
                        {isPaused ? <Play className="h-4 w-4" /> : <Pause className="h-4 w-4" />}
                    </Button>

                    <Button
                        variant="outline"
                        size="icon"
                        onClick={onStop}
                        title="Detener"
                        className="text-destructive border-destructive hover:bg-destructive/10"
                    >
                        <StopCircle className="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
