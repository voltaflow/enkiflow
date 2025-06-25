import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { format, addDays } from 'date-fns';
import { es } from 'date-fns/locale';
import { Plus, Copy, MoreVertical, Trash2, Edit, ChevronLeft, ChevronRight } from 'lucide-react';
import { formatDurationHHMM } from '@/lib/time-utils';

interface TimeEntry {
    id: number;
    description: string;
    started_at: string;
    stopped_at: string | null;
    duration: number;
    is_billable: boolean;
    task_id: number | null;
    project_id: number | null;
    task?: {
        id: number;
        title: string;
    };
    project?: {
        id: number;
        name: string;
        color?: string;
    };
}

interface Project {
    id: number;
    name: string;
    color?: string;
}

interface TimesheetDayProps {
    date: Date;
    entries: TimeEntry[];
    projects: Project[];
    isLocked: boolean;
    dailyGoal?: number;
    onAddTime: () => void;
    onEditEntry: (entry: TimeEntry) => void;
    onDeleteEntry: (entryId: number) => void;
    onDuplicateDay: () => void;
    isDuplicating?: boolean;
    onDateChange: (date: Date) => void;
}

export function TimesheetDay({
    date,
    entries,
    projects,
    isLocked,
    dailyGoal = 8,
    onAddTime,
    onEditEntry,
    onDeleteEntry,
    onDuplicateDay,
    isDuplicating = false,
    onDateChange
}: TimesheetDayProps) {
    const formatDuration = (seconds: number) => {
        return formatDurationHHMM(seconds);
    };

    const formatTime = (dateString: string) => {
        return format(new Date(dateString), 'HH:mm');
    };

    const totalSeconds = entries.reduce((sum, entry) => sum + (entry.duration || 0), 0);
    const totalHours = totalSeconds / 3600;

    // Group entries by project
    const entriesByProject = entries.reduce((acc, entry) => {
        const projectId = entry.project_id || 0;
        if (!acc[projectId]) {
            acc[projectId] = {
                project: entry.project || null,
                entries: [],
                totalDuration: 0
            };
        }
        acc[projectId].entries.push(entry);
        acc[projectId].totalDuration += entry.duration || 0;
        return acc;
    }, {} as Record<number, { project: any; entries: TimeEntry[]; totalDuration: number }>);

    return (
        <Card className="w-full">
            <CardHeader className="pb-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex items-center">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8 rounded-r-none"
                                onClick={() => onDateChange(addDays(date, -1))}
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8 rounded-l-none border-l-0"
                                onClick={() => onDateChange(addDays(date, 1))}
                                disabled={date.toDateString() === new Date().toDateString()}
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                        <CardTitle className="text-lg">
                            {format(date, 'EEEE, d \'de\' MMMM, yyyy', { locale: es })}
                        </CardTitle>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="text-sm text-muted-foreground">
                            Total: <span className="font-semibold">{formatDurationHHMM(totalSeconds)} / {dailyGoal}h</span>
                        </div>
                        {totalHours >= dailyGoal && (
                            <Badge variant="default" className="bg-green-500">
                                Meta alcanzada
                            </Badge>
                        )}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => onDateChange(new Date())}
                        >
                            Hoy
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <CardContent className="space-y-4">
                {/* Add Time Button */}
                {!isLocked && (
                    <Button
                        onClick={onAddTime}
                        className="w-full"
                        variant="outline"
                    >
                        <Plus className="h-4 w-4 mr-2" />
                        Añadir tiempo
                    </Button>
                )}

                {/* Entries Table */}
                {entries.length > 0 ? (
                    <Table className="table-fixed">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-[180px]">Proyecto</TableHead>
                                <TableHead className="w-[180px]">Tarea</TableHead>
                                <TableHead className="w-[400px]">Descripción</TableHead>
                                <TableHead className="w-[120px]">Tiempo</TableHead>
                                <TableHead className="w-[80px] text-center">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries.map((entry) => (
                                <TableRow key={entry.id}>
                                    <TableCell className="w-[180px]">
                                        <div className="flex items-center gap-2 max-w-[180px]">
                                            {entry.project?.color && (
                                                <div
                                                    className="w-3 h-3 rounded-full flex-shrink-0"
                                                    style={{ backgroundColor: entry.project.color }}
                                                />
                                            )}
                                            <span className="font-medium truncate">
                                                {entry.project?.name || 'Sin proyecto'}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="w-[180px]">
                                        <div className="truncate">
                                            {entry.task?.title || '-'}
                                        </div>
                                    </TableCell>
                                    <TableCell className="w-[400px]">
                                        <div className="space-y-1 max-w-[400px]">
                                            <p className="text-sm break-words whitespace-pre-wrap line-clamp-3">
                                                {entry.description}
                                            </p>
                                            {entry.is_billable && (
                                                <Badge variant="outline" className="text-xs">
                                                    Facturable
                                                </Badge>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="w-[120px]">
                                        <div className="text-sm">
                                            <div className="font-medium">{formatDuration(entry.duration || 0)}</div>
                                            <div className="text-muted-foreground text-xs">
                                                {formatTime(entry.started_at)} - {entry.stopped_at ? formatTime(entry.stopped_at) : 'En curso'}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="w-[80px]">
                                        {!isLocked && (
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon">
                                                        <MoreVertical className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem onClick={() => onEditEntry(entry)}>
                                                        <Edit className="h-4 w-4 mr-2" />
                                                        Editar
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() => {
                                                            onDeleteEntry(entry.id);
                                                        }}
                                                        className="text-destructive"
                                                    >
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        Eliminar
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                ) : (
                    <div className="text-center py-8 text-muted-foreground">
                        No hay entradas de tiempo para este día
                    </div>
                )}

                {/* Project Summary */}
                {entries.length > 0 && (
                    <div className="border-t pt-4 space-y-2">
                        <h4 className="text-sm font-semibold text-muted-foreground">Resumen por proyecto</h4>
                        {Object.entries(entriesByProject).map(([projectId, data]) => (
                            <div key={projectId} className="flex items-center justify-between text-sm">
                                <div className="flex items-center gap-2">
                                    {data.project?.color && (
                                        <div
                                            className="w-3 h-3 rounded-full"
                                            style={{ backgroundColor: data.project.color }}
                                        />
                                    )}
                                    <span>{data.project?.name || 'Sin proyecto'}</span>
                                </div>
                                <span className="font-medium">
                                    {formatDuration(data.totalDuration)}
                                </span>
                            </div>
                        ))}
                    </div>
                )}

                {/* Duplicate Day Button */}
                {!isLocked && entries.length === 0 && (
                    <Button
                        onClick={() => {
                            if (!isDuplicating) {
                                onDuplicateDay();
                            }
                        }}
                        variant="outline"
                        className="w-full"
                        disabled={isDuplicating}
                    >
                        <Copy className="h-4 w-4 mr-2" />
                        {isDuplicating ? 'Duplicando...' : 'Duplicar día más reciente'}
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}