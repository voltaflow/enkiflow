import { ConfirmationModal } from '@/components/ConfirmationModal';
import { Heading } from '@/components/heading';
import { ViewSelector } from '@/components/TimeTracker/ViewSelector';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import axios from '@/lib/axios-config';
import { Head, router, usePage } from '@inertiajs/react';
import { format } from 'date-fns';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

// Time tracking components
import { AddTimeModal } from '@/components/TimeTracker/AddTimeModal';
import { ApprovalBanner } from '@/components/TimeTracker/ApprovalBanner';
import { DescriptionInput } from '@/components/TimeTracker/DescriptionInput';
import { EditTimeModalCustom } from '@/components/TimeTracker/EditTimeModalCustom';
import { IdlePromptModal } from '@/components/TimeTracker/IdlePromptModal';
import { StatusIndicator } from '@/components/TimeTracker/StatusIndicator';
import { TaskSelector } from '@/components/TimeTracker/TaskSelector';
import { Timer } from '@/components/TimeTracker/Timer';
import { TimesheetDay } from '@/components/TimeTracker/TimesheetDay';
import { TimesheetWeek } from '@/components/TimeTracker/TimesheetWeek';

// Hooks and store
import { useIdleDetection } from '@/hooks/useIdleDetection';
import { useTimeReminders } from '@/hooks/useTimeReminders';
import { useTimesheetApproval } from '@/hooks/useTimesheetApproval';
import { useTimeEntryStore } from '@/stores/timeEntryStore';

// Icons

// Types
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

interface TimeEntry {
    id: number;
    description: string;
    started_at: string;
    stopped_at: string | null;
    ended_at?: string | null;
    duration: number;
    is_billable: boolean;
    task_id: number | null;
    project_id: number | null;
    task?: Task;
    project?: Project;
}

interface TimeUnifiedProps {
    projects: Project[];
    tasks: Task[];
    todayEntries: TimeEntry[];
    weekEntries: TimeEntry[];
    activeView?: 'timer' | 'day' | 'week';
}

function TimeUnifiedContent({ projects, tasks, todayEntries, weekEntries, activeView = 'timer' }: TimeUnifiedProps) {
    const { auth } = usePage().props as any;
    const isGuest = auth?.isGuest || false;
    
    const [currentView, setCurrentView] = useState(activeView);
    const [showIdlePrompt, setShowIdlePrompt] = useState(false);
    const [selectedDate, setSelectedDate] = useState(new Date());
    const [weekStart, setWeekStart] = useState(() => {
        const today = new Date();
        const day = today.getDay();
        const diff = today.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(today.setDate(diff));
    });

    // Delete confirmation dialog state
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [entryToDelete, setEntryToDelete] = useState<number | null>(null);
    // Removed isDeleting state to simplify

    // Add time modal state
    const [showAddTimeModal, setShowAddTimeModal] = useState(false);

    // Edit time modal state
    const [showEditTimeModal, setShowEditTimeModal] = useState(false);
    const [entryToEdit, setEntryToEdit] = useState<TimeEntry | null>(null);

    // Force re-render state to work around dialog issues
    const [, forceUpdate] = useState({});

    // Duplicate day state
    const [isDuplicating, setIsDuplicating] = useState(false);

    // Delete row confirmation state
    const [showDeleteRowDialog, setShowDeleteRowDialog] = useState(false);
    const [rowToDelete, setRowToDelete] = useState<{ projectId: number; taskId: number | null } | null>(null);

    // Store and hooks
    const timeEntryStore = useTimeEntryStore();
    // Create a wrapper to provide the expected store structure
    const store = {
        state: {
            approval: {
                isSubmitted: false,
                isApproved: false,
                isLocked: false,
                submittedAt: null,
                approvedBy: null
            },
            currentEntry: {
                is_running: false,
                is_paused: false,
                project_id: null,
                task_id: null
            },
            recentEntries: [],
            preferences: {
                dailyHoursGoal: 8
            }
        },
        hasActiveTimer: false,
        formattedDuration: '00:00:00',
        status: 'idle',
        currentDuration: 0,
        canEditEntry: true,
        startTimer: async () => {},
        pauseTimer: async () => {},
        resumeTimer: async () => {},
        stopTimer: async () => {},
        updateTimeEntry: async () => {},
        updateEntryProject: async () => {},
        updateEntryDescription: async () => {},
        duplicatePreviousDay: async () => [],
        handleIdleExceeded: () => {},
        ...timeEntryStore
    };
    const { submitTimesheet, canSubmit, status: approvalStatus } = useTimesheetApproval();

    // State for week entries (initially from props)
    const [currentWeekEntries, setCurrentWeekEntries] = useState(weekEntries);
    const [loadingWeek, setLoadingWeek] = useState(false);

    // State for day entries
    const [currentDayEntries, setCurrentDayEntries] = useState(todayEntries);
    const [loadingDay, setLoadingDay] = useState(false);

    // Removed debug logs

    // Function to load week data
    const loadWeekData = async (weekStartDate: Date) => {
        setLoadingWeek(true);
        // Loading week data
        try {
            const response = await axios.get(route('tenant.time.week-data'), {
                params: {
                    week: format(weekStartDate, 'yyyy-MM-dd'),
                },
            });

            // Week data loaded

            if (response.data.entries) {
                setCurrentWeekEntries(response.data.entries);
            } else {
                // No entries in response
                setCurrentWeekEntries([]);
            }
        } catch (error) {
            // Fallback to current week entries
            setCurrentWeekEntries(weekEntries);
        } finally {
            setLoadingWeek(false);
        }
    };

    // Function to load day data
    const loadDayData = async (date: Date) => {
        setLoadingDay(true);
        // Loading day data
        try {
            const response = await axios.get(route('tenant.time.day-entries'), {
                params: {
                    date: format(date, 'yyyy-MM-dd'),
                },
            });

            // Day data loaded

            if (response.data.entries) {
                setCurrentDayEntries(response.data.entries);
            } else {
                // No entries for this day
                setCurrentDayEntries([]);
            }
        } catch (error) {
            setCurrentDayEntries([]);
        } finally {
            setLoadingDay(false);
        }
    };

    // Effect to load data when week changes
    useEffect(() => {
        if (currentView === 'week') {
            loadWeekData(weekStart);
        }
    }, [weekStart, currentView]);

    // Effect to load data when selected date changes
    useEffect(() => {
        if (currentView === 'day') {
            loadDayData(selectedDate);
        }
    }, [selectedDate, currentView]);

    // Removed cleanup effects since we're not using dialogs anymore

    // Idle detection
    const idle = useIdleDetection({
        threshold: 600, // 10 minutes
        onIdle: () => {
            if (store.state.currentEntry.is_running) {
                setShowIdlePrompt(true);
            }
        },
    });

    // Time reminders
    useTimeReminders({
        dailyGoal: 8,
        reminderTime: '17:00',
        enableNotifications: true,
    });

    // Handle idle prompt responses
    const handleKeepTime = () => {
        setShowIdlePrompt(false);
        idle.resetActivity();
    };

    const handleDiscardTime = (minutes: number) => {
        setShowIdlePrompt(false);
        store.handleIdleExceeded(false, minutes);
        idle.resetActivity();
    };

    // Timer controls
    const handleStartTimer = async () => {
        await store.startTimer();
    };

    const handlePauseTimer = async () => {
        await store.pauseTimer();
    };

    const handleResumeTimer = async () => {
        await store.resumeTimer();
    };

    const handleStopTimer = async () => {
        await store.stopTimer();
    };

    // View change handler
    const handleViewChange = (view: 'timer' | 'day' | 'week') => {
        setCurrentView(view);
        // Update URL without page reload
        router.visit(window.location.pathname + '?view=' + view, {
            preserveState: true,
            replace: true,
            only: [],
        });
    };

    // Submit timesheet
    const handleSubmitWeek = async () => {
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 6);
        await submitTimesheet(weekStart, weekEnd);
    };

    // Handle delete entry
    const handleDeleteEntry = async () => {
        if (!entryToDelete) {
            return;
        }

        // Close modal immediately
        setShowDeleteDialog(false);
        const entryId = entryToDelete;
        setEntryToDelete(null);

        try {
            // Build absolute URL to avoid any query parameter issues
            const absoluteDeleteUrl = `${window.location.protocol}//${window.location.host}/time/${entryId}`;

            const response = await axios.delete(absoluteDeleteUrl);

            toast.success('Entrada eliminada correctamente');

            // Reload data
            if (currentView === 'day') {
                await loadDayData(selectedDate);
            } else if (currentView === 'week') {
                await loadWeekData(weekStart);
            }

            // Force a re-render to ensure UI is responsive
            forceUpdate({});
        } catch (error) {
            toast.error('Error al eliminar la entrada');
        }
    };

    // Handle add time manually
    const handleAddTimeManually = async (data: {
        project_id: number | null;
        task_id: number | null;
        description: string;
        duration: string;
        started_at: string;
        ended_at: string;
    }) => {
        // Parse duration from HH:MM to seconds
        const [hours, minutes] = data.duration.split(':').map(Number);
        const durationSeconds = hours * 3600 + minutes * 60;

        try {
            const payload = {
                description: data.description,
                project_id: data.project_id,
                task_id: data.task_id,
                started_at: data.started_at,
                ended_at: data.ended_at,
                duration: durationSeconds,
                is_manual: true,
            };

            await axios.post(route('tenant.time.store'), payload);

            // Reload data
            if (currentView === 'day') {
                await loadDayData(selectedDate);
            } else if (currentView === 'week') {
                await loadWeekData(weekStart);
            }
        } catch (error: any) {
            if (error.response?.status === 422) {
                const validationErrors = error.response.data.errors;
                const errorMessages = Object.values(validationErrors).flat().join(', ');
                throw new Error(errorMessages || 'Error de validación');
            }
            throw error;
        }
    };

    // Handle delete entire row with confirmation
    const handleDeleteRowConfirm = async () => {
        if (!rowToDelete) return;

        setShowDeleteRowDialog(false);
        const { projectId, taskId } = rowToDelete;
        setRowToDelete(null);

        try {
            // Delete all entries for this project/task combination in the week
            const weekDates = Array.from({ length: 7 }, (_, i) => {
                const date = new Date(weekStart);
                date.setDate(date.getDate() + i);
                return date;
            });

            for (const date of weekDates) {
                const dateStr = format(date, 'yyyy-MM-dd');

                // Find if there's an entry for this date
                const entry = currentWeekEntries.find((e) => {
                    const entryDate = e.started_at.includes('T') ? e.started_at.split('T')[0] : e.started_at.split(' ')[0];
                    return e.project_id === projectId && e.task_id === taskId && entryDate === dateStr && e.duration > 0;
                });

                if (entry) {
                    // Send 0 hours to delete the entry
                    await store.updateTimeEntry(
                        projectId,
                        taskId,
                        dateStr,
                        0, // 0 hours will delete the entry
                        '',
                    );
                }
            }

            // Reload the week data
            await loadWeekData(weekStart);
            toast.success('Todas las entradas de la fila han sido eliminadas');
        } catch (error) {
            toast.error('Error al eliminar la fila. Por favor intenta de nuevo.');
        }
    };

    return (
        <AppSidebarLayout>
            <Head title="Registro de Tiempo" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <Heading>Registro de Tiempo</Heading>
                </div>

                {/* Approval Banner */}
                <div className="w-full">
                    <ApprovalBanner
                        isSubmitted={store.state.approval.isSubmitted}
                        isApproved={store.state.approval.isApproved}
                        isLocked={store.state.approval.isLocked}
                        submittedAt={store.state.approval.submittedAt}
                        approvedBy={store.state.approval.approvedBy}
                        onSubmit={approvalStatus === 'draft' ? handleSubmitWeek : undefined}
                    />
                </div>

                {/* View Selector */}
                <ViewSelector currentView={currentView} onViewChange={handleViewChange} />

                {/* Main Content Container with Fixed Height */}
                <div className="min-h-[600px] space-y-4">
                    {/* Timer View */}
                    {currentView === 'timer' && (
                        <div className="mt-6 min-h-[500px] space-y-6">
                            {isGuest ? (
                                <div className="mx-auto max-w-2xl text-center">
                                    <p className="text-muted-foreground">
                                        Como usuario invitado, no puedes registrar tiempo. 
                                        Puedes ver los registros de tiempo en las vistas de día y semana.
                                    </p>
                                </div>
                            ) : (
                            <>
                            <div className="mx-auto grid max-w-6xl gap-6 md:grid-cols-1 lg:grid-cols-2">
                                {/* Timer Controls */}
                                <div className="space-y-4">
                                    {(() => {
                                        // Check if task selection is required
                                        const selectedProjectId = store.state.currentEntry.project_id;
                                        const selectedTaskId = store.state.currentEntry.task_id;
                                        const projectTasks = selectedProjectId ? tasks.filter((t) => t.project_id === selectedProjectId) : [];
                                        const taskRequired = selectedProjectId && projectTasks.length > 0 && !selectedTaskId;
                                        const canStartTimer = !taskRequired;
                                        const startDisabledReason = taskRequired
                                            ? 'Por favor selecciona una tarea antes de iniciar el temporizador'
                                            : undefined;

                                        return (
                                            <Timer
                                                isRunning={store.state.currentEntry.is_running}
                                                isPaused={store.state.currentEntry.is_paused}
                                                hasActiveTimer={store.hasActiveTimer}
                                                formattedTime={store.formattedDuration}
                                                canStartTimer={canStartTimer}
                                                startDisabledReason={startDisabledReason}
                                                onStart={handleStartTimer}
                                                onPause={handlePauseTimer}
                                                onResume={handleResumeTimer}
                                                onStop={handleStopTimer}
                                            />
                                        );
                                    })()}

                                    <StatusIndicator
                                        status={store.status}
                                        duration={store.currentDuration}
                                        startTime={store.state.currentEntry.started_at}
                                    />
                                </div>

                                {/* Timer Details */}
                                <div className="space-y-4">
                                    <DescriptionInput
                                        value={store.state.currentEntry.description}
                                        disabled={!store.canEditEntry}
                                        onChange={(value) => store.updateEntryDescription(value)}
                                    />

                                    <TaskSelector
                                        projects={projects}
                                        tasks={tasks}
                                        selectedProjectId={store.state.currentEntry.project_id}
                                        selectedTaskId={store.state.currentEntry.task_id}
                                        disabled={false}
                                        onProjectChange={(projectId) => {
                                            store.updateEntryProject(projectId, null);
                                        }}
                                        onTaskChange={(taskId) => {
                                            store.updateEntryProject(store.state.currentEntry.project_id, taskId);
                                        }}
                                    />
                                </div>
                            </div>

                            {/* Recent Entries */}
                            {store.state.recentEntries.length > 0 && (
                                <div className="space-y-4">
                                    <h3 className="text-lg font-semibold">Entradas Recientes</h3>
                                    {/* Map recent entries here */}
                                </div>
                            )}
                            </>
                            )}
                        </div>
                    )}

                    {/* Day View */}
                    {currentView === 'day' && (
                        <div className="mt-6 min-h-[400px] space-y-4 md:min-h-[500px] lg:min-h-[600px]">
                            <div className="transition-opacity duration-200 ease-in-out" style={{ opacity: loadingDay ? 0.5 : 1 }}>
                                {loadingDay ? (
                                    <div className="flex h-[400px] items-center justify-center">
                                        <div className="h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900"></div>
                                    </div>
                                ) : (
                                    <TimesheetDay
                                        date={selectedDate}
                                        entries={currentDayEntries}
                                        projects={projects}
                                        isLocked={store.state.approval.isLocked}
                                        dailyGoal={store.state.preferences.dailyHoursGoal}
                                        isGuest={isGuest}
                                        onAddTime={() => setShowAddTimeModal(true)}
                                        onEditEntry={(entry) => {
                                            setEntryToEdit(entry as any);
                                            setShowEditTimeModal(true);
                                        }}
                                        onDeleteEntry={(entryId) => {
                                            if (typeof entryId === 'number' && entryId > 0) {
                                                setEntryToDelete(entryId);
                                                setShowDeleteDialog(true);
                                            } else {
                                                toast.error('ID de entrada inválido');
                                            }
                                        }}
                                        onDuplicateDay={async () => {
                                            if (isDuplicating) {
                                                return;
                                            }

                                            setIsDuplicating(true);

                                            try {
                                                const yesterday = new Date(selectedDate);
                                                yesterday.setDate(yesterday.getDate() - 1);

                                                const fromDateStr = yesterday.toISOString().split('T')[0];
                                                const toDateStr = selectedDate.toISOString().split('T')[0];

                                                const duplicatedEntries = await store.duplicatePreviousDay(fromDateStr, toDateStr);

                                                if (duplicatedEntries && duplicatedEntries.length > 0) {
                                                    toast.success(`Se duplicaron ${duplicatedEntries.length} entradas del día anterior`);
                                                    await loadDayData(selectedDate);
                                                }
                                            } catch (error: any) {
                                                // Error handling is done in the store
                                            } finally {
                                                setIsDuplicating(false);
                                            }
                                        }}
                                        isDuplicating={isDuplicating}
                                        onDateChange={setSelectedDate}
                                    />
                                )}
                            </div>
                        </div>
                    )}

                    {/* Week View */}
                    {currentView === 'week' && (
                        <div className="mt-6 min-h-[400px] space-y-4 md:min-h-[500px] lg:min-h-[600px]">
                            <div className="transition-opacity duration-200 ease-in-out" style={{ opacity: loadingWeek ? 0 : 1 }}>
                                {loadingWeek ? (
                                    <div className="flex h-[500px] items-center justify-center">
                                        <div className="h-8 w-8 animate-spin rounded-full border-b-2 border-gray-900"></div>
                                    </div>
                                ) : (
                                    <TimesheetWeek
                                        weekStart={weekStart}
                                        isGuest={isGuest}
                                        entries={currentWeekEntries.map((entry) => {
                                            // Handle both date formats: "YYYY-MM-DD HH:MM:SS" and ISO format
                                            let dateStr = '';
                                            if (entry.started_at) {
                                                if (entry.started_at.includes('T')) {
                                                    // ISO format
                                                    dateStr = entry.started_at.split('T')[0];
                                                } else {
                                                    // MySQL format
                                                    dateStr = entry.started_at.split(' ')[0];
                                                }
                                            }
                                            return {
                                                id: entry.id,
                                                project_id: entry.project_id,
                                                task_id: entry.task_id,
                                                date: dateStr,
                                                duration: entry.duration || 0,
                                                description: entry.description || '',
                                            };
                                        })}
                                        projects={projects}
                                        tasks={tasks}
                                        isLocked={store.state.approval.isLocked}
                                        weeklyGoal={store.state.preferences.dailyHoursGoal * 5}
                                        onCellUpdate={async (projectId, taskId, date, hours, description) => {
                                            try {
                                                await store.updateTimeEntry(projectId, taskId, date, hours, description);
                                                // Reload week data to reflect changes
                                                await loadWeekData(weekStart);
                                            } catch (error) {
                                                // Error is already handled in the store
                                            }
                                        }}
                                        onWeekChange={(newWeekStart) => {
                                            setWeekStart(newWeekStart);
                                            // Data will be loaded by the effect
                                        }}
                                        onSubmit={handleSubmitWeek}
                                        onDayClick={(date) => {
                                            setSelectedDate(date);
                                            setCurrentView('day');
                                        }}
                                        onDeleteEntry={async (entryId) => {
                                            if (typeof entryId === 'number' && entryId > 0) {
                                                try {
                                                    // Find the entry to get its details
                                                    const entry = currentWeekEntries.find((e) => e.id === entryId);
                                                    if (entry) {
                                                        // Extract date from started_at
                                                        let entryDate = '';
                                                        if (entry.started_at.includes('T')) {
                                                            entryDate = entry.started_at.split('T')[0];
                                                        } else {
                                                            entryDate = entry.started_at.split(' ')[0];
                                                        }

                                                        // Send 0 hours to delete the entry
                                                        await store.updateTimeEntry(
                                                            entry.project_id,
                                                            entry.task_id,
                                                            entryDate,
                                                            0, // 0 hours will delete the entry
                                                            '',
                                                        );

                                                        // Success message is shown by the store
                                                        // Reload week data
                                                        await loadWeekData(weekStart);
                                                    } else {
                                                        toast.error('No se pudo encontrar la entrada');
                                                    }
                                                } catch (error) {
                                                    toast.error('Error al eliminar la entrada');
                                                }
                                            } else {
                                                toast.error('ID de entrada inválido');
                                            }
                                        }}
                                        onDeleteRow={(projectId, taskId) => {
                                            setRowToDelete({ projectId, taskId });
                                            setShowDeleteRowDialog(true);
                                        }}
                                    />
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Idle Prompt Modal */}
            {showIdlePrompt && <IdlePromptModal idleMinutes={idle.getIdleMinutes()} onKeepTime={handleKeepTime} onDiscardTime={handleDiscardTime} />}

            {/* Custom Confirmation Modal */}
            <ConfirmationModal
                isOpen={showDeleteDialog}
                onClose={() => {
                    setShowDeleteDialog(false);
                    setEntryToDelete(null);
                }}
                onConfirm={handleDeleteEntry}
                title="Confirmar eliminación"
                message="¿Estás seguro de que quieres eliminar esta entrada de tiempo? Esta acción no se puede deshacer."
                confirmText="Eliminar"
                cancelText="Cancelar"
                isDestructive={true}
            />

            {/* Original Delete Dialog - DISABLED due to UI freeze issues
            <Dialog 
                open={showDeleteDialog} 
                onOpenChange={(open) => {
                    setShowDeleteDialog(open);
                    if (!open) {
                        setEntryToDelete(null);
                        // Force cleanup of any stuck portals after dialog closes
                        setTimeout(() => {
                            const portals = document.querySelectorAll('[data-radix-portal]');
                            portals.forEach(portal => {
                                if (portal.innerHTML === '') {
                                    portal.remove();
                                }
                            });
                            // Also remove any stuck overlays
                            const overlays = document.querySelectorAll('[data-radix-dialog-overlay]');
                            overlays.forEach(overlay => {
                                if (!overlay.parentElement?.querySelector('[data-radix-dialog-content]')) {
                                    overlay.remove();
                                }
                            });
                        }, 100);
                    }
                }}
                modal={true}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Confirmar eliminación</DialogTitle>
                        <DialogDescription>
                            ¿Estás seguro de que quieres eliminar esta entrada de tiempo? Esta acción no se puede deshacer.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setShowDeleteDialog(false);
                                setEntryToDelete(null);
                            }}
                            disabled={false}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDeleteEntry}
                            disabled={false}
                        >
                            Eliminar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            */}

            {/* Add Time Modal */}
            <AddTimeModal
                isOpen={showAddTimeModal}
                onClose={() => setShowAddTimeModal(false)}
                projects={projects}
                tasks={tasks}
                date={selectedDate}
                onSubmit={handleAddTimeManually}
            />

            {/* Delete Row Confirmation Modal */}
            <ConfirmationModal
                isOpen={showDeleteRowDialog}
                onClose={() => {
                    setShowDeleteRowDialog(false);
                    setRowToDelete(null);
                }}
                onConfirm={handleDeleteRowConfirm}
                title="Confirmar eliminación de fila"
                message="¿Estás seguro de que quieres eliminar todas las entradas de esta fila para la semana? Esta acción no se puede deshacer."
                confirmText="Eliminar todas"
                cancelText="Cancelar"
                isDestructive={true}
            />

            {/* Edit Time Modal - Using custom implementation to avoid Radix UI freezing issues */}
            <EditTimeModalCustom
                isOpen={showEditTimeModal}
                onClose={() => {
                    setShowEditTimeModal(false);
                    setEntryToEdit(null);
                }}
                projects={projects}
                tasks={tasks}
                entry={entryToEdit}
                onSubmit={async (data) => {
                    try {
                        // Parse duration from HH:MM to seconds
                        const [hours, minutes] = data.duration.split(':').map(Number);
                        const durationSeconds = hours * 3600 + minutes * 60;

                        // Validate ID before making request
                        if (!data.id || typeof data.id !== 'number' || data.id <= 0) {
                            toast.error('Error: ID de entrada inválido');
                            return;
                        }

                        const updateUrl = `/time/${data.id}`;

                        const response = await axios.put(updateUrl, {
                            description: data.description,
                            project_id: data.project_id,
                            task_id: data.task_id,
                            started_at: data.started_at,
                            ended_at: data.ended_at,
                            duration: durationSeconds,
                        });

                        toast.success('Entrada de tiempo actualizada');

                        // Close modal and clean up state BEFORE reloading data
                        setShowEditTimeModal(false);
                        setEntryToEdit(null);

                        // Force a re-render to ensure UI is responsive
                        forceUpdate({});

                        // Reload data after closing modal
                        if (currentView === 'day') {
                            await loadDayData(selectedDate);
                        } else if (currentView === 'week') {
                            await loadWeekData(weekStart);
                        }
                    } catch (error: any) {
                        toast.error('Error al actualizar la entrada');

                        // Close modal on error too
                        setShowEditTimeModal(false);
                        setEntryToEdit(null);
                    }
                }}
            />
        </AppSidebarLayout>
    );
}

export default function TimeUnified(props: TimeUnifiedProps) {
    return <TimeUnifiedContent {...props} />;
}
