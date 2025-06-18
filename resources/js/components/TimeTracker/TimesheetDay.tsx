import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Plus, Copy, MoreVertical, Trash2, Edit } from 'lucide-react';

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
    onDuplicateDay
}: TimesheetDayProps) {
    const formatDuration = (seconds: number) => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        return `${hours}h ${minutes}m`;
    };

    const formatTime = (dateString: string) => {
        return format(new Date(dateString), 'HH:mm');
    };

    const totalHours = entries.reduce((sum, entry) => sum + (entry.duration || 0), 0) / 3600;

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
                    <CardTitle className="text-xl">
                        {format(date, 'EEEE, d \'de\' MMMM, yyyy', { locale: es })}
                    </CardTitle>
                    <div className="flex items-center gap-4">
                        <div className="text-sm text-muted-foreground">
                            Total: <span className="font-semibold">{totalHours.toFixed(1)}/{dailyGoal}h</span>
                        </div>
                        {totalHours >= dailyGoal && (
                            <Badge variant="default" className="bg-green-500">
                                Meta alcanzada
                            </Badge>
                        )}
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
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Proyecto</TableHead>
                                <TableHead>Tarea</TableHead>
                                <TableHead>Descripción</TableHead>
                                <TableHead>Tiempo</TableHead>
                                <TableHead className="w-[100px]">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries.map((entry) => (
                                <TableRow key={entry.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            {entry.project?.color && (
                                                <div
                                                    className="w-3 h-3 rounded-full"
                                                    style={{ backgroundColor: entry.project.color }}
                                                />
                                            )}
                                            <span className="font-medium">
                                                {entry.project?.name || 'Sin proyecto'}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {entry.task?.title || '-'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <span>{entry.description}</span>
                                            {entry.is_billable && (
                                                <Badge variant="outline" className="text-xs">
                                                    Facturable
                                                </Badge>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="text-sm">
                                            <div>{formatDuration(entry.duration || 0)}</div>
                                            <div className="text-muted-foreground text-xs">
                                                {formatTime(entry.started_at)} - {entry.stopped_at ? formatTime(entry.stopped_at) : 'En curso'}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
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
                                                        onClick={() => onDeleteEntry(entry.id)}
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
                {!isLocked && (
                    <Button
                        onClick={onDuplicateDay}
                        variant="outline"
                        className="w-full"
                    >
                        <Copy className="h-4 w-4 mr-2" />
                        Duplicar día anterior
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}