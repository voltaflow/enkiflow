import { createContext, ReactNode, useContext, useState } from 'react';

interface TimeEntry {
    id: number;
    description: string;
    project_id: number | null;
    task_id: number | null;
    started_at: string;
    ended_at: string | null;
    duration: number;
}

interface TimeEntryContextType {
    entries: TimeEntry[];
    activeTimer: TimeEntry | null;
    setEntries: (entries: TimeEntry[]) => void;
    setActiveTimer: (timer: TimeEntry | null) => void;
    addEntry: (entry: TimeEntry) => void;
    updateEntry: (id: number, updates: Partial<TimeEntry>) => void;
    deleteEntry: (id: number) => void;
}

const TimeEntryContext = createContext<TimeEntryContextType | undefined>(undefined);

export function TimeEntryProvider({ children }: { children: ReactNode }) {
    const [entries, setEntries] = useState<TimeEntry[]>([]);
    const [activeTimer, setActiveTimer] = useState<TimeEntry | null>(null);

    const addEntry = (entry: TimeEntry) => {
        setEntries((prev) => [...prev, entry]);
    };

    const updateEntry = (id: number, updates: Partial<TimeEntry>) => {
        setEntries((prev) => prev.map((entry) => (entry.id === id ? { ...entry, ...updates } : entry)));
    };

    const deleteEntry = (id: number) => {
        setEntries((prev) => prev.filter((entry) => entry.id !== id));
    };

    const value: TimeEntryContextType = {
        entries,
        activeTimer,
        setEntries,
        setActiveTimer,
        addEntry,
        updateEntry,
        deleteEntry,
    };

    return <TimeEntryContext.Provider value={value}>{children}</TimeEntryContext.Provider>;
}

export function useTimeEntry() {
    const context = useContext(TimeEntryContext);
    if (context === undefined) {
        throw new Error('useTimeEntry must be used within a TimeEntryProvider');
    }
    return context;
}

// Alias for backward compatibility
export const useTimeEntryStore = useTimeEntry;
