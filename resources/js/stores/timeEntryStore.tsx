import React, { createContext, useContext, useReducer, useEffect, ReactNode } from 'react';
import { useLocalStorageBackup } from '@/hooks/useLocalStorageBackup';
import axios from 'axios';

// Types
interface TimeEntry {
    id: number | null;
    description: string;
    project_id: number | null;
    task_id: number | null;
    started_at: string | null;
    stopped_at: string | null;
    duration: number;
    is_billable: boolean;
    is_running: boolean;
    is_paused: boolean;
    paused_at: Date | null;
    total_paused_time: number;
    last_synced_at: Date | null;
}

interface Timer {
    id: number;
    user_id: number;
    description: string;
    project_id: number | null;
    task_id: number | null;
    started_at: string;
    is_running: boolean;
    total_duration: number;
}

interface Preferences {
    dailyHoursGoal: number;
    reminderTime: string;
    enableIdleDetection: boolean;
    enableReminders: boolean;
}

interface ApprovalState {
    isSubmitted: boolean;
    submittedAt: Date | null;
    isApproved: boolean;
    approvedAt: Date | null;
    approvedBy: any | null;
    isLocked: boolean;
    lockedAt: Date | null;
}

interface TimeEntryState {
    currentEntry: TimeEntry;
    recentEntries: TimeEntry[];
    activeTimers: Timer[];
    isLoading: boolean;
    error: string | null;
    reminders: {
        dailySent: boolean;
        lastSentAt: Date | null;
    };
    idleDetection: {
        threshold: number;
        lastActivity: Date | null;
        idleStartedAt: Date | null;
    };
    approval: ApprovalState;
    preferences: Preferences;
}

// Action Types
type TimeEntryAction =
    | { type: 'SET_TIMER_STATE'; payload: Partial<TimeEntry> }
    | { type: 'RESET_CURRENT_ENTRY' }
    | { type: 'SET_ENTRY_DESCRIPTION'; payload: string }
    | { type: 'SET_ENTRY_PROJECT'; payload: { projectId: number | null; taskId: number | null } }
    | { type: 'ADD_RECENT_ENTRY'; payload: TimeEntry }
    | { type: 'SET_LOADING'; payload: boolean }
    | { type: 'SET_ERROR'; payload: string | null }
    | { type: 'SET_APPROVAL_STATUS'; payload: Partial<ApprovalState> }
    | { type: 'SET_PREFERENCES'; payload: Partial<Preferences> }
    | { type: 'MARK_REMINDER_SENT' }
    | { type: 'UPDATE_LAST_ACTIVITY' }
    | { type: 'ADJUST_CURRENT_DURATION'; payload: number }
    | { type: 'ADD_TO_PAUSED_TIME'; payload: number }
    | { type: 'SET_CURRENT_ENTRY'; payload: TimeEntry }
    | { type: 'ADD_ACTIVE_TIMER'; payload: Timer }
    | { type: 'REMOVE_ACTIVE_TIMER'; payload: number }
    | { type: 'RESET_APPROVAL_STATUS' };

// Initial State
const initialState: TimeEntryState = {
    currentEntry: {
        id: null,
        description: '',
        project_id: null,
        task_id: null,
        started_at: null,
        stopped_at: null,
        duration: 0,
        is_billable: false,
        is_running: false,
        is_paused: false,
        paused_at: null,
        total_paused_time: 0,
        last_synced_at: null,
    },
    recentEntries: [],
    activeTimers: [],
    isLoading: false,
    error: null,
    reminders: {
        dailySent: false,
        lastSentAt: null,
    },
    idleDetection: {
        threshold: 600, // 10 minutes
        lastActivity: null,
        idleStartedAt: null,
    },
    approval: {
        isSubmitted: false,
        submittedAt: null,
        isApproved: false,
        approvedAt: null,
        approvedBy: null,
        isLocked: false,
        lockedAt: null,
    },
    preferences: {
        dailyHoursGoal: 8,
        reminderTime: '17:00',
        enableIdleDetection: true,
        enableReminders: true,
    },
};

// Reducer
function timeEntryReducer(state: TimeEntryState, action: TimeEntryAction): TimeEntryState {
    switch (action.type) {
        case 'SET_TIMER_STATE':
            return {
                ...state,
                currentEntry: {
                    ...state.currentEntry,
                    ...action.payload,
                },
            };

        case 'RESET_CURRENT_ENTRY':
            return {
                ...state,
                currentEntry: initialState.currentEntry,
            };

        case 'SET_ENTRY_DESCRIPTION':
            return {
                ...state,
                currentEntry: {
                    ...state.currentEntry,
                    description: action.payload,
                },
            };

        case 'SET_ENTRY_PROJECT':
            return {
                ...state,
                currentEntry: {
                    ...state.currentEntry,
                    project_id: action.payload.projectId,
                    task_id: action.payload.taskId,
                },
            };

        case 'ADD_RECENT_ENTRY':
            return {
                ...state,
                recentEntries: [action.payload, ...state.recentEntries.slice(0, 9)],
            };

        case 'SET_LOADING':
            return {
                ...state,
                isLoading: action.payload,
            };

        case 'SET_ERROR':
            return {
                ...state,
                error: action.payload,
            };

        case 'SET_APPROVAL_STATUS':
            return {
                ...state,
                approval: {
                    ...state.approval,
                    ...action.payload,
                },
            };

        case 'SET_PREFERENCES':
            return {
                ...state,
                preferences: {
                    ...state.preferences,
                    ...action.payload,
                },
            };

        case 'MARK_REMINDER_SENT':
            return {
                ...state,
                reminders: {
                    dailySent: true,
                    lastSentAt: new Date(),
                },
            };

        case 'UPDATE_LAST_ACTIVITY':
            return {
                ...state,
                idleDetection: {
                    ...state.idleDetection,
                    lastActivity: new Date(),
                    idleStartedAt: null,
                },
            };

        case 'ADJUST_CURRENT_DURATION':
            return {
                ...state,
                currentEntry: {
                    ...state.currentEntry,
                    duration: Math.max(0, state.currentEntry.duration + action.payload),
                },
            };

        case 'ADD_TO_PAUSED_TIME':
            return {
                ...state,
                currentEntry: {
                    ...state.currentEntry,
                    total_paused_time: state.currentEntry.total_paused_time + action.payload,
                },
            };

        case 'SET_CURRENT_ENTRY':
            return {
                ...state,
                currentEntry: action.payload,
            };

        case 'ADD_ACTIVE_TIMER':
            return {
                ...state,
                activeTimers: [...state.activeTimers, action.payload],
            };

        case 'REMOVE_ACTIVE_TIMER':
            return {
                ...state,
                activeTimers: state.activeTimers.filter(timer => timer.id !== action.payload),
            };

        case 'RESET_APPROVAL_STATUS':
            return {
                ...state,
                approval: initialState.approval,
            };

        default:
            return state;
    }
}

// Context
interface TimeEntryContextType {
    state: TimeEntryState;
    // Actions
    startTimer: () => Promise<void>;
    pauseTimer: () => void;
    resumeTimer: () => void;
    stopTimer: () => Promise<void>;
    updateEntryDescription: (description: string) => void;
    updateEntryProject: (projectId: number | null, taskId: number | null) => void;
    handleIdleExceeded: (keepTime: boolean, discardMinutes?: number) => void;
    sendDailyReminder: () => Promise<void>;
    duplicatePreviousDay: (fromDate: string, toDate: string) => Promise<TimeEntry[]>;
    submitTimesheet: (weekStart: Date, weekEnd: Date) => Promise<any>;
    approveTimesheet: (userId: number, weekStart: Date) => Promise<any>;
    lockTimesheet: () => void;
    loadUserPreferences: () => Promise<void>;
    loadSavedEntry: () => void;
    syncFailedEntries: () => Promise<void>;
    // Getters
    hasActiveTimer: boolean;
    canStartNewTimer: boolean;
    todaysTotalHours: number;
    needsReminder: boolean;
    canEditTimesheet: boolean;
    timesheetStatus: 'draft' | 'submitted' | 'approved' | 'locked';
    currentDuration: number;
    formattedDuration: string;
    canEditEntry: boolean;
    status: 'running' | 'paused' | 'stopped';
}

const TimeEntryContext = createContext<TimeEntryContextType | undefined>(undefined);

// Provider Component
export function TimeEntryProvider({ children }: { children: ReactNode }) {
    const [state, dispatch] = useReducer(timeEntryReducer, initialState);
    const localStorage = useLocalStorageBackup();

    // Derived state (getters)
    const hasActiveTimer = state.activeTimers.some(timer => timer.is_running);
    const canStartNewTimer = !hasActiveTimer;

    const todaysTotalHours = state.recentEntries
        .filter(entry => {
            const entryDate = new Date(entry.started_at || '');
            const today = new Date();
            return entryDate.toDateString() === today.toDateString();
        })
        .reduce((sum, entry) => sum + (entry.duration || 0), 0) / 3600;

    const needsReminder = () => {
        if (!state.preferences.enableReminders) return false;
        if (state.reminders.dailySent) return false;

        const now = new Date();
        const [hours, minutes] = state.preferences.reminderTime.split(':').map(Number);
        const reminderTime = new Date();
        reminderTime.setHours(hours, minutes, 0);

        return now >= reminderTime && todaysTotalHours < state.preferences.dailyHoursGoal;
    };

    const canEditTimesheet = !state.approval.isLocked;

    const timesheetStatus = (): 'draft' | 'submitted' | 'approved' | 'locked' => {
        if (state.approval.isLocked) return 'locked';
        if (state.approval.isApproved) return 'approved';
        if (state.approval.isSubmitted) return 'submitted';
        return 'draft';
    };

    const currentDuration = () => {
        if (!state.currentEntry.started_at) return 0;

        let duration = state.currentEntry.duration;

        if (state.currentEntry.is_running) {
            const now = new Date();
            const startTime = new Date(state.currentEntry.started_at);
            const elapsed = Math.floor((now.getTime() - startTime.getTime()) / 1000);
            duration += elapsed - state.currentEntry.total_paused_time;
        }

        return duration;
    };

    const formattedDuration = () => {
        const seconds = currentDuration();
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    const canEditEntry = state.currentEntry.is_running || state.currentEntry.is_paused;

    const status = (): 'running' | 'paused' | 'stopped' => {
        if (state.currentEntry.is_running) return 'running';
        if (state.currentEntry.is_paused) return 'paused';
        return 'stopped';
    };

    // Actions
    const startTimer = async () => {
        if (!canStartNewTimer) {
            // Stop active timer first
            const activeTimer = state.activeTimers.find(t => t.is_running);
            if (activeTimer) {
                await stopTimer();
            }
        }

        const startTime = new Date();
        dispatch({
            type: 'SET_TIMER_STATE',
            payload: {
                started_at: startTime.toISOString(),
                is_running: true,
                is_paused: false,
                duration: 0,
                total_paused_time: 0,
            },
        });

        // Save to localStorage
        localStorage.saveCurrentEntry(state.currentEntry);
    };

    const pauseTimer = () => {
        if (!state.currentEntry.is_running) return;

        dispatch({
            type: 'SET_TIMER_STATE',
            payload: {
                is_running: false,
                is_paused: true,
                paused_at: new Date(),
            },
        });

        localStorage.saveCurrentEntry(state.currentEntry);
    };

    const resumeTimer = () => {
        if (!state.currentEntry.is_paused || !state.currentEntry.paused_at) return;

        const now = new Date();
        const pauseDuration = Math.floor((now.getTime() - state.currentEntry.paused_at.getTime()) / 1000);

        dispatch({
            type: 'SET_TIMER_STATE',
            payload: {
                is_running: true,
                is_paused: false,
                paused_at: null,
                total_paused_time: state.currentEntry.total_paused_time + pauseDuration,
            },
        });

        localStorage.saveCurrentEntry(state.currentEntry);
    };

    const stopTimer = async () => {
        if (!state.currentEntry.is_running && !state.currentEntry.is_paused) return;

        const endTime = new Date();
        const duration = currentDuration();

        dispatch({
            type: 'SET_TIMER_STATE',
            payload: {
                is_running: false,
                is_paused: false,
                stopped_at: endTime.toISOString(),
                duration,
            },
        });

        try {
            dispatch({ type: 'SET_LOADING', payload: true });

            const response = await axios.post('/api/time-entries', {
                description: state.currentEntry.description,
                project_id: state.currentEntry.project_id,
                task_id: state.currentEntry.task_id,
                started_at: state.currentEntry.started_at,
                ended_at: endTime.toISOString(),
                duration,
                is_manual: false,
            });

            dispatch({ type: 'ADD_RECENT_ENTRY', payload: response.data.time_entry });
            dispatch({ type: 'RESET_CURRENT_ENTRY' });

            localStorage.clearCurrentEntry();
        } catch (error: any) {
            dispatch({ type: 'SET_ERROR', payload: error.response?.data?.message || 'Error al guardar la entrada de tiempo' });
            localStorage.saveFailedEntry(state.currentEntry);
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const updateEntryDescription = (description: string) => {
        dispatch({ type: 'SET_ENTRY_DESCRIPTION', payload: description });
        localStorage.saveCurrentEntry({ ...state.currentEntry, description });
    };

    const updateEntryProject = (projectId: number | null, taskId: number | null) => {
        dispatch({ type: 'SET_ENTRY_PROJECT', payload: { projectId, taskId } });
        localStorage.saveCurrentEntry({ ...state.currentEntry, project_id: projectId, task_id: taskId });
    };

    const handleIdleExceeded = (keepTime: boolean, discardMinutes: number = 0) => {
        if (!state.currentEntry.is_running) return;

        if (keepTime) {
            dispatch({ type: 'UPDATE_LAST_ACTIVITY' });
        } else {
            const discardSeconds = discardMinutes * 60;
            dispatch({ type: 'ADJUST_CURRENT_DURATION', payload: -discardSeconds });
            dispatch({ type: 'ADD_TO_PAUSED_TIME', payload: discardSeconds });
        }

        localStorage.saveCurrentEntry(state.currentEntry);
    };

    const sendDailyReminder = async () => {
        if (!needsReminder()) return;

        try {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('EnkiFlow - Registro de Tiempo', {
                    body: `Has registrado ${todaysTotalHours.toFixed(1)} de ${state.preferences.dailyHoursGoal} horas hoy.`,
                    icon: '/logo.png',
                });
            }

            await axios.post('/api/reminders/daily', {
                hours_tracked: todaysTotalHours,
                hours_goal: state.preferences.dailyHoursGoal,
            });

            dispatch({ type: 'MARK_REMINDER_SENT' });
        } catch (error) {
            console.error('Error enviando recordatorio:', error);
        }
    };

    const duplicatePreviousDay = async (fromDate: string, toDate: string): Promise<TimeEntry[]> => {
        try {
            dispatch({ type: 'SET_LOADING', payload: true });

            const response = await axios.post('/api/time-entries/duplicate-day', {
                from_date: fromDate,
                to_date: toDate,
            });

            response.data.entries.forEach((entry: TimeEntry) => {
                dispatch({ type: 'ADD_RECENT_ENTRY', payload: entry });
            });

            return response.data.entries;
        } catch (error: any) {
            dispatch({ type: 'SET_ERROR', payload: error.response?.data?.message || 'Error al duplicar día' });
            throw error;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const submitTimesheet = async (weekStart: Date, weekEnd: Date) => {
        if (state.approval.isSubmitted) return;

        try {
            dispatch({ type: 'SET_LOADING', payload: true });

            const response = await axios.post('/api/timesheets/submit', {
                week_start: weekStart.toISOString(),
                week_end: weekEnd.toISOString(),
            });

            dispatch({
                type: 'SET_APPROVAL_STATUS',
                payload: {
                    isSubmitted: true,
                    submittedAt: new Date(),
                },
            });

            return response.data;
        } catch (error: any) {
            dispatch({ type: 'SET_ERROR', payload: error.response?.data?.message || 'Error al enviar hoja de tiempo' });
            throw error;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const approveTimesheet = async (userId: number, weekStart: Date) => {
        try {
            const response = await axios.post('/api/timesheets/approve', {
                user_id: userId,
                week_start: weekStart.toISOString(),
            });

            dispatch({
                type: 'SET_APPROVAL_STATUS',
                payload: {
                    isApproved: true,
                    approvedAt: new Date(),
                    approvedBy: response.data.approved_by,
                },
            });

            return response.data;
        } catch (error: any) {
            dispatch({ type: 'SET_ERROR', payload: error.response?.data?.message });
            throw error;
        }
    };

    const lockTimesheet = () => {
        dispatch({
            type: 'SET_APPROVAL_STATUS',
            payload: {
                isLocked: true,
                lockedAt: new Date(),
            },
        });
    };

    const loadUserPreferences = async () => {
        try {
            const response = await axios.get('/api/user/preferences');
            dispatch({ type: 'SET_PREFERENCES', payload: response.data.preferences });
        } catch (error) {
            console.error('Error cargando preferencias:', error);
        }
    };

    const loadSavedEntry = () => {
        const savedEntry = localStorage.getSavedEntry();
        if (savedEntry) {
            dispatch({ type: 'SET_CURRENT_ENTRY', payload: savedEntry });

            if (savedEntry.is_running || savedEntry.is_paused) {
                const now = new Date();
                const lastActive = savedEntry.paused_at || savedEntry.started_at;
                const hoursSinceActive = (now.getTime() - new Date(lastActive).getTime()) / (1000 * 60 * 60);

                if (hoursSinceActive > 8) {
                    dispatch({
                        type: 'SET_TIMER_STATE',
                        payload: {
                            is_running: false,
                            is_paused: false,
                            stopped_at: new Date(new Date(lastActive).getTime() + 10 * 60 * 1000).toISOString(),
                            duration: savedEntry.duration + 600,
                        },
                    });

                    localStorage.saveFailedEntry(state.currentEntry);
                    localStorage.clearCurrentEntry();
                }
            }
        }
    };

    const syncFailedEntries = async () => {
        const failedEntries = localStorage.getFailedEntries();
        if (!failedEntries.length) return;

        dispatch({ type: 'SET_LOADING', payload: true });

        for (const entry of failedEntries) {
            try {
                const response = await axios.post('/api/time-entries', {
                    description: entry.description,
                    project_id: entry.project_id,
                    task_id: entry.task_id,
                    started_at: entry.started_at,
                    ended_at: entry.stopped_at,
                    duration: entry.duration,
                    is_manual: false,
                });

                localStorage.removeFailedEntry(entry);
                dispatch({ type: 'ADD_RECENT_ENTRY', payload: response.data.time_entry });
            } catch (error) {
                console.error('Error al sincronizar entrada:', error);
            }
        }

        dispatch({ type: 'SET_LOADING', payload: false });
    };

    // Load saved data on mount
    useEffect(() => {
        loadSavedEntry();
        loadUserPreferences();
        syncFailedEntries();
    }, []);

    const value: TimeEntryContextType = {
        state,
        // Actions
        startTimer,
        pauseTimer,
        resumeTimer,
        stopTimer,
        updateEntryDescription,
        updateEntryProject,
        handleIdleExceeded,
        sendDailyReminder,
        duplicatePreviousDay,
        submitTimesheet,
        approveTimesheet,
        lockTimesheet,
        loadUserPreferences,
        loadSavedEntry,
        syncFailedEntries,
        // Getters
        hasActiveTimer,
        canStartNewTimer,
        todaysTotalHours,
        needsReminder: needsReminder(),
        canEditTimesheet,
        timesheetStatus: timesheetStatus(),
        currentDuration: currentDuration(),
        formattedDuration: formattedDuration(),
        canEditEntry,
        status: status(),
    };

    return <TimeEntryContext.Provider value={value}>{children}</TimeEntryContext.Provider>;
}

// Hook to use the context
export function useTimeEntryStore() {
    const context = useContext(TimeEntryContext);
    if (context === undefined) {
        throw new Error('useTimeEntryStore must be used within a TimeEntryProvider');
    }
    return context;
}