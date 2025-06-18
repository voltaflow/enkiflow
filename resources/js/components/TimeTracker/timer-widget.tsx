import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTimerSync } from '@/hooks/use-broadcast-sync';
import axios from 'axios';
import { Clock, Pause, Play, Square } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Project {
    id: number;
    name: string;
}

interface Task {
    id: number;
    title: string;
    project_id: number;
}

interface Timer {
    id: number;
    description: string;
    project_id: number | null;
    task_id: number | null;
    started_at: string;
    is_running: boolean;
    total_duration: number;
    project?: Project;
    task?: Task;
}

interface TimerWidgetProps {
    projects: Project[];
    tasks: Task[];
    onTimerStop?: (timeEntry: any) => void;
}

export default function TimerWidget({ projects, tasks, onTimerStop }: TimerWidgetProps) {
    const [timer, setTimer] = useState<Timer | null>(null);
    const [description, setDescription] = useState('');
    const [selectedProjectId, setSelectedProjectId] = useState<string>('none');
    const [selectedTaskId, setSelectedTaskId] = useState<string>('none');
    const [displayTime, setDisplayTime] = useState('00:00:00');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Multi-tab synchronization
    const { notifyTimerStarted, notifyTimerStopped } = useTimerSync();

    // Fetch current timer on mount
    useEffect(() => {
        fetchCurrentTimer();
    }, []);

    // Update display time every second
    useEffect(() => {
        if (!timer) {
            setDisplayTime('00:00:00');
            return;
        }

        const updateTimer = () => {
            if (timer.is_running) {
                const startTime = new Date(timer.started_at).getTime();
                const now = new Date().getTime();
                const elapsed = Math.floor((now - startTime) / 1000) + timer.total_duration;
                setDisplayTime(formatTime(elapsed));
            } else {
                setDisplayTime(formatTime(timer.total_duration));
            }
        };

        updateTimer();
        const interval = setInterval(updateTimer, 1000);

        return () => clearInterval(interval);
    }, [timer]);

    const fetchCurrentTimer = async () => {
        try {
            const response = await axios.get('/api/timer/current');
            if (response.data.timer) {
                setTimer(response.data.timer);
                setDescription(response.data.timer.description || '');
                setSelectedProjectId(response.data.timer.project_id?.toString() || 'none');
                setSelectedTaskId(response.data.timer.task_id?.toString() || 'none');
            }
        } catch (err) {
            console.error('Failed to fetch current timer:', err);
        }
    };

    const formatTime = (seconds: number): string => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    const handleStart = async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await axios.post('/api/timer/start', {
                description,
                project_id: selectedProjectId && selectedProjectId !== 'none' ? parseInt(selectedProjectId) : null,
                task_id: selectedTaskId && selectedTaskId !== 'none' ? parseInt(selectedTaskId) : null,
            });

            setTimer(response.data.timer);
            notifyTimerStarted(response.data.timer);
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to start timer');
        } finally {
            setLoading(false);
        }
    };

    const handlePause = async () => {
        if (!timer) return;

        setLoading(true);
        setError(null);

        try {
            const response = await axios.post(`/api/timer/${timer.id}/pause`);
            setTimer({ ...timer, ...response.data.timer });
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to pause timer');
        } finally {
            setLoading(false);
        }
    };

    const handleResume = async () => {
        if (!timer) return;

        setLoading(true);
        setError(null);

        try {
            const response = await axios.post(`/api/timer/${timer.id}/resume`);
            setTimer({ ...timer, ...response.data.timer });
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to resume timer');
        } finally {
            setLoading(false);
        }
    };

    const handleStop = async () => {
        if (!timer) return;

        setLoading(true);
        setError(null);

        try {
            const response = await axios.post(`/api/timer/${timer.id}/stop`);
            setTimer(null);
            setDescription('');
            setSelectedProjectId('none');
            setSelectedTaskId('none');

            notifyTimerStopped(timer, response.data.time_entry);

            if (onTimerStop) {
                onTimerStop(response.data.time_entry);
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to stop timer');
        } finally {
            setLoading(false);
        }
    };

    const handleUpdateTimer = async () => {
        if (!timer) return;

        try {
            await axios.put(`/api/timer/${timer.id}`, {
                description,
                project_id: selectedProjectId && selectedProjectId !== 'none' ? parseInt(selectedProjectId) : null,
                task_id: selectedTaskId && selectedTaskId !== 'none' ? parseInt(selectedTaskId) : null,
            });
        } catch (err) {
            console.error('Failed to update timer:', err);
        }
    };

    const filteredTasks = selectedProjectId && selectedProjectId !== 'none' ? tasks.filter((task) => task.project_id === parseInt(selectedProjectId)) : tasks;

    return (
        <Card className="w-full">
            <CardContent className="p-6">
                <div className="mb-4 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Clock className="text-muted-foreground h-5 w-5" />
                        <h3 className="text-lg font-semibold">Time Tracker</h3>
                    </div>
                    <div className="font-mono text-2xl font-bold">{displayTime}</div>
                </div>

                <div className="space-y-4">
                    <Input
                        placeholder="What are you working on?"
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        onBlur={timer ? handleUpdateTimer : undefined}
                        disabled={loading}
                    />

                    <div className="grid grid-cols-2 gap-2">
                        <Select value={selectedProjectId} onValueChange={setSelectedProjectId} disabled={loading}>
                            <SelectTrigger>
                                <SelectValue placeholder="Select project" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No project</SelectItem>
                                {projects.map((project) => (
                                    <SelectItem key={project.id} value={project.id.toString()}>
                                        {project.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select value={selectedTaskId} onValueChange={setSelectedTaskId} disabled={loading || !selectedProjectId || selectedProjectId === 'none'}>
                            <SelectTrigger>
                                <SelectValue placeholder="Select task" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No task</SelectItem>
                                {filteredTasks.map((task) => (
                                    <SelectItem key={task.id} value={task.id.toString()}>
                                        {task.title}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {error && <div className="text-destructive text-sm">{error}</div>}

                    <div className="flex gap-2">
                        {!timer ? (
                            <Button onClick={handleStart} disabled={loading} className="flex-1">
                                <Play className="mr-2 h-4 w-4" />
                                Start Timer
                            </Button>
                        ) : (
                            <>
                                {timer.is_running ? (
                                    <Button onClick={handlePause} disabled={loading} variant="secondary" className="flex-1">
                                        <Pause className="mr-2 h-4 w-4" />
                                        Pause
                                    </Button>
                                ) : (
                                    <Button onClick={handleResume} disabled={loading} variant="secondary" className="flex-1">
                                        <Play className="mr-2 h-4 w-4" />
                                        Resume
                                    </Button>
                                )}
                                <Button onClick={handleStop} disabled={loading} variant="destructive" className="flex-1">
                                    <Square className="mr-2 h-4 w-4" />
                                    Stop
                                </Button>
                            </>
                        )}
                    </div>

                    {timer && (
                        <div className="text-muted-foreground flex items-center gap-2 text-sm">
                            <Badge variant={timer.is_running ? 'default' : 'secondary'}>{timer.is_running ? 'Running' : 'Paused'}</Badge>
                            {timer.project && <span>Project: {timer.project.name}</span>}
                            {timer.task && <span>• Task: {timer.task.title}</span>}
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
