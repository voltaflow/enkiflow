import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format, addDays, startOfWeek, isAfter } from 'date-fns';
import { es } from 'date-fns/locale';
import { ChevronLeft, ChevronRight, Send, Copy, Plus, Trash2, Calendar, ChevronDown, ChevronUp } from 'lucide-react';
import { formatDurationHHMM } from '@/lib/time-utils';
import { TimesheetCellEditor } from './TimesheetCellEditor';
import { AddProjectTaskModal } from './AddProjectTaskModal';
import { useTimeEntryStore } from '@/stores/timeEntryStore';
import { toast } from 'sonner';
import axios from '@/lib/axios-config';
import { route } from '@/lib/route-helper';

interface TimeEntry {
    id: number | string;
    project_id: number | null;
    task_id: number | null;
    date: string;
    duration: number;
    description?: string;
    started_at?: string;
    ended_at?: string;
    is_virtual?: boolean;
}

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

interface TimesheetWeekProps {
    weekStart: Date;
    entries: TimeEntry[];
    projects: Project[];
    tasks: Task[];
    isLocked: boolean;
    weeklyGoal?: number;
    onCellUpdate: (projectId: number | null, taskId: number | null, date: string, hours: number, description?: string) => void;
    onWeekChange: (newWeekStart: Date) => void;
    onSubmit: () => void;
    onDayClick?: (date: Date) => void;
    onDeleteEntry?: (entryId: number) => void;
    onDeleteRow?: (projectId: number, taskId: number | null) => void;
}

export function TimesheetWeek({
    weekStart,
    entries,
    projects,
    tasks,
    isLocked,
    weeklyGoal = 40,
    onCellUpdate,
    onWeekChange,
    onSubmit,
    onDayClick,
    onDeleteEntry,
    onDeleteRow
}: TimesheetWeekProps) {
    const [editingCell, setEditingCell] = useState<string | null>(null);
    const [isCopyingRows, setIsCopyingRows] = useState(false);
    const [showAddProjectTaskModal, setShowAddProjectTaskModal] = useState(false);
    const [expandedProjects, setExpandedProjects] = useState<Set<number>>(new Set());
    const store = useTimeEntryStore();

    // Generate array of dates for the week
    const weekDates = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));

    // Check if we're already viewing the current week or beyond
    const today = new Date();
    const currentWeekStart = startOfWeek(today, { weekStartsOn: 1 }); // Monday as start of week
    const nextWeekStart = addDays(weekStart, 7);
    // Disable next button only if we're already viewing the current week
    const isNextWeekDisabled = weekStart.getTime() >= currentWeekStart.getTime();

    // Group entries by project/task and date (keep all necessary data)
    const entriesMap = entries.reduce((acc, entry) => {
        const key = `${entry.project_id}-${entry.task_id || 0}-${entry.date}`;
        acc[key] = {
            id: typeof entry.id === 'string' ? parseInt(entry.id, 10) : entry.id,
            duration: entry.duration, // Keep in seconds
            description: entry.description || '',
            project_id: entry.project_id,
            task_id: entry.task_id,
            date: entry.date
        };
        return acc;
    }, {} as Record<string, { id: number; duration: number; description: string; project_id: number | null; task_id: number | null; date: string }>);

    // Group entries by project and their tasks
    interface ProjectGroup {
        project: Project;
        tasks: (Task | null)[];
        projectTotal: number;
    }

    const projectGroups: ProjectGroup[] = [];
    const projectMap = new Map<number, { tasks: Set<number | null>, total: number }>();

    // First pass: collect all project-task combinations and calculate totals
    entries.forEach(entry => {
        if (!entry.project_id) return;
        
        if (!projectMap.has(entry.project_id)) {
            projectMap.set(entry.project_id, { tasks: new Set(), total: 0 });
        }
        
        const projectData = projectMap.get(entry.project_id)!;
        projectData.tasks.add(entry.task_id);
        projectData.total += entry.duration;
    });

    // Second pass: build the project groups
    projectMap.forEach((data, projectId) => {
        const project = projects.find(p => p.id === projectId);
        if (!project) return;

        const projectTasks: (Task | null)[] = [];
        data.tasks.forEach(taskId => {
            if (taskId === null) {
                projectTasks.push(null);
            } else {
                const task = tasks.find(t => t.id === taskId);
                if (task) projectTasks.push(task);
            }
        });

        projectGroups.push({
            project,
            tasks: projectTasks.sort((a, b) => {
                // Put "no task" entries at the end
                if (!a) return 1;
                if (!b) return -1;
                return a.title.localeCompare(b.title);
            }),
            projectTotal: data.total
        });
    });

    // Calculate totals (in seconds)
    const dailyTotals = weekDates.map(date => {
        const dateStr = format(date, 'yyyy-MM-dd');
        return Object.entries(entriesMap)
            .filter(([key]) => key.endsWith(dateStr))
            .reduce((sum, [, data]) => sum + data.duration, 0);
    });

    // Calculate project totals for each day
    const getProjectDayTotal = (projectId: number, date: Date): number => {
        const dateStr = format(date, 'yyyy-MM-dd');
        return entries
            .filter(e => e.project_id === projectId && e.date === dateStr)
            .reduce((sum, e) => sum + e.duration, 0);
    };

    const weekTotalSeconds = dailyTotals.reduce((sum, seconds) => sum + seconds, 0);
    const weekTotalHours = weekTotalSeconds / 3600; // For weekly goal comparison

    const handleCellSave = (projectId: number | null, taskId: number | null, date: Date, duration: number, description: string) => {
        const dateStr = format(date, 'yyyy-MM-dd');
        const hours = duration / 3600; // Convert to hours for the callback

        onCellUpdate(projectId, taskId, dateStr, hours, description);
        setEditingCell(null);
    };

    const formatDuration = (seconds: number) => {
        if (seconds === 0) return '-';
        return formatDurationHHMM(seconds);
    };

    // Check if the week has any entries
    const hasEntries = entries.length > 0;

    // Handle deleting an entire row
    const handleDeleteRow = (projectId: number, taskId: number | null) => {
        if (onDeleteRow) {
            onDeleteRow(projectId, taskId);
        }
    };

    // Toggle project expansion
    const toggleProjectExpanded = (projectId: number) => {
        const newExpanded = new Set(expandedProjects);
        if (newExpanded.has(projectId)) {
            newExpanded.delete(projectId);
        } else {
            newExpanded.add(projectId);
        }
        setExpandedProjects(newExpanded);
    };

    const handleCopyRowsFromPreviousWeek = async () => {
        setIsCopyingRows(true);
        try {
            await store.copyRowsFromPreviousWeek(format(weekStart, 'yyyy-MM-dd'));

            // The backend creates entries directly, so we just need to reload
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } catch (error) {
            // Error handling is done in the store
        } finally {
            setIsCopyingRows(false);
        }
    };

    return (
        <Card className="w-full shadow-sm">
            <CardHeader className="pb-2 min-h-[72px]">
                <div className="flex items-center justify-between h-[40px]">
                    <div className="flex items-center gap-3">
                        <div className="flex items-center">
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => onWeekChange(addDays(weekStart, -7))}
                                className="h-8 w-8 rounded-r-none"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => onWeekChange(addDays(weekStart, 7))}
                                disabled={isNextWeekDisabled}
                                title={isNextWeekDisabled ? 'Ya estás en la semana actual' : 'Siguiente semana'}
                                className="h-8 w-8 rounded-l-none border-l-0"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                        
                        <div className="flex items-center gap-2">
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            <h2 className="text-lg font-semibold">
                                {format(weekStart, 'd \'de\' MMM', { locale: es })} - {format(addDays(weekStart, 6), 'd \'de\' MMM, yyyy', { locale: es })}
                            </h2>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="text-sm">
                            <span className="text-muted-foreground">Total:</span>{' '}
                            <span className="font-semibold text-foreground">{formatDurationHHMM(weekTotalSeconds)}</span>
                            <span className="text-muted-foreground"> / {weeklyGoal}h</span>
                        </div>
                        {weekTotalHours >= weeklyGoal && (
                            <Badge variant="default" className="bg-green-600 hover:bg-green-700">
                                Meta alcanzada
                            </Badge>
                        )}
                    </div>
                </div>
            </CardHeader>

            <CardContent className="p-6 pt-2 space-y-4">
                {/* Add Project/Task Button */}
                {!isLocked && (
                    <Button
                        onClick={() => setShowAddProjectTaskModal(true)}
                        className="w-full"
                        variant="outline"
                    >
                        <Plus className="h-4 w-4 mr-2" />
                        Agregar proyecto/tarea
                    </Button>
                )}
                {!hasEntries && (
                    <div className="mb-6 p-6 border-2 border-dashed rounded-lg bg-muted/20 text-center">
                        <p className="text-muted-foreground mb-4 text-sm">
                            No hay entradas de tiempo para esta semana
                        </p>
                        <Button
                            onClick={handleCopyRowsFromPreviousWeek}
                            disabled={isCopyingRows}
                            variant="outline"
                            size="sm"
                        >
                            <Copy className="h-4 w-4 mr-2" />
                            {isCopyingRows ? 'Copiando...' : 'Copiar filas de la hoja más reciente'}
                        </Button>
                    </div>
                )}

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full border-collapse">
                        <thead>
                            <tr className="border-b bg-muted/30">
                                <th className="sticky left-0 bg-muted/30 min-w-[250px] p-3 text-left font-medium">
                                    Proyecto / Tarea
                                </th>
                                {weekDates.map((date, index) => (
                                    <th
                                        key={index}
                                        className={`min-w-[90px] p-3 text-center ${onDayClick ? 'cursor-pointer hover:bg-muted/50' : ''}`}
                                        onClick={() => onDayClick && onDayClick(date)}
                                    >
                                        <div className="text-xs text-muted-foreground uppercase">
                                            {format(date, 'EEE', { locale: es })}
                                        </div>
                                        <div className="text-sm font-semibold">
                                            {format(date, 'd')}
                                        </div>
                                    </th>
                                ))}
                                <th className="min-w-[90px] p-3 text-center font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {projectGroups.map((group) => {
                                const isExpanded = expandedProjects.has(group.project.id);
                                const hasMultipleTasks = group.tasks.length > 1;
                                
                                return (
                                    <React.Fragment key={group.project.id}>
                                        {/* Project row */}
                                        <tr className="border-b hover:bg-muted/20 transition-colors group h-[56px]">
                                            <td className="sticky left-0 bg-background p-3">
                                                <div className="flex items-center gap-2 h-8">
                                                    {hasMultipleTasks && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-6 w-6 p-0"
                                                            onClick={() => toggleProjectExpanded(group.project.id)}
                                                        >
                                                            {isExpanded ? (
                                                                <ChevronUp className="h-4 w-4" />
                                                            ) : (
                                                                <ChevronDown className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                    )}
                                                    {!hasMultipleTasks && <div className="w-6" />}
                                                    {group.project.color && (
                                                        <div
                                                            className="w-2 h-8 rounded-sm flex-shrink-0"
                                                            style={{ backgroundColor: group.project.color }}
                                                        />
                                                    )}
                                                    <div className="min-w-0">
                                                        <div className="font-medium text-sm">{group.project.name}</div>
                                                        {!hasMultipleTasks && group.tasks[0] && (
                                                            <div className="text-xs text-muted-foreground truncate">
                                                                {group.tasks[0].title}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>
                                            {/* Project cells */}
                                            {weekDates.map((date) => {
                                                const dateStr = format(date, 'yyyy-MM-dd');
                                                
                                                // For single task projects, allow direct editing on the project row
                                                if (!hasMultipleTasks && group.tasks.length === 1) {
                                                    const task = group.tasks[0];
                                                    const cellKey = `${group.project.id}-${task?.id || 0}-${dateStr}`;
                                                    const entry = entries.find(e =>
                                                        e.project_id === group.project.id &&
                                                        e.task_id === (task?.id || null) &&
                                                        e.date === dateStr
                                                    );
                                                    const entryData = entriesMap[cellKey] || { duration: 0, description: '' };
                                                    const isEditing = editingCell === cellKey;
                                                    const hasExistingEntry = !!entry && !entry.is_virtual;
                                                    const isVirtual = entry?.is_virtual || false;

                                                    return (
                                                        <td key={date.toISOString()} className="p-2 text-center">
                                                            <TimesheetCellEditor
                                                                isOpen={isEditing}
                                                                onOpenChange={(open) => {
                                                                    if (open && !isLocked) {
                                                                        setEditingCell(cellKey);
                                                                    } else {
                                                                        setEditingCell(null);
                                                                    }
                                                                }}
                                                                duration={entryData.duration}
                                                                description={entryData.description}
                                                                onSave={(duration, description) =>
                                                                    handleCellSave(group.project.id, task?.id || null, date, duration, description)
                                                                }
                                                                onDelete={hasExistingEntry && !isVirtual && entryData.id && onDeleteEntry ? () => onDeleteEntry(entryData.id) : undefined}
                                                                hasExistingEntry={hasExistingEntry && !isVirtual}
                                                            >
                                                                <div
                                                                    className={`
                                                                        flex h-9 w-full items-center justify-center
                                                                        rounded-md transition-all cursor-pointer
                                                                        ${isLocked ? 'cursor-not-allowed opacity-60' : 'hover:bg-muted/50'}
                                                                        ${hasExistingEntry && !isVirtual ? 'bg-primary/5' : ''}
                                                                        ${entryData.duration > 0 ? 'font-medium text-foreground' : 'text-muted-foreground'}
                                                                    `}
                                                                >
                                                                    <span className="text-sm">
                                                                        {formatDuration(entryData.duration)}
                                                                    </span>
                                                                </div>
                                                            </TimesheetCellEditor>
                                                        </td>
                                                    );
                                                }
                                                
                                                // For multi-task projects, just show the total
                                                const dayTotal = getProjectDayTotal(group.project.id, date);
                                                return (
                                                    <td key={date.toISOString()} className="p-2 text-center">
                                                        <div className="flex h-9 w-full items-center justify-center">
                                                            <span className={`text-sm ${dayTotal > 0 ? 'font-medium' : 'text-muted-foreground'}`}>
                                                                {formatDuration(dayTotal)}
                                                            </span>
                                                        </div>
                                                    </td>
                                                );
                                            })}
                                            <td className="p-3 text-center">
                                                <div className="flex items-center justify-center gap-2">
                                                    <span className="font-semibold text-sm">{formatDuration(group.projectTotal)}</span>
                                                    {!isLocked && onDeleteRow && !hasMultipleTasks && group.tasks.length === 1 && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-6 w-6 text-destructive hover:text-destructive/90 opacity-0 group-hover:opacity-100 transition-opacity"
                                                            onClick={() => handleDeleteRow(group.project.id, group.tasks[0]?.id || null)}
                                                            title="Eliminar todas las entradas de esta fila"
                                                        >
                                                            <Trash2 className="h-3 w-3" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        {/* Task rows - only show if expanded and has multiple tasks */}
                                        {isExpanded && hasMultipleTasks && group.tasks.map((task) => {
                                            const taskKey = task ? `task-${task.id}` : 'no-task';
                                            return (
                                                <tr key={taskKey} className="border-b hover:bg-muted/10 transition-all duration-200 group h-[48px]">
                                                    <td className="sticky left-0 bg-background p-3 pl-14 h-[48px]">
                                                        <div className="text-sm text-muted-foreground">
                                                            {task ? task.title : <span className="italic">Sin tarea específica</span>}
                                                        </div>
                                                    </td>
                                                    {weekDates.map((date, dateIndex) => {
                                                        const dateStr = format(date, 'yyyy-MM-dd');
                                                        const cellKey = `${group.project.id}-${task?.id || 0}-${dateStr}`;
                                                        const entry = entries.find(e =>
                                                            e.project_id === group.project.id &&
                                                            e.task_id === (task?.id || null) &&
                                                            e.date === dateStr
                                                        );
                                                        const entryData = entriesMap[cellKey] || { duration: 0, description: '' };
                                                        const isEditing = editingCell === cellKey;
                                                        const hasExistingEntry = !!entry && !entry.is_virtual;
                                                        const isVirtual = entry?.is_virtual || false;

                                                        return (
                                                            <td key={dateIndex} className="p-2 text-center">
                                                                <TimesheetCellEditor
                                                                    isOpen={isEditing}
                                                                    onOpenChange={(open) => {
                                                                        if (open && !isLocked) {
                                                                            setEditingCell(cellKey);
                                                                        } else {
                                                                            setEditingCell(null);
                                                                        }
                                                                    }}
                                                                    duration={entryData.duration}
                                                                    description={entryData.description}
                                                                    onSave={(duration, description) =>
                                                                        handleCellSave(group.project.id, task?.id || null, date, duration, description)
                                                                    }
                                                                    onDelete={hasExistingEntry && !isVirtual && entryData.id && onDeleteEntry ? () => onDeleteEntry(entryData.id) : undefined}
                                                                    hasExistingEntry={hasExistingEntry && !isVirtual}
                                                                >
                                                                    <div
                                                                        className={`
                                                                            flex h-9 w-full items-center justify-center
                                                                            rounded-md transition-all cursor-pointer
                                                                            ${isLocked ? 'cursor-not-allowed opacity-60' : 'hover:bg-muted/50'}
                                                                            ${hasExistingEntry && !isVirtual ? 'bg-primary/5' : ''}
                                                                            ${entryData.duration > 0 ? 'font-medium text-foreground' : 'text-muted-foreground'}
                                                                        `}
                                                                    >
                                                                        <span className="text-sm">
                                                                            {formatDuration(entryData.duration)}
                                                                        </span>
                                                                    </div>
                                                                </TimesheetCellEditor>
                                                            </td>
                                                        );
                                                    })}
                                                    <td className="p-3 text-center">
                                                        <div className="flex items-center justify-center gap-2">
                                                            <span className="text-sm">
                                                                {formatDuration(
                                                                    weekDates.reduce((sum, date) => {
                                                                        const dateStr = format(date, 'yyyy-MM-dd');
                                                                        const key = `${group.project.id}-${task?.id || 0}-${dateStr}`;
                                                                        return sum + (entriesMap[key]?.duration || 0);
                                                                    }, 0)
                                                                )}
                                                            </span>
                                                            {!isLocked && onDeleteRow && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-6 w-6 text-destructive hover:text-destructive/90 opacity-0 group-hover:opacity-100 transition-opacity"
                                                                    onClick={() => handleDeleteRow(group.project.id, task?.id || null)}
                                                                    title="Eliminar todas las entradas de esta fila"
                                                                >
                                                                    <Trash2 className="h-3 w-3" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </React.Fragment>
                                );
                            })}
                            {/* Daily totals row */}
                            <tr className="border-t-2 bg-muted/30">
                                <td className="sticky left-0 bg-muted/30 p-3 font-semibold">
                                    Total por día
                                </td>
                                {dailyTotals.map((total, index) => (
                                    <td key={index} className="p-3 text-center">
                                        <span className="font-semibold text-sm">{formatDuration(total)}</span>
                                    </td>
                                ))}
                                <td className="p-3 text-center">
                                    <span className="font-bold text-sm">{formatDurationHHMM(weekTotalSeconds)}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>

            {/* Add Project/Task Modal */}
            <AddProjectTaskModal
                isOpen={showAddProjectTaskModal}
                onClose={() => setShowAddProjectTaskModal(false)}
                projects={projects}
                tasks={tasks}
                existingProjectTaskCombinations={(() => {
                    const combinations = new Set<string>();
                    projectGroups.forEach(group => {
                        group.tasks.forEach(task => {
                            combinations.add(`${group.project.id}-${task?.id || 0}`);
                        });
                    });
                    return combinations;
                })()}
                onSubmit={async (data) => {
                    try {
                        // Call the endpoint to validate the combination
                        const response = await axios.post(route('tenant.time.add-week-row'), {
                            week_start: format(weekStart, 'yyyy-MM-dd'),
                            project_id: data.project_id,
                            task_id: data.task_id,
                        });

                        if (response.data.message) {
                            toast.success(response.data.message);
                        }

                        // Close modal after successful validation
                        setShowAddProjectTaskModal(false);

                        // Instead of reloading, we'll add the row to the UI
                        // The parent component should handle adding the virtual row
                        // For now, we'll still reload but this should be improved
                        // to update the state directly
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } catch (error: any) {
                        if (error.response?.data?.message) {
                            toast.error(error.response.data.message);
                        } else {
                            toast.error('Error al agregar proyecto/tarea');
                        }
                        throw error; // Re-throw to let the modal handle the error
                    }
                }}
            />
        </Card>
    );
}
