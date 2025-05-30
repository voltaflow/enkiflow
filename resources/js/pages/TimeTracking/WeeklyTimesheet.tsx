import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTimesheetSync } from '@/hooks/use-broadcast-sync';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { addDays, format, parseISO } from 'date-fns';
import { Calendar, ChevronDown, ChevronLeft, ChevronRight, ChevronUp, Download, Send } from 'lucide-react';
import React, { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Project {
    id: number;
    name: string;
    tasks: Task[];
}

interface Task {
    id: number;
    name: string;
}

interface TimeEntry {
    id?: number;
    hours: number;
    description?: string;
    is_billable: boolean;
    locked: boolean;
}

interface WeeklyTimesheet {
    id: number;
    status: 'draft' | 'submitted' | 'approved' | 'rejected';
    total_hours: number;
    total_billable_hours: number;
    is_editable: boolean;
}

interface Props {
    weekStart: string;
    weekEnd: string;
    timesheet: WeeklyTimesheet;
    projects: Project[];
    entriesByProjectTask: Record<string, any>;
    dailyTotals: Record<string, number>;
    weekTotal: number;
}

export default function WeeklyTimesheet({ weekStart, weekEnd, timesheet, projects, entriesByProjectTask, dailyTotals, weekTotal }: Props) {
    const [expandedProjects, setExpandedProjects] = useState<Set<number>>(new Set());
    const [editingCell, setEditingCell] = useState<string | null>(null);
    const [cellValues, setCellValues] = useState<Record<string, string>>({});
    const [isSaving, setIsSaving] = useState(false);

    // Multi-tab synchronization
    const { notifyTimesheetUpdated } = useTimesheetSync();

    const weekStartDate = parseISO(weekStart);
    const weekDays = Array.from({ length: 7 }, (_, i) => addDays(weekStartDate, i));

    // Initialize cell values from existing entries
    useEffect(() => {
        const values: Record<string, string> = {};
        Object.entries(entriesByProjectTask).forEach(([projectId, projectData]: [string, any]) => {
            Object.entries(projectData.tasks).forEach(([taskId, taskData]: [string, any]) => {
                Object.entries(taskData.entries).forEach(([date, entry]: [string, any]) => {
                    const key = `${projectId}-${taskId}-${date}`;
                    values[key] = entry.hours.toString();
                });
            });
        });
        setCellValues(values);
    }, [entriesByProjectTask]);

    const toggleProject = (projectId: number) => {
        const newExpanded = new Set(expandedProjects);
        if (newExpanded.has(projectId)) {
            newExpanded.delete(projectId);
        } else {
            newExpanded.add(projectId);
        }
        setExpandedProjects(newExpanded);
    };

    const handleCellClick = (key: string) => {
        if (timesheet.is_editable) {
            setEditingCell(key);
        }
    };

    const handleCellChange = (key: string, value: string) => {
        // Only allow numbers and decimal point
        if (value === '' || /^\d*\.?\d*$/.test(value)) {
            setCellValues((prev) => ({ ...prev, [key]: value }));
        }
    };

    const handleCellBlur = async (key: string) => {
        setEditingCell(null);

        const [projectId, taskId, date] = key.split('-');
        const hours = parseFloat(cellValues[key] || '0');

        // Skip if value hasn't changed
        const existingEntry = entriesByProjectTask[projectId]?.tasks[taskId]?.entries[date];
        if (existingEntry && existingEntry.hours === hours) {
            return;
        }

        // Save the change
        await saveEntry(parseInt(projectId), taskId === 'no-task' ? null : parseInt(taskId), date, hours);
    };

    const handleKeyDown = (e: React.KeyboardEvent, key: string) => {
        if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            handleCellBlur(key);

            // Move to next cell on Tab
            if (e.key === 'Tab') {
                // Implement tab navigation logic here
            }
        } else if (e.key === 'Escape') {
            // Cancel edit and restore original value
            const [projectId, taskId, date] = key.split('-');
            const existingEntry = entriesByProjectTask[projectId]?.tasks[taskId]?.entries[date];
            if (existingEntry) {
                setCellValues((prev) => ({ ...prev, [key]: existingEntry.hours.toString() }));
            } else {
                setCellValues((prev) => ({ ...prev, [key]: '0' }));
            }
            setEditingCell(null);
        }
    };

    const saveEntry = async (projectId: number, taskId: number | null, date: string, hours: number) => {
        setIsSaving(true);
        try {
            await axios.post(`/time/timesheet/${timesheet.id}/update`, {
                entries: [
                    {
                        project_id: projectId,
                        task_id: taskId,
                        date: date,
                        hours: hours,
                        is_billable: true,
                    },
                ],
            });

            // Notify other tabs
            notifyTimesheetUpdated(timesheet.id, {
                project_id: projectId,
                task_id: taskId,
                date: date,
                hours: hours,
            });

            // Refresh the page to get updated data
            router.reload({ only: ['entriesByProjectTask', 'dailyTotals', 'weekTotal', 'timesheet'] });
        } catch (error) {
            toast.error('Failed to save entry');
            console.error(error);
        } finally {
            setIsSaving(false);
        }
    };

    const navigateWeek = (direction: 'prev' | 'next') => {
        const newWeek = direction === 'prev' ? addDays(weekStartDate, -7) : addDays(weekStartDate, 7);

        router.get('/time/timesheet', { week: format(newWeek, 'yyyy-MM-dd') });
    };

    const submitTimesheet = async () => {
        if (!confirm('Are you sure you want to submit this timesheet for approval?')) {
            return;
        }

        try {
            await axios.post(`/time/timesheet/${timesheet.id}/submit`);
            toast.success('Timesheet submitted successfully');
            router.reload();
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to submit timesheet');
        }
    };

    const handleExport = async () => {
        try {
            const response = await axios.get('/api/export/csv', {
                params: {
                    start_date: weekStart,
                    end_date: weekEnd,
                    format: 'detailed',
                    grouping: 'none',
                },
                responseType: 'blob',
            });

            // Create download link
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `timesheet-${weekStart}.csv`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            toast.success('Timesheet exported successfully');
        } catch (error) {
            toast.error('Failed to export timesheet');
            console.error(error);
        }
    };

    const getCellValue = (projectId: number, taskId: number | string, date: string) => {
        const key = `${projectId}-${taskId}-${date}`;
        if (cellValues[key] !== undefined) {
            return cellValues[key];
        }

        const entry = entriesByProjectTask[projectId]?.tasks[taskId]?.entries[date];
        return entry ? entry.hours.toString() : '0';
    };

    const renderCell = (projectId: number, taskId: number | string, date: string) => {
        const key = `${projectId}-${taskId}-${date}`;
        const isEditing = editingCell === key;
        const value = getCellValue(projectId, taskId, date);
        const entry = entriesByProjectTask[projectId]?.tasks[taskId]?.entries[date];
        const isLocked = entry?.locked || !timesheet.is_editable;

        if (isEditing && !isLocked) {
            return (
                <Input
                    type="text"
                    value={value}
                    onChange={(e) => handleCellChange(key, e.target.value)}
                    onBlur={() => handleCellBlur(key)}
                    onKeyDown={(e) => handleKeyDown(e, key)}
                    className="h-8 w-16 p-1 text-center"
                    autoFocus
                />
            );
        }

        return (
            <div
                onClick={() => !isLocked && handleCellClick(key)}
                className={`flex h-8 w-16 cursor-pointer items-center justify-center text-center hover:bg-gray-100 dark:hover:bg-gray-800 ${
                    isLocked ? 'cursor-not-allowed opacity-50' : ''
                } ${parseFloat(value) > 0 ? 'font-medium' : 'text-gray-400'}`}
            >
                {parseFloat(value) > 0 ? value : '-'}
            </div>
        );
    };

    return (
        <AppLayout>
            <Head title="Weekly Timesheet" />

            <div className="container mx-auto py-6">
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <h1 className="text-2xl font-bold">Weekly Timesheet</h1>
                        <Badge variant={timesheet.status === 'draft' ? 'secondary' : 'default'}>{timesheet.status.toUpperCase()}</Badge>
                    </div>

                    <div className="flex items-center gap-2">
                        {timesheet.status === 'draft' && timesheet.is_editable && (
                            <Button onClick={submitTimesheet} variant="default">
                                <Send className="mr-2 h-4 w-4" />
                                Submit for Approval
                            </Button>
                        )}
                        <Button variant="outline" onClick={handleExport}>
                            <Download className="mr-2 h-4 w-4" />
                            Export
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <Button variant="ghost" size="icon" onClick={() => navigateWeek('prev')}>
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>

                                <div className="flex items-center gap-2">
                                    <Calendar className="h-4 w-4" />
                                    <span className="font-medium">
                                        {format(weekStartDate, 'MMM d')} - {format(parseISO(weekEnd), 'MMM d, yyyy')}
                                    </span>
                                </div>

                                <Button variant="ghost" size="icon" onClick={() => navigateWeek('next')}>
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>

                            <div className="text-muted-foreground text-sm">
                                Total: <span className="text-foreground font-medium">{weekTotal}h</span>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full border-collapse">
                                <thead>
                                    <tr className="border-b">
                                        <th className="min-w-[200px] p-2 text-left">Project / Task</th>
                                        {weekDays.map((day) => (
                                            <th key={day.toISOString()} className="min-w-[80px] p-2 text-center">
                                                <div className="text-muted-foreground text-xs">{format(day, 'EEE')}</div>
                                                <div className="text-sm font-medium">{format(day, 'd')}</div>
                                            </th>
                                        ))}
                                        <th className="min-w-[80px] p-2 text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {projects.map((project) => {
                                        const isExpanded = expandedProjects.has(project.id);
                                        const projectData = entriesByProjectTask[project.id];

                                        return (
                                            <React.Fragment key={project.id}>
                                                <tr className="border-b hover:bg-gray-50 dark:hover:bg-gray-900">
                                                    <td className="p-2">
                                                        <div className="flex items-center gap-2">
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-6 w-6"
                                                                onClick={() => toggleProject(project.id)}
                                                            >
                                                                {isExpanded ? <ChevronUp className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
                                                            </Button>
                                                            <span className="font-medium">{project.name}</span>
                                                        </div>
                                                    </td>
                                                    {weekDays.map((day) => {
                                                        const dateStr = format(day, 'yyyy-MM-dd');
                                                        const projectTotal = Object.values(projectData?.tasks || {}).reduce(
                                                            (sum: number, taskData: any) => {
                                                                const entry = taskData.entries[dateStr];
                                                                return sum + (entry ? parseFloat(entry.hours) : 0);
                                                            },
                                                            0,
                                                        );

                                                        return (
                                                            <td key={dateStr} className="p-2 text-center">
                                                                <div className="text-sm font-medium">{projectTotal > 0 ? projectTotal : '-'}</div>
                                                            </td>
                                                        );
                                                    })}
                                                    <td className="p-2 text-center font-medium">
                                                        {/* Project total */}
                                                        {Object.values(projectData?.tasks || {}).reduce((sum: number, taskData: any) => {
                                                            return (
                                                                sum +
                                                                Object.values(taskData.entries).reduce((taskSum: number, entry: any) => {
                                                                    return taskSum + parseFloat(entry.hours);
                                                                }, 0)
                                                            );
                                                        }, 0)}
                                                        h
                                                    </td>
                                                </tr>

                                                {isExpanded &&
                                                    project.tasks.map((task) => (
                                                        <tr
                                                            key={`${project.id}-${task.id}`}
                                                            className="border-b hover:bg-gray-50 dark:hover:bg-gray-900"
                                                        >
                                                            <td className="p-2 pl-10">
                                                                <span className="text-muted-foreground text-sm">{task.name}</span>
                                                            </td>
                                                            {weekDays.map((day) => (
                                                                <td key={day.toISOString()} className="p-2">
                                                                    {renderCell(project.id, task.id, format(day, 'yyyy-MM-dd'))}
                                                                </td>
                                                            ))}
                                                            <td className="p-2 text-center text-sm">
                                                                {/* Task total */}
                                                                {Object.values(projectData?.tasks[task.id]?.entries || {}).reduce(
                                                                    (sum: number, entry: any) => {
                                                                        return sum + parseFloat(entry.hours);
                                                                    },
                                                                    0,
                                                                )}
                                                                h
                                                            </td>
                                                        </tr>
                                                    ))}

                                                {isExpanded && (
                                                    <tr key={`${project.id}-no-task`} className="border-b hover:bg-gray-50 dark:hover:bg-gray-900">
                                                        <td className="p-2 pl-10">
                                                            <span className="text-muted-foreground text-sm italic">No specific task</span>
                                                        </td>
                                                        {weekDays.map((day) => (
                                                            <td key={day.toISOString()} className="p-2">
                                                                {renderCell(project.id, 'no-task', format(day, 'yyyy-MM-dd'))}
                                                            </td>
                                                        ))}
                                                        <td className="p-2 text-center text-sm">
                                                            {/* No task total */}
                                                            {Object.values(projectData?.tasks['no-task']?.entries || {}).reduce(
                                                                (sum: number, entry: any) => {
                                                                    return sum + parseFloat(entry.hours);
                                                                },
                                                                0,
                                                            )}
                                                            h
                                                        </td>
                                                    </tr>
                                                )}
                                            </React.Fragment>
                                        );
                                    })}

                                    <tr className="bg-gray-50 font-medium dark:bg-gray-900">
                                        <td className="p-2">Daily Total</td>
                                        {weekDays.map((day) => {
                                            const dateStr = format(day, 'yyyy-MM-dd');
                                            return (
                                                <td key={dateStr} className="p-2 text-center">
                                                    {dailyTotals[dateStr] || 0}h
                                                </td>
                                            );
                                        })}
                                        <td className="p-2 text-center">{weekTotal}h</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {isSaving && <div className="text-muted-foreground mt-4 text-center text-sm">Saving...</div>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
