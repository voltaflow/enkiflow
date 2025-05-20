import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Heading } from '@/components/heading';
import { Timer } from '@/components/time-tracking/timer';
import { TimeEntryForm } from '@/components/time-tracking/time-entry-form';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Head, Link, router } from '@inertiajs/react';
import { Calendar, MoreVertical, Plus, Clock, Calendar as CalendarIcon, PieChart } from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';
import { format, parseISO, differenceInSeconds } from 'date-fns';
import { es } from 'date-fns/locale';

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

interface Project {
  id: number;
  name: string;
}

interface Task {
  id: number;
  title: string;
  project_id: number;
  project: Project;
}

interface TimeCategory {
  id: number;
  name: string;
  color: string;
  billable_default: boolean;
}

interface Props {
  timeEntries: TimeEntry[];
  runningEntry: TimeEntry | null;
  projects: Project[];
  tasks: Task[];
  categories: TimeCategory[];
  dateFilter: string;
}

export default function TimeTrackingIndex({ 
  timeEntries: initialTimeEntries,
  runningEntry: initialRunningEntry,
  projects,
  tasks,
  categories,
  dateFilter = 'today'
}: Props) {
  const [timeEntries, setTimeEntries] = useState<TimeEntry[]>(initialTimeEntries);
  const [runningEntry, setRunningEntry] = useState<TimeEntry | null>(initialRunningEntry);
  const [showNewEntryDialog, setShowNewEntryDialog] = useState(false);
  const [currentTab, setCurrentTab] = useState(dateFilter);
  
  // Update running timer every second
  useEffect(() => {
    if (!runningEntry) return;
    
    const interval = setInterval(() => {
      const updatedEntries = timeEntries.map(entry => {
        if (entry.id === runningEntry.id) {
          const durationInSeconds = differenceInSeconds(
            new Date(),
            parseISO(entry.started_at)
          );
          const hours = Math.floor(durationInSeconds / 3600);
          const minutes = Math.floor((durationInSeconds % 3600) / 60);
          const seconds = durationInSeconds % 60;
          
          return {
            ...entry,
            duration: durationInSeconds,
            formatted_duration: `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
          };
        }
        return entry;
      });
      
      setTimeEntries(updatedEntries);
    }, 1000);
    
    return () => clearInterval(interval);
  }, [runningEntry, timeEntries]);

  const handleStartTimer = async (data: Partial<TimeEntry>) => {
    try {
      const response = await axios.post(route('tenant.time.start'), data);
      const newEntry = response.data.time_entry;
      
      setRunningEntry(newEntry);
      setTimeEntries(prev => [newEntry, ...prev]);
      setShowNewEntryDialog(false);
    } catch (error) {
      console.error('Error starting timer:', error);
    }
  };

  const handleStopTimer = async (entryId: number) => {
    try {
      const response = await axios.post(route('tenant.time.stop', { timeEntry: entryId }));
      const updatedEntry = response.data.time_entry;
      
      setRunningEntry(null);
      setTimeEntries(prev => 
        prev.map(entry => entry.id === entryId ? updatedEntry : entry)
      );
    } catch (error) {
      console.error('Error stopping timer:', error);
    }
  };

  const handleDeleteEntry = async (entryId: number) => {
    if (!confirm('¿Está seguro que desea eliminar este registro de tiempo?')) return;
    
    try {
      await axios.delete(route('tenant.time.destroy', { timeEntry: entryId }));
      
      // Remove from state
      if (runningEntry?.id === entryId) {
        setRunningEntry(null);
      }
      
      setTimeEntries(prev => prev.filter(entry => entry.id !== entryId));
    } catch (error) {
      console.error('Error deleting time entry:', error);
    }
  };

  const handleTabChange = (value: string) => {
    setCurrentTab(value);
    router.get(route('tenant.time.index'), { date: value }, {
      preserveState: true,
      replace: true,
    });
  };

  const formatDateTime = (dateString: string) => {
    return format(parseISO(dateString), 'dd/MM/yyyy HH:mm', { locale: es });
  };

  const formatTime = (dateString: string) => {
    return format(parseISO(dateString), 'HH:mm', { locale: es });
  };

  return (
    <AppShell>
      <Head title="Registro de Tiempo" />
      
      <div className="container py-6 space-y-6">
        <div className="flex justify-between items-center">
          <Heading>Registro de Tiempo</Heading>
          <div className="flex gap-2">
            <Link href={route('tenant.time.report')}>
              <Button variant="outline" className="flex items-center gap-2">
                <PieChart className="h-4 w-4" />
                <span>Reportes</span>
              </Button>
            </Link>
            <Dialog open={showNewEntryDialog} onOpenChange={setShowNewEntryDialog}>
              <DialogTrigger asChild>
                <Button className="flex items-center gap-2">
                  <Plus className="h-4 w-4" />
                  <span>Nueva Entrada</span>
                </Button>
              </DialogTrigger>
              <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                  <DialogTitle>Nueva Entrada de Tiempo</DialogTitle>
                </DialogHeader>
                <TimeEntryForm 
                  projects={projects}
                  tasks={tasks}
                  categories={categories}
                  onSubmit={handleStartTimer}
                  onCancel={() => setShowNewEntryDialog(false)}
                />
              </DialogContent>
            </Dialog>
          </div>
        </div>
        
        {runningEntry && (
          <Card className="border-primary">
            <CardHeader className="pb-2">
              <CardTitle className="text-lg">Temporizador en ejecución</CardTitle>
            </CardHeader>
            <CardContent>
              <Timer 
                timeEntry={runningEntry}
                onStop={() => handleStopTimer(runningEntry.id)}
              />
            </CardContent>
          </Card>
        )}
        
        <Tabs value={currentTab} onValueChange={handleTabChange} className="space-y-4">
          <TabsList>
            <TabsTrigger value="today" className="flex items-center gap-2">
              <Clock className="h-4 w-4" />
              <span>Hoy</span>
            </TabsTrigger>
            <TabsTrigger value="week" className="flex items-center gap-2">
              <Calendar className="h-4 w-4" />
              <span>Esta semana</span>
            </TabsTrigger>
            <TabsTrigger value="month" className="flex items-center gap-2">
              <CalendarIcon className="h-4 w-4" />
              <span>Este mes</span>
            </TabsTrigger>
          </TabsList>
          
          {['today', 'week', 'month'].map((tab) => (
            <TabsContent key={tab} value={tab} className="space-y-4">
              <Card>
                <CardContent className="pt-6">
                  {timeEntries.length > 0 ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Descripción</TableHead>
                          <TableHead>Proyecto / Tarea</TableHead>
                          <TableHead>Categoría</TableHead>
                          <TableHead>Inicio</TableHead>
                          <TableHead>Fin</TableHead>
                          <TableHead>Duración</TableHead>
                          <TableHead>Facturable</TableHead>
                          <TableHead className="w-[80px]"></TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {timeEntries.map((entry) => (
                          <TableRow key={entry.id}>
                            <TableCell>{entry.description}</TableCell>
                            <TableCell>
                              {entry.project?.name && (
                                <div>{entry.project.name}</div>
                              )}
                              {entry.task?.title && (
                                <div className="text-sm text-muted-foreground">{entry.task.title}</div>
                              )}
                            </TableCell>
                            <TableCell>
                              {entry.category && (
                                <Badge 
                                  style={{ 
                                    backgroundColor: entry.category.color,
                                    color: '#fff'
                                  }}
                                >
                                  {entry.category.name}
                                </Badge>
                              )}
                            </TableCell>
                            <TableCell>{formatDateTime(entry.started_at)}</TableCell>
                            <TableCell>
                              {entry.stopped_at ? formatTime(entry.stopped_at) : '-'}
                            </TableCell>
                            <TableCell>{entry.formatted_duration || '-'}</TableCell>
                            <TableCell>
                              {entry.is_billable ? 
                                <Badge variant="default">Sí</Badge> : 
                                <Badge variant="outline">No</Badge>
                              }
                            </TableCell>
                            <TableCell>
                              <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                  <Button variant="ghost" size="icon">
                                    <MoreVertical className="h-4 w-4" />
                                  </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                  {entry.stopped_at ? (
                                    <>
                                      <DropdownMenuItem>Editar</DropdownMenuItem>
                                      <DropdownMenuItem
                                        className="text-destructive"
                                        onClick={() => handleDeleteEntry(entry.id)}
                                      >
                                        Eliminar
                                      </DropdownMenuItem>
                                    </>
                                  ) : (
                                    <DropdownMenuItem
                                      onClick={() => handleStopTimer(entry.id)}
                                    >
                                      Detener
                                    </DropdownMenuItem>
                                  )}
                                </DropdownMenuContent>
                              </DropdownMenu>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <div className="text-center py-8">
                      <p className="text-muted-foreground">No hay entradas de tiempo para este período</p>
                    </div>
                  )}
                </CardContent>
              </Card>
              
              <div className="flex justify-end">
                <div className="text-sm text-muted-foreground">
                  Total: {timeEntries.reduce((total, entry) => {
                    return total + (entry.duration || 0);
                  }, 0) / 3600} horas
                </div>
              </div>
            </TabsContent>
          ))}
        </Tabs>
      </div>
    </AppShell>
  );
}