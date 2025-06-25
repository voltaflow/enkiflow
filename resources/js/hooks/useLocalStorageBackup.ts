const CURRENT_ENTRY_KEY = 'enkiflow_current_time_entry';
const FAILED_ENTRIES_KEY = 'enkiflow_failed_time_entries';
const PREFERENCES_KEY = 'enkiflow_time_preferences';
const IDLE_STATE_KEY = 'enkiflow_idle_state';

interface StoredEntry {
    [key: string]: any;
    startTime?: string;
    endTime?: string;
    pausedAt?: string;
    lastSyncedAt?: string;
}

interface StoredPreferences {
    dailyHoursGoal: number;
    reminderTime: string;
    enableIdleDetection: boolean;
    enableReminders: boolean;
}

export function useLocalStorageBackup() {
    const saveCurrentEntry = (entry: any) => {
        try {
            const toStore: StoredEntry = {
                ...entry,
                startTime: entry.started_at,
                endTime: entry.stopped_at,
                pausedAt: entry.paused_at?.toISOString ? entry.paused_at.toISOString() : entry.paused_at,
                lastSyncedAt: new Date().toISOString()
            };
            localStorage.setItem(CURRENT_ENTRY_KEY, JSON.stringify(toStore));
        } catch (error) {
            // Silently fail
        }
    };

    const getSavedEntry = (): any | null => {
        try {
            const savedEntry = localStorage.getItem(CURRENT_ENTRY_KEY);
            if (!savedEntry) {
                return null;
            }

            const entry = JSON.parse(savedEntry);

            // Convert strings to Date where needed
            if (entry.pausedAt) entry.paused_at = new Date(entry.pausedAt);

            return entry;
        } catch (error) {
            return null;
        }
    };

    const clearCurrentEntry = () => {
        try {
            localStorage.removeItem(CURRENT_ENTRY_KEY);
        } catch (error) {
            // Silently fail
        }
    };

    const saveFailedEntry = (entry: any) => {
        try {
            const failedEntries = getFailedEntries();

            failedEntries.push({
                ...entry,
                startTime: entry.started_at,
                endTime: entry.stopped_at,
                failedAt: new Date().toISOString()
            });

            localStorage.setItem(FAILED_ENTRIES_KEY, JSON.stringify(failedEntries));
        } catch (error) {
            // Silently fail
        }
    };

    const getFailedEntries = (): any[] => {
        try {
            const entries = localStorage.getItem(FAILED_ENTRIES_KEY);
            return entries ? JSON.parse(entries) : [];
        } catch (error) {
            return [];
        }
    };

    const removeFailedEntry = (entry: any) => {
        try {
            const failedEntries = getFailedEntries();
            const updatedEntries = failedEntries.filter((e: any) =>
                e.startTime !== entry.started_at ||
                e.description !== entry.description
            );

            localStorage.setItem(FAILED_ENTRIES_KEY, JSON.stringify(updatedEntries));
        } catch (error) {
            // Silently fail
        }
    };

    const savePreferences = (prefs: StoredPreferences) => {
        try {
            localStorage.setItem(PREFERENCES_KEY, JSON.stringify(prefs));
        } catch (error) {
            // Silently fail
        }
    };

    const getPreferences = (): StoredPreferences | null => {
        try {
            const prefs = localStorage.getItem(PREFERENCES_KEY);
            return prefs ? JSON.parse(prefs) : null;
        } catch (error) {
            return null;
        }
    };

    const saveIdleState = (idleData: any) => {
        try {
            localStorage.setItem(IDLE_STATE_KEY, JSON.stringify({
                ...idleData,
                timestamp: new Date().toISOString()
            }));
        } catch (error) {
            // Silently fail
        }
    };

    const getIdleState = () => {
        try {
            const state = localStorage.getItem(IDLE_STATE_KEY);
            return state ? JSON.parse(state) : null;
        } catch (error) {
            return null;
        }
    };

    return {
        saveCurrentEntry,
        getSavedEntry,
        clearCurrentEntry,
        saveFailedEntry,
        getFailedEntries,
        removeFailedEntry,
        savePreferences,
        getPreferences,
        saveIdleState,
        getIdleState
    };
}