import React, { createContext, useContext, useReducer, useEffect, useRef, useState, useMemo, ReactNode } from 'react';
import { useLocalStorageBackup } from '@/hooks/useLocalStorageBackup';
import { useTimerBroadcast } from '@/hooks/useBroadcastChannel';
import { useTimerOfflineQueue } from '@/hooks/useOfflineQueue';
import axios from '@/lib/axios-config';
import { toast } from 'sonner';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { route } from '@/lib/route-helper';

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
    copyRowsFromPreviousWeek: (targetWeekStart: string) => Promise<{ project_id: number | null; task_id: number | null; project?: any; task?: any }[]>;
    submitTimesheet: (weekStart: Date, weekEnd: Date) => Promise<any>;
    approveTimesheet: (userId: number, weekStart: Date) => Promise<any>;
    lockTimesheet: () => void;
    loadUserPreferences: () => Promise<void>;
    loadSavedEntry: () => void;
    syncFailedEntries: () => Promise<void>;
    updateTimeEntry: (projectId: number | null, taskId: number | null, date: string, hours: number, description?: string, isPlaceholder?: boolean) => Promise<void>;
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
    const [currentTime, setCurrentTime] = useState(new Date());
    const localStorage = useLocalStorageBackup();
    const syncIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const lastSyncRef = useRef<Date | null>(null);
    const timerIntervalRef = useRef<NodeJS.Timeout | null>(null);
    
    // Use ref to always have access to the latest state
    const stateRef = useRef(state);
    stateRef.current = state;
    
    // Offline queue for timer sync
    const { queueTimerSync, queueTimerStop, isOnline } = useTimerOfflineQueue();
    
    // Broadcast channel for cross-tab sync
    const { broadcastTimerStart, broadcastTimerStop, broadcastTimerPause, broadcastTimerResume, broadcastTimerUpdate } = useTimerBroadcast(
        (data) => {
            // Handle timer updates from other tabs
            // Reload the timer state from server
            loadSavedEntry();
        }
    );

    // Update current time every second for timer display
    useEffect(() => {
        if (state.currentEntry.is_running) {
            timerIntervalRef.current = setInterval(() => {
                setCurrentTime(new Date());
            }, 1000);
        } else {
            if (timerIntervalRef.current) {
                clearInterval(timerIntervalRef.current);
                timerIntervalRef.current = null;
            }
        }
        
        return () => {
            if (timerIntervalRef.current) {
                clearInterval(timerIntervalRef.current);
            }
        };
    }, [state.currentEntry.is_running]);

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

    const needsReminder = useMemo(() => {
        if (!state.preferences.enableReminders) return false;
        if (state.reminders.dailySent) return false;

        const now = new Date();
        const [hours, minutes] = state.preferences.reminderTime.split(':').map(Number);
        const reminderTime = new Date();
        reminderTime.setHours(hours, minutes, 0);

        return now >= reminderTime && todaysTotalHours < state.preferences.dailyHoursGoal;
    }, [state.preferences.enableReminders, state.reminders.dailySent, state.preferences.reminderTime, todaysTotalHours, state.preferences.dailyHoursGoal]);

    const canEditTimesheet = !state.approval.isLocked;

    const timesheetStatus = useMemo((): 'draft' | 'submitted' | 'approved' | 'locked' => {
        if (state.approval.isLocked) return 'locked';
        if (state.approval.isApproved) return 'approved';
        if (state.approval.isSubmitted) return 'submitted';
        return 'draft';
    }, [state.approval.isLocked, state.approval.isApproved, state.approval.isSubmitted]);

    const currentDuration = useMemo(() => {
        // If timer is not running, return the stored duration
        if (!state.currentEntry.is_running) {
            return state.currentEntry.duration || 0;
        }

        // If no start time, return 0
        if (!state.currentEntry.started_at) {
            return 0;
        }

        // Calculate elapsed time since start
        const startTime = new Date(state.currentEntry.started_at);
        const elapsed = Math.floor((currentTime.getTime() - startTime.getTime()) / 1000);
        
        // Subtract any paused time
        const actualDuration = elapsed - (state.currentEntry.total_paused_time || 0);
        
        // Ensure duration is never negative
        return Math.max(0, actualDuration);
    }, [state.currentEntry.started_at, state.currentEntry.is_running, state.currentEntry.total_paused_time, currentTime]);

    const formattedDuration = useMemo(() => {
        const seconds = currentDuration;
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }, [currentDuration]);

    const canEditEntry = state.currentEntry.is_running || state.currentEntry.is_paused;

    const status = useMemo((): 'running' | 'paused' | 'stopped' => {
        if (state.currentEntry.is_running) return 'running';
        if (state.currentEntry.is_paused) return 'paused';
        return 'stopped';
    }, [state.currentEntry.is_running, state.currentEntry.is_paused]);


    // Sync timer state with server
    const syncTimerWithServer = async () => {
        if (!state.currentEntry.is_running && !state.currentEntry.is_paused) {
            return;
        }

        const syncData = {
            description: state.currentEntry.description,
            project_id: state.currentEntry.project_id,
            task_id: state.currentEntry.task_id,
            is_running: state.currentEntry.is_running,
            is_paused: state.currentEntry.is_paused,
            duration: currentDuration,
            paused_duration: state.currentEntry.total_paused_time,
            metadata: {
                client_time: new Date().toISOString(),
                tab_id: window.name || 'default',
            },
        };

        try {
            if (isOnline) {
                const response = await axios.post(route('api.timer.active.sync'), syncData);
                lastSyncRef.current = new Date();
                
                // Update localStorage with current duration
                const updatedEntry = {
                    ...state.currentEntry,
                    duration: currentDuration,
                    last_synced_at: new Date(),
                };
                localStorage.saveCurrentEntry(updatedEntry);
            } else {
                // Queue for later sync
                queueTimerSync(syncData);
            }
        } catch (error) {
            // Queue for retry
            queueTimerSync(syncData);
        }
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
        
        // Update state immediately
        dispatch({
            type: 'SET_TIMER_STATE',
            payload: {
                started_at: startTime.toISOString(),
                is_running: true,
                is_paused: false,
                duration: 0,
                total_paused_time: 0,
                last_synced_at: startTime.toISOString(),
            },
        });

        // Create updated entry for localStorage
        const updatedEntry = {
            ...state.currentEntry,
            started_at: startTime.toISOString(),
            is_running: true,
            is_paused: false,
            duration: 0,
            total_paused_time: 0,
            last_synced_at: startTime,
        };

        // Save to localStorage
        localStorage.saveCurrentEntry(updatedEntry);
        
        // Sync with server
        try {
            const response = await axios.post(route('api.timer.active.start'), {
                description: state.currentEntry.description,
                project_id: state.currentEntry.project_id,
                task_id: state.currentEntry.task_id,
            });
            
            // Update with server response
            if (response.data.timer) {
                dispatch({
                    type: 'SET_TIMER_STATE',
                    payload: {
                        id: response.data.timer.id,
                    },
                });
            }
            
            // Broadcast to other tabs
            broadcastTimerStart(updatedEntry);
        } catch (error) {
            // Timer will continue locally
        }
    };

    const pauseTimer = async () => {
        if (!state.currentEntry.is_running) return;

        const pausedAt = new Date();
        const updatedEntry = {
            ...state.currentEntry,
            is_running: false,
            is_paused: true,
            paused_at: pausedAt,
        };

        dispatch({
            type: 'SET_TIMER_STATE',
            payload: {
                is_running: false,
                is_paused: true,
                paused_at: pausedAt,
            },
        });

        localStorage.saveCurrentEntry(updatedEntry);
        
        // Sync with server
        try {
            await axios.post(route('api.timer.active.pause'));
            broadcastTimerPause(updatedEntry);
        } catch (error) {
            // Timer will pause locally regardless
        }
    };

    const resumeTimer = async () => {
        if (!state.currentEntry.is_paused || !state.currentEntry.paused_at) return;

        const now = new Date();
        const pauseDuration = Math.floor((now.getTime() - state.currentEntry.paused_at.getTime()) / 1000);
        const updatedEntry = {
            ...state.currentEntry,
            is_running: true,
            is_paused: false,
            paused_at: null,
            total_paused_time: state.currentEntry.total_paused_time + pauseDuration,
        };

        dispatch({
            type: 'SET_TIMER_STATE',
            payload: {
                is_running: true,
                is_paused: false,
                paused_at: null,
                total_paused_time: state.currentEntry.total_paused_time + pauseDuration,
            },
        });

        localStorage.saveCurrentEntry(updatedEntry);
        
        // Sync with server
        try {
            await axios.post(route('api.timer.active.resume'));
            broadcastTimerResume(updatedEntry);
        } catch (error) {
            // Timer will resume locally regardless
        }
    };

    const stopTimer = async () => {
        if (!state.currentEntry.is_running && !state.currentEntry.is_paused) return;

        const endTime = new Date();
        const duration = currentDuration;
        

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
            
            // Stop timer on server first
            if (isOnline) {
                // First try to stop the active timer
                try {
                    const response = await axios.post(route('api.timer.active.stop'));
                    
                    if (response.data.time_entry) {
                        dispatch({ type: 'ADD_RECENT_ENTRY', payload: response.data.time_entry });
                        toast.success('Tiempo registrado exitosamente');
                        
                        // Broadcast to other tabs
                        broadcastTimerStop(response.data.time_entry);
                    }
                } catch (stopError: any) {
                    // If no active timer found, create a time entry manually
                    if (stopError.response?.status === 400 && stopError.response?.data?.message?.includes('No active timer found')) {
                        // Create time entry directly
                        const timeEntryData = {
                            description: state.currentEntry.description || '',
                            project_id: state.currentEntry.project_id,
                            task_id: state.currentEntry.task_id,
                            started_at: state.currentEntry.started_at || new Date(Date.now() - duration * 1000).toISOString(),
                            ended_at: endTime.toISOString(),
                            duration: Math.floor(duration),
                            is_manual: false,
                            created_via: 'timer',
                        };
                        
                        const response = await axios.post(route('tenant.time.store'), timeEntryData);
                        
                        if (response.data.time_entry) {
                            dispatch({ type: 'ADD_RECENT_ENTRY', payload: response.data.time_entry });
                            toast.success('Tiempo registrado exitosamente');
                            broadcastTimerStop(response.data.time_entry);
                        }
                    } else {
                        throw stopError;
                    }
                }
            } else {
                // Queue stop request for offline sync
                const stopData = {
                    description: state.currentEntry.description,
                    project_id: state.currentEntry.project_id,
                    task_id: state.currentEntry.task_id,
                    started_at: state.currentEntry.started_at,
                    ended_at: endTime.toISOString(),
                    duration: Math.floor(duration),
                };
                
                queueTimerStop(stopData);
                toast.warning('Timer guardado localmente. Se sincronizará cuando vuelvas a estar online.');
            }
            
            dispatch({ type: 'RESET_CURRENT_ENTRY' });
            localStorage.clearCurrentEntry();
            
        } catch (error: any) {
            const errorMessage = error.response?.data?.message || 'Error al guardar la entrada de tiempo';
            
            dispatch({ type: 'SET_ERROR', payload: errorMessage });
            
            // Save the current entry as failed for later sync
            localStorage.saveFailedEntry({
                ...state.currentEntry,
                stopped_at: endTime.toISOString(),
                duration: Math.floor(duration),
            });
            
            // Reset the timer even if there was an error
            dispatch({ type: 'RESET_CURRENT_ENTRY' });
            localStorage.clearCurrentEntry();
            
            toast.error(errorMessage);
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const updateEntryDescription = (description: string) => {
        dispatch({ type: 'SET_ENTRY_DESCRIPTION', payload: description });
        // Use the latest state from ref
        const latestState = stateRef.current.currentEntry;
        localStorage.saveCurrentEntry({ ...latestState, description });
        
        // Sync with server if timer is active
        if (latestState.is_running || latestState.is_paused) {
            syncTimerWithServer();
            broadcastTimerUpdate({ ...latestState, description });
        }
    };

    const updateEntryProject = (projectId: number | null, taskId: number | null) => {
        dispatch({ type: 'SET_ENTRY_PROJECT', payload: { projectId, taskId } });
        // Use the latest state from ref
        const latestState = stateRef.current.currentEntry;
        localStorage.saveCurrentEntry({ ...latestState, project_id: projectId, task_id: taskId });
        
        // Sync with server if timer is active
        if (latestState.is_running || latestState.is_paused) {
            syncTimerWithServer();
            broadcastTimerUpdate({ ...latestState, project_id: projectId, task_id: taskId });
        }
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

            await axios.post(route('tenant.time.reminders.daily'), {
                hours_tracked: todaysTotalHours,
                hours_goal: state.preferences.dailyHoursGoal,
            });

            dispatch({ type: 'MARK_REMINDER_SENT' });
        } catch (error) {
            // Reminder failed silently
        }
    };

    const copyRowsFromPreviousWeek = async (targetWeekStart: string): Promise<{ project_id: number | null; task_id: number | null; project?: any; task?: any }[]> => {
        try {
            dispatch({ type: 'SET_LOADING', payload: true });
            
            const response = await axios.post(route('tenant.time.copy-previous-week-rows'), {
                target_week_start: targetWeekStart,
                create_entries: true, // Create entries directly in backend
            });

            if (response.data.created_count) {
                toast.success(`Se copiaron ${response.data.rows_count} fila${response.data.rows_count > 1 ? 's' : ''} de la hoja más reciente`);
                return []; // Return empty array since entries were created in backend
            } else if (response.data.rows && response.data.rows.length > 0) {
                // Legacy response format
                toast.success(`Se encontraron ${response.data.rows.length} fila${response.data.rows.length > 1 ? 's' : ''} de la hoja más reciente (semana del ${format(new Date(response.data.from_week), 'dd/MM', { locale: es })})`);
                return response.data.rows;
            }

            return [];
        } catch (error: any) {
            
            const errorMessage = error.response?.data?.message || 'Error al copiar filas de la hoja más reciente';
            dispatch({ type: 'SET_ERROR', payload: errorMessage });
            
            if (error.response?.status === 404) {
                toast.info('No hay hojas previas con datos para copiar');
                return [];
            }
            
            if (error.response?.status === 400) {
                toast.warning('La semana actual ya tiene entradas de tiempo');
                return [];
            }
            
            toast.error(errorMessage);
            throw error; // Re-throw to see it in the component
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const duplicatePreviousDay = async (fromDate: string, toDate: string): Promise<TimeEntry[]> => {
        try {
            dispatch({ type: 'SET_LOADING', payload: true });
            
            const response = await axios.post('/time/duplicate-day', {
                to_date: toDate,
            });

            // Response received
            
            if (response.data.entries && response.data.entries.length > 0) {
                response.data.entries.forEach((entry: TimeEntry) => {
                    dispatch({ type: 'ADD_RECENT_ENTRY', payload: entry });
                });
                // Mostrar mensaje de éxito desde aquí también
                toast.success(`Se duplicaron ${response.data.entries.length} entradas del día más reciente`);
            }

            return response.data.entries || [];
        } catch (error: any) {
            console.error('Error duplicating day:', error.response?.data);
            const errorMessage = error.response?.data?.message || 'Error al duplicar día';
            dispatch({ type: 'SET_ERROR', payload: errorMessage });
            
            // Si es 404, significa que no hay entradas para duplicar
            if (error.response?.status === 404) {
                toast.info('No se encontraron días anteriores con entradas de tiempo');
                return [];
            }
            
            // Si es 400, el día ya tiene entradas
            if (error.response?.status === 400) {
                toast.warning(errorMessage);
                return [];
            }
            
            // Si es 422, hubo un problema de validación
            if (error.response?.status === 422) {
                const validationErrors = error.response?.data?.errors;
                if (validationErrors) {
                    const firstError = Object.values(validationErrors)[0];
                    toast.error(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    toast.warning(errorMessage);
                }
                return [];
            }
            
            // Si es 500, error del servidor
            if (error.response?.status === 500) {
                toast.error('Error del servidor al duplicar entradas. Por favor revisa que los datos sean válidos.');
                return [];
            }
            
            // Otros errores
            toast.error(errorMessage);
            throw error;
        } finally {
            dispatch({ type: 'SET_LOADING', payload: false });
        }
    };

    const submitTimesheet = async (weekStart: Date, weekEnd: Date) => {
        if (state.approval.isSubmitted) return;

        try {
            dispatch({ type: 'SET_LOADING', payload: true });

            const response = await axios.post(route('tenant.time.timesheet.submit'), {
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
            const response = await axios.post(route('tenant.time.timesheet.approve'), {
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
            const response = await axios.get(route('tenant.time.preferences'));
            dispatch({ type: 'SET_PREFERENCES', payload: response.data.preferences });
        } catch (error) {
            // Use default preferences
        }
    };

    const loadSavedEntry = async () => {
        // Always start with a clean slate - timer at 00:00:00
        localStorage.clearCurrentEntry();
        dispatch({ type: 'RESET_CURRENT_ENTRY' });
    };

    const syncFailedEntries = async () => {
        const failedEntries = localStorage.getFailedEntries();
        if (!failedEntries.length) return;

        dispatch({ type: 'SET_LOADING', payload: true });

        for (const entry of failedEntries) {
            try {
                const response = await axios.post(route('tenant.time.store'), {
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
            } catch (error: any) {
                // Silently skip failed entries
            }
        }

        dispatch({ type: 'SET_LOADING', payload: false });
    };
    
    const updateTimeEntry = async (projectId: number | null, taskId: number | null, date: string, hours: number, description?: string, isPlaceholder: boolean = false) => {
        try {
            // Skip if no project_id (required field)
            if (!projectId) {
                toast.error('Por favor selecciona un proyecto');
                return;
            }
            
            const response = await axios.post(route('tenant.time.timesheet.quick-add'), {
                project_id: projectId,
                task_id: taskId,
                date: date,
                hours: hours,
                description: description || '',
                is_billable: true,
                is_placeholder: isPlaceholder
            });
            
            // Show success message only if not a placeholder
            if (!isPlaceholder) {
                if (hours === 0) {
                    toast.success('Entrada eliminada correctamente');
                } else {
                    toast.success('Entrada actualizada correctamente');
                }
            }
            
            return response.data;
        } catch (error: any) {
            console.error('Error updating time entry:', error);
            if (error.response?.status === 422) {
                const errors = error.response.data.errors;
                const firstError = Object.values(errors)[0];
                toast.error(Array.isArray(firstError) ? firstError[0] : firstError);
            } else if (error.response?.status === 403) {
                toast.error('La hoja de tiempo está bloqueada');
            } else {
                toast.error('Error al actualizar la entrada');
            }
            throw error;
        }
    };

    // Load saved data on mount
    useEffect(() => {
        // Add a small delay to ensure localStorage is available
        const timer = setTimeout(async () => {
            // Load saved entry (will try server first, then localStorage)
            await loadSavedEntry();
            await loadUserPreferences();
            // Temporarily disable sync to avoid errors
            // syncFailedEntries();
        }, 100);
        
        return () => clearTimeout(timer);
    }, []);
    
    // Save state ONLY when timer is actively running or paused
    useEffect(() => {
        if (state.currentEntry.is_running || state.currentEntry.is_paused) {
            localStorage.saveCurrentEntry(state.currentEntry);
        } else {
            // Timer is stopped, clear localStorage
            localStorage.clearCurrentEntry();
        }
    }, [
        state.currentEntry.is_running, 
        state.currentEntry.is_paused
    ]);
    
    // Sync with server periodically while timer is running
    useEffect(() => {
        if (state.currentEntry.is_running || state.currentEntry.is_paused) {
            // Save immediately
            localStorage.saveCurrentEntry(state.currentEntry);
            syncTimerWithServer();
            
            // Set up periodic sync
            syncIntervalRef.current = setInterval(() => {
                // Sync with server every 30 seconds
                const lastSync = lastSyncRef.current;
                if (!lastSync || new Date().getTime() - lastSync.getTime() > 30000) {
                    syncTimerWithServer();
                }
            }, 5000); // Check every 5 seconds
        } else {
            // Clear sync interval when timer is not active
            if (syncIntervalRef.current) {
                clearInterval(syncIntervalRef.current);
                syncIntervalRef.current = null;
            }
        }
        
        return () => {
            if (syncIntervalRef.current) {
                clearInterval(syncIntervalRef.current);
            }
        };
    }, [state.currentEntry.is_running, state.currentEntry.is_paused]);


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
        copyRowsFromPreviousWeek,
        submitTimesheet,
        approveTimesheet,
        lockTimesheet,
        loadUserPreferences,
        loadSavedEntry,
        syncFailedEntries,
        updateTimeEntry,
        // Getters
        hasActiveTimer,
        canStartNewTimer,
        todaysTotalHours,
        needsReminder: needsReminder,
        canEditTimesheet,
        timesheetStatus: timesheetStatus,
        currentDuration: currentDuration,
        formattedDuration: formattedDuration,
        canEditEntry,
        status: status,
    };

    // Handle page visibility changes to sync when tab becomes active
    useEffect(() => {
        const handleVisibilityChange = () => {
            if (!document.hidden && (state.currentEntry.is_running || state.currentEntry.is_paused)) {
                // Tab became visible, sync with server
                syncTimerWithServer();
            }
        };
        
        document.addEventListener('visibilitychange', handleVisibilityChange);
        
        return () => {
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
    }, [state.currentEntry.is_running, state.currentEntry.is_paused]);
    
    // Sync on window focus
    useEffect(() => {
        const handleFocus = () => {
            if (state.currentEntry.is_running || state.currentEntry.is_paused) {
                loadSavedEntry();
            }
        };
        
        window.addEventListener('focus', handleFocus);
        
        return () => {
            window.removeEventListener('focus', handleFocus);
        };
    }, [state.currentEntry.is_running, state.currentEntry.is_paused]);

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