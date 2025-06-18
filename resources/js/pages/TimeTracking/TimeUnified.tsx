import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { Heading } from '@/components/heading';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

// Time tracking components
import { Timer } from '@/components/TimeTracker/Timer';
import { TaskSelector } from '@/components/TimeTracker/TaskSelector';
import { DescriptionInput } from '@/components/TimeTracker/DescriptionInput';
import { StatusIndicator } from '@/components/TimeTracker/StatusIndicator';
import { TimesheetDay } from '@/components/TimeTracker/TimesheetDay';
import { TimesheetWeek } from '@/components/TimeTracker/TimesheetWeek';
import { IdlePromptModal } from '@/components/TimeTracker/IdlePromptModal';
import { ApprovalBanner } from '@/components/TimeTracker/ApprovalBanner';

// Hooks and store
import { TimeEntryProvider, useTimeEntryStore } from '@/stores/timeEntryStore';
import { useTimer } from '@/hooks/useTimer';
import { useIdleDetection } from '@/hooks/useIdleDetection';
import { useTimeReminders } from '@/hooks/useTimeReminders';
import { useTimesheetApproval } from '@/hooks/useTimesheetApproval';

// Icons
import { Clock, Calendar, CalendarDays, Settings } from 'lucide-react';

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
    const [currentView, setCurrentView] = useState(activeView);
    const [showIdlePrompt, setShowIdlePrompt] = useState(false);
    const [selectedDate, setSelectedDate] = useState(new Date());
    const [weekStart, setWeekStart] = useState(() => {
        const today = new Date();
        const day = today.getDay();
        const diff = today.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(today.setDate(diff));
    });

    // Store and hooks
    const store = useTimeEntryStore();
    const timer = useTimer();
    const { submitTimesheet, canSubmit, status: approvalStatus } = useTimesheetApproval();

    // Idle detection
    const idle = useIdleDetection({
        threshold: 600, // 10 minutes
        onIdle: () => {
            if (timer.isRunning) {
                setShowIdlePrompt(true);
            }
        }
    });

    // Time reminders
    useTimeReminders({
        dailyGoal: 8,
        reminderTime: '17:00',
        enableNotifications: true
    });

    // Handle idle prompt responses
    const handleKeepTime = () => {
        setShowIdlePrompt(false);
        idle.resetActivity();
    };

    const handleDiscardTime = (minutes: number) => {
        setShowIdlePrompt(false);
        timer.adjustDuration(-minutes * 60);
        store.handleIdleExceeded(false, minutes);
        idle.resetActivity();
    };

    // Timer controls
    const handleStartTimer = async () => {
        await store.startTimer();
        timer.start();
    };

    const handlePauseTimer = () => {
        store.pauseTimer();
        timer.pause();
    };

    const handleResumeTimer = () => {
        store.resumeTimer();
        timer.resume();
    };

    const handleStopTimer = async () => {
        const duration = timer.stop();
        await store.stopTimer();
    };

    // Tab change handler
    const handleTabChange = (value: string) => {
        setCurrentView(value as 'timer' | 'day' | 'week');
        // Update URL without page reload
        router.visit(
            window.location.pathname + '?view=' + value,
            {
                preserveState: true,
                replace: true,
                only: []
            }
        );
    };

    // Submit timesheet
    const handleSubmitWeek = async () => {
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 6);
        await submitTimesheet(weekStart, weekEnd);
    };

    return (
        <AppSidebarLayout>
            <Head title="Registro de Tiempo" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6 py-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <Heading>Registro de Tiempo</Heading>
                    <Button variant="outline" size="icon">
                        <Settings className="h-4 w-4" />
                    </Button>
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

                {/* Tabs Navigation */}
                <Tabs value={currentView} onValueChange={handleTabChange} className="space-y-4">
                    <TabsList className="grid w-full grid-cols-3">
                        <TabsTrigger value="timer" className="flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            <span>Temporizador</span>
                        </TabsTrigger>
                        <TabsTrigger value="day" className="flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            <span>Día</span>
                        </TabsTrigger>
                        <TabsTrigger value="week" className="flex items-center gap-2">
                            <CalendarDays className="h-4 w-4" />
                            <span>Semana</span>
                        </TabsTrigger>
                    </TabsList>

                    {/* Timer View */}
                    <TabsContent value="timer" className="space-y-6 mt-6">
                        <div className="grid gap-6 md:grid-cols-1 lg:grid-cols-2 max-w-6xl mx-auto">
                            {/* Timer Controls */}
                            <div className="space-y-4">
                                <Timer
                                    isRunning={timer.isRunning}
                                    isPaused={timer.isPaused}
                                    hasActiveTimer={store.hasActiveTimer}
                                    formattedTime={timer.formattedTime}
                                    onStart={handleStartTimer}
                                    onPause={handlePauseTimer}
                                    onResume={handleResumeTimer}
                                    onStop={handleStopTimer}
                                />

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
                                    disabled={!store.canEditEntry}
                                    onProjectChange={(projectId) => store.updateEntryProject(projectId, null)}
                                    onTaskChange={(taskId) => store.updateEntryProject(store.state.currentEntry.project_id, taskId)}
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
                    </TabsContent>

                    {/* Day View */}
                    <TabsContent value="day" className="space-y-4 mt-6">
                        <TimesheetDay
                            date={selectedDate}
                            entries={todayEntries}
                            projects={projects}
                            isLocked={store.state.approval.isLocked}
                            dailyGoal={store.state.preferences.dailyHoursGoal}
                            onAddTime={() => setCurrentView('timer')}
                            onEditEntry={(entry) => console.log('Edit entry:', entry)}
                            onDeleteEntry={(entryId) => console.log('Delete entry:', entryId)}
                            onDuplicateDay={async () => {
                                const yesterday = new Date(selectedDate);
                                yesterday.setDate(yesterday.getDate() - 1);
                                await store.duplicatePreviousDay(
                                    yesterday.toISOString().split('T')[0],
                                    selectedDate.toISOString().split('T')[0]
                                );
                            }}
                        />
                    </TabsContent>

                    {/* Week View */}
                    <TabsContent value="week" className="space-y-4 mt-6">
                        <TimesheetWeek
                            weekStart={weekStart}
                            entries={weekEntries}
                            projects={projects}
                            tasks={tasks}
                            isLocked={store.state.approval.isLocked}
                            weeklyGoal={store.state.preferences.dailyHoursGoal * 5}
                            onCellUpdate={(projectId, taskId, date, hours) => {
                                console.log('Update cell:', { projectId, taskId, date, hours });
                            }}
                            onWeekChange={setWeekStart}
                            onSubmit={handleSubmitWeek}
                        />
                    </TabsContent>
                </Tabs>
            </div>

            {/* Idle Prompt Modal */}
            {showIdlePrompt && (
                <IdlePromptModal
                    idleMinutes={idle.getIdleMinutes()}
                    onKeepTime={handleKeepTime}
                    onDiscardTime={handleDiscardTime}
                />
            )}
        </AppSidebarLayout>
    );
}

export default function TimeUnified(props: TimeUnifiedProps) {
    return (
        <TimeEntryProvider>
            <TimeUnifiedContent {...props} />
        </TimeEntryProvider>
    );
}