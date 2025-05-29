import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { CalendarDays, Clock, TrendingUp, BarChart3 } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import TimerWidget from '@/components/time-tracking/timer-widget';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import axios from 'axios';
import type { PageProps } from '@/types';

interface Project {
    id: number;
    name: string;
}

interface Task {
    id: number;
    title: string;
    project_id: number;
}

interface TimeEntry {
    id: number;
    description: string;
    duration: number;
    formatted_duration: string;
    started_at: string;
    ended_at: string;
    project: Project | null;
    task: Task | null;
    is_billable: boolean;
    created_via: string;
}

interface DailySummary {
    total_time: number;
    manual_time: number;
    tracked_time: number;
    formatted_total: string;
    productivity: {
        productive_seconds: number;
        neutral_seconds: number;
        distracting_seconds: number;
        productive_percentage: number;
        neutral_percentage: number;
        distracting_percentage: number;
    } | null;
}

interface TimeTrackingDashboardProps extends PageProps {
    projects: Project[];
    tasks: Task[];
}

export default function TimeTrackingDashboard({ projects, tasks }: TimeTrackingDashboardProps) {
    const [timeEntries, setTimeEntries] = useState<TimeEntry[]>([]);
    const [dailySummary, setDailySummary] = useState<DailySummary | null>(null);
    const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('today');

    useEffect(() => {
        fetchDailyData(selectedDate);
    }, [selectedDate]);

    const fetchDailyData = async (date: string) => {
        setLoading(true);
        try {
            const response = await axios.get('/api/reports/daily', {
                params: { date }
            });
            setTimeEntries(response.data.time_entries);
            setDailySummary(response.data.summary);
        } catch (error) {
            console.error('Failed to fetch daily data:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleTimerStop = (newTimeEntry: TimeEntry) => {
        // Add the new time entry to the list
        setTimeEntries([newTimeEntry, ...timeEntries]);
        // Refresh daily summary
        fetchDailyData(selectedDate);
    };

    const formatTime = (seconds: number): string => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        return `${hours}h ${minutes}m`;
    };

    const formatDateTime = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
    };

    return (
        <AppLayout>
            <Head title="Time Tracking" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Time Tracking</h1>
                        <p className="text-muted-foreground">Track your time and monitor productivity</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="date"
                            value={selectedDate}
                            onChange={(e) => setSelectedDate(e.target.value)}
                            className="px-3 py-2 border rounded-md"
                            max={new Date().toISOString().split('T')[0]}
                        />
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Time Today</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {dailySummary ? formatTime(dailySummary.total_time) : '0h 0m'}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {timeEntries.length} time entries
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Manual Time</CardTitle>
                            <CalendarDays className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {dailySummary ? formatTime(dailySummary.manual_time) : '0h 0m'}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Timer & manual entries
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Productivity</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {dailySummary?.productivity?.productive_percentage || 0}%
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Productive time
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Projects</CardTitle>
                            <BarChart3 className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {projects.length}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Across all projects
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-1">
                        <TimerWidget
                            projects={projects}
                            tasks={tasks}
                            onTimerStop={handleTimerStop}
                        />
                    </div>

                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Time Entries</CardTitle>
                                <CardDescription>
                                    Your tracked time for {new Date(selectedDate).toLocaleDateString()}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Tabs value={activeTab} onValueChange={setActiveTab}>
                                    <TabsList className="grid w-full grid-cols-3">
                                        <TabsTrigger value="today">Today</TabsTrigger>
                                        <TabsTrigger value="week">This Week</TabsTrigger>
                                        <TabsTrigger value="month">This Month</TabsTrigger>
                                    </TabsList>
                                    <TabsContent value="today" className="space-y-4">
                                        {loading ? (
                                            <div className="text-center py-8 text-muted-foreground">
                                                Loading time entries...
                                            </div>
                                        ) : timeEntries.length === 0 ? (
                                            <div className="text-center py-8 text-muted-foreground">
                                                No time entries for this date
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                {timeEntries.map((entry) => (
                                                    <div
                                                        key={entry.id}
                                                        className="flex items-center justify-between p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                                                    >
                                                        <div className="flex-1">
                                                            <div className="font-medium">
                                                                {entry.description || 'No description'}
                                                            </div>
                                                            <div className="text-sm text-muted-foreground">
                                                                {entry.project?.name || 'No project'}
                                                                {entry.task && ` • ${entry.task.title}`}
                                                            </div>
                                                            <div className="text-xs text-muted-foreground mt-1">
                                                                {formatDateTime(entry.started_at)} - {formatDateTime(entry.ended_at)}
                                                                {' • '}
                                                                {entry.created_via === 'timer' ? 'Timer' : 'Manual'}
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <div className="font-mono font-semibold">
                                                                {entry.formatted_duration}
                                                            </div>
                                                            {entry.is_billable && (
                                                                <span className="text-xs text-green-600">Billable</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </TabsContent>
                                    <TabsContent value="week">
                                        <div className="text-center py-8 text-muted-foreground">
                                            Week view coming soon...
                                        </div>
                                    </TabsContent>
                                    <TabsContent value="month">
                                        <div className="text-center py-8 text-muted-foreground">
                                            Month view coming soon...
                                        </div>
                                    </TabsContent>
                                </Tabs>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}