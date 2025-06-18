import React, { useState, useRef, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format, startOfWeek, addDays } from 'date-fns';
import { es } from 'date-fns/locale';
import { ChevronLeft, ChevronRight, Send } from 'lucide-react';

interface TimeEntry {
    id: number;
    project_id: number;
    task_id: number | null;
    date: string;
    duration: number;
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
    onCellUpdate: (projectId: number, taskId: number | null, date: string, hours: number) => void;
    onWeekChange: (newWeekStart: Date) => void;
    onSubmit: () => void;
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
    onSubmit
}: TimesheetWeekProps) {
    const [editingCell, setEditingCell] = useState<string | null>(null);
    const [tempValue, setTempValue] = useState('');
    const inputRef = useRef<HTMLInputElement>(null);

    // Generate array of dates for the week
    const weekDates = Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));

    // Group entries by project/task and date
    const entriesMap = entries.reduce((acc, entry) => {
        const key = `${entry.project_id}-${entry.task_id || 0}-${entry.date}`;
        acc[key] = entry.duration / 3600; // Convert to hours
        return acc;
    }, {} as Record<string, number>);

    // Get unique project/task combinations
    const projectTaskRows = projects.flatMap(project => {
        const projectTasks = tasks.filter(task => task.project_id === project.id);
        if (projectTasks.length === 0) {
            return [{ project, task: null }];
        }
        return projectTasks.map(task => ({ project, task }));
    });

    // Calculate totals
    const dailyTotals = weekDates.map(date => {
        const dateStr = format(date, 'yyyy-MM-dd');
        return Object.entries(entriesMap)
            .filter(([key]) => key.endsWith(dateStr))
            .reduce((sum, [, hours]) => sum + hours, 0);
    });

    const projectTotals = projectTaskRows.map(row => {
        return weekDates.reduce((sum, date) => {
            const dateStr = format(date, 'yyyy-MM-dd');
            const key = `${row.project.id}-${row.task?.id || 0}-${dateStr}`;
            return sum + (entriesMap[key] || 0);
        }, 0);
    });

    const weekTotal = dailyTotals.reduce((sum, hours) => sum + hours, 0);

    const handleCellClick = (projectId: number, taskId: number | null, date: Date) => {
        if (isLocked) return;

        const dateStr = format(date, 'yyyy-MM-dd');
        const key = `${projectId}-${taskId || 0}-${dateStr}`;
        const currentValue = entriesMap[key] || 0;

        setEditingCell(key);
        setTempValue(currentValue.toString());
    };

    const handleCellBlur = () => {
        if (!editingCell) return;

        const [projectId, taskId, dateStr] = editingCell.split('-');
        const hours = parseFloat(tempValue) || 0;

        onCellUpdate(
            parseInt(projectId),
            taskId === '0' ? null : parseInt(taskId),
            dateStr,
            hours
        );

        setEditingCell(null);
        setTempValue('');
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleCellBlur();
        } else if (e.key === 'Escape') {
            setEditingCell(null);
            setTempValue('');
        }
    };

    useEffect(() => {
        if (editingCell && inputRef.current) {
            inputRef.current.focus();
            inputRef.current.select();
        }
    }, [editingCell]);

    const formatHours = (hours: number) => {
        if (hours === 0) return '-';
        return hours.toFixed(1);
    };

    return (
        <Card className="w-full">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => onWeekChange(addDays(weekStart, -7))}
                        >
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <CardTitle className="text-xl">
                            Semana: {format(weekStart, 'd \'de\' MMM', { locale: es })} - 
                            {format(addDays(weekStart, 6), 'd \'de\' MMM, yyyy', { locale: es })}
                        </CardTitle>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => onWeekChange(addDays(weekStart, 7))}
                        >
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="text-sm text-muted-foreground">
                            Total: <span className="font-semibold">{weekTotal.toFixed(1)}/{weeklyGoal}h</span>
                        </div>
                        {weekTotal >= weeklyGoal && (
                            <Badge variant="default" className="bg-green-500">
                                Meta alcanzada
                            </Badge>
                        )}
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="sticky left-0 bg-background min-w-[200px]">
                                    Proyecto/Tarea
                                </TableHead>
                                {weekDates.map((date, index) => (
                                    <TableHead key={index} className="text-center min-w-[80px]">
                                        <div>
                                            <div className="font-medium">
                                                {format(date, 'EEE', { locale: es })}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {format(date, 'd/MM')}
                                            </div>
                                        </div>
                                    </TableHead>
                                ))}
                                <TableHead className="text-center min-w-[80px]">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {projectTaskRows.map((row, rowIndex) => {
                                const rowKey = `${row.project.id}-${row.task?.id || 0}`;
                                return (
                                    <TableRow key={rowKey}>
                                        <TableCell className="sticky left-0 bg-background">
                                            <div className="flex items-center gap-2">
                                                {row.project.color && (
                                                    <div
                                                        className="w-3 h-3 rounded-full flex-shrink-0"
                                                        style={{ backgroundColor: row.project.color }}
                                                    />
                                                )}
                                                <div>
                                                    <div className="font-medium">{row.project.name}</div>
                                                    {row.task && (
                                                        <div className="text-sm text-muted-foreground">
                                                            {row.task.title}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </TableCell>
                                        {weekDates.map((date, dateIndex) => {
                                            const dateStr = format(date, 'yyyy-MM-dd');
                                            const cellKey = `${row.project.id}-${row.task?.id || 0}-${dateStr}`;
                                            const hours = entriesMap[cellKey] || 0;
                                            const isEditing = editingCell === cellKey;

                                            return (
                                                <TableCell
                                                    key={dateIndex}
                                                    className="text-center p-1 cursor-pointer hover:bg-muted/50"
                                                    onClick={() => handleCellClick(row.project.id, row.task?.id || null, date)}
                                                >
                                                    {isEditing ? (
                                                        <Input
                                                            ref={inputRef}
                                                            type="number"
                                                            step="0.1"
                                                            min="0"
                                                            max="24"
                                                            value={tempValue}
                                                            onChange={(e) => setTempValue(e.target.value)}
                                                            onBlur={handleCellBlur}
                                                            onKeyDown={handleKeyDown}
                                                            className="h-8 text-center"
                                                            onClick={(e) => e.stopPropagation()}
                                                        />
                                                    ) : (
                                                        <span className={hours > 0 ? 'font-medium' : 'text-muted-foreground'}>
                                                            {formatHours(hours)}
                                                        </span>
                                                    )}
                                                </TableCell>
                                            );
                                        })}
                                        <TableCell className="text-center font-medium">
                                            {formatHours(projectTotals[rowIndex])}
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                            {/* Daily totals row */}
                            <TableRow className="border-t-2">
                                <TableCell className="sticky left-0 bg-background font-semibold">
                                    Total por día
                                </TableCell>
                                {dailyTotals.map((total, index) => (
                                    <TableCell key={index} className="text-center font-semibold">
                                        {formatHours(total)}
                                    </TableCell>
                                ))}
                                <TableCell className="text-center font-bold">
                                    {formatHours(weekTotal)}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                {!isLocked && (
                    <Button
                        onClick={onSubmit}
                        className="w-full mt-4"
                        size="lg"
                    >
                        <Send className="h-4 w-4 mr-2" />
                        Enviar semana para aprobación
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}