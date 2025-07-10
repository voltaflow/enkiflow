import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef } from 'react';

interface BroadcastMessage {
    type: 'timer_started' | 'timer_stopped' | 'entry_created' | 'entry_updated' | 'entry_deleted' | 'timesheet_updated' | 'tab_opened' | 'tab_closed';
    payload: any;
    timestamp: number;
    tabId: string;
}

export function useBroadcastSync(channelName: string = 'enkiflow_sync') {
    const channelRef = useRef<BroadcastChannel | null>(null);
    const tabIdRef = useRef<string>(`tab_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`);
    const lastMessageRef = useRef<string>('');

    useEffect(() => {
        // Create or get existing broadcast channel
        try {
            channelRef.current = new BroadcastChannel(channelName);

            // Listen for messages from other tabs
            channelRef.current.onmessage = (event: MessageEvent<BroadcastMessage>) => {
                const message = event.data;

                // Ignore messages from this tab
                if (message.tabId === tabIdRef.current) {
                    return;
                }

                // Deduplicate messages (in case of multiple listeners)
                const messageKey = `${message.type}_${JSON.stringify(message.payload)}_${message.timestamp}`;
                if (messageKey === lastMessageRef.current) {
                    return;
                }
                lastMessageRef.current = messageKey;

                // Handle different message types
                handleBroadcastMessage(message);
            };

            // Notify other tabs that this tab is active
            broadcast('tab_opened', { tabId: tabIdRef.current });

            // Cleanup on unmount
            return () => {
                if (channelRef.current) {
                    broadcast('tab_closed', { tabId: tabIdRef.current });
                    channelRef.current.close();
                    channelRef.current = null;
                }
            };
        } catch (error) {
            // BroadcastChannel not supported
        }
    }, [channelName]);

    const handleBroadcastMessage = useCallback((message: BroadcastMessage) => {
        switch (message.type) {
            case 'timer_started':
            case 'timer_stopped':
                // Reload timer data
                router.reload({
                    only: ['timer', 'activeTimer'],
                });
                break;

            case 'entry_created':
            case 'entry_updated':
            case 'entry_deleted':
                // Reload time entries
                router.reload({
                    only: ['timeEntries', 'entriesByProjectTask', 'dailyTotals', 'weekTotal'],
                });
                break;

            case 'timesheet_updated':
                // Reload timesheet data
                router.reload({
                    only: ['timesheet', 'entriesByProjectTask', 'dailyTotals', 'weekTotal'],
                });
                break;

            default:
                // Unknown message type - ignore
                break;
        }
    }, []);

    const broadcast = useCallback((type: BroadcastMessage['type'], payload: any = {}) => {
        if (!channelRef.current) {
            return;
        }

        const message: BroadcastMessage = {
            type,
            payload,
            timestamp: Date.now(),
            tabId: tabIdRef.current,
        };

        try {
            channelRef.current.postMessage(message);
        } catch (error) {
            // Failed to broadcast message
        }
    }, []);

    return {
        broadcast,
        tabId: tabIdRef.current,
        isSupported: typeof BroadcastChannel !== 'undefined',
    };
}

// Specific hooks for different features
export function useTimerSync() {
    const { broadcast, ...rest } = useBroadcastSync('enkiflow_timer_sync');

    const notifyTimerStarted = useCallback(
        (timer: any) => {
            broadcast('timer_started', { timer });
        },
        [broadcast],
    );

    const notifyTimerStopped = useCallback(
        (timer: any, timeEntry?: any) => {
            broadcast('timer_stopped', { timer, timeEntry });
        },
        [broadcast],
    );

    return {
        notifyTimerStarted,
        notifyTimerStopped,
        ...rest,
    };
}

export function useTimeEntrySync() {
    const { broadcast, ...rest } = useBroadcastSync('enkiflow_timeentry_sync');

    const notifyEntryCreated = useCallback(
        (entry: any) => {
            broadcast('entry_created', { entry });
        },
        [broadcast],
    );

    const notifyEntryUpdated = useCallback(
        (entry: any) => {
            broadcast('entry_updated', { entry });
        },
        [broadcast],
    );

    const notifyEntryDeleted = useCallback(
        (entryId: number) => {
            broadcast('entry_deleted', { entryId });
        },
        [broadcast],
    );

    return {
        notifyEntryCreated,
        notifyEntryUpdated,
        notifyEntryDeleted,
        ...rest,
    };
}

export function useTimesheetSync() {
    const { broadcast, ...rest } = useBroadcastSync('enkiflow_timesheet_sync');

    const notifyTimesheetUpdated = useCallback(
        (timesheetId: number, updates: any) => {
            broadcast('timesheet_updated', { timesheetId, updates });
        },
        [broadcast],
    );

    return {
        notifyTimesheetUpdated,
        ...rest,
    };
}

// Storage sync for offline support
export function useStorageSync(key: string) {
    const syncData = useCallback(
        (data: any) => {
            try {
                localStorage.setItem(
                    key,
                    JSON.stringify({
                        data,
                        timestamp: Date.now(),
                        tabId: `tab_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                    }),
                );

                // Dispatch storage event for other tabs
                window.dispatchEvent(
                    new StorageEvent('storage', {
                        key,
                        newValue: JSON.stringify(data),
                        url: window.location.href,
                    }),
                );
            } catch (error) {
                // Failed to sync to localStorage
            }
        },
        [key],
    );

    const getData = useCallback(() => {
        try {
            const stored = localStorage.getItem(key);
            if (stored) {
                const parsed = JSON.parse(stored);
                // Check if data is not too old (5 minutes)
                if (Date.now() - parsed.timestamp < 5 * 60 * 1000) {
                    return parsed.data;
                }
            }
        } catch (error) {
            // Failed to read from localStorage
        }
        return null;
    }, [key]);

    useEffect(() => {
        const handleStorageChange = (event: StorageEvent) => {
            if (event.key === key && event.newValue) {
                // Handle storage change from other tabs
                router.reload();
            }
        };

        window.addEventListener('storage', handleStorageChange);
        return () => window.removeEventListener('storage', handleStorageChange);
    }, [key]);

    return { syncData, getData };
}
