import { useCallback, useEffect, useRef, useState } from 'react';

interface QueuedRequest {
    id: string;
    url: string;
    method: string;
    data: any;
    timestamp: number;
    retryCount: number;
    maxRetries: number;
}

interface UseOfflineQueueOptions {
    queueKey?: string;
    maxRetries?: number;
    retryDelay?: number;
    onSync?: (request: QueuedRequest) => void;
    onError?: (request: QueuedRequest, error: any) => void;
}

const DEFAULT_QUEUE_KEY = 'enkiflow_offline_queue';
const DEFAULT_MAX_RETRIES = 3;
const DEFAULT_RETRY_DELAY = 5000; // 5 seconds

export function useOfflineQueue({
    queueKey = DEFAULT_QUEUE_KEY,
    maxRetries = DEFAULT_MAX_RETRIES,
    retryDelay = DEFAULT_RETRY_DELAY,
    onSync,
    onError,
}: UseOfflineQueueOptions = {}) {
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const [queueSize, setQueueSize] = useState(0);
    const syncTimeoutRef = useRef<NodeJS.Timeout>();
    const isSyncingRef = useRef(false);

    // Load queue from localStorage
    const loadQueue = useCallback((): QueuedRequest[] => {
        try {
            const stored = localStorage.getItem(queueKey);
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Error loading offline queue:', error);
            return [];
        }
    }, [queueKey]);

    // Save queue to localStorage
    const saveQueue = useCallback(
        (queue: QueuedRequest[]) => {
            try {
                localStorage.setItem(queueKey, JSON.stringify(queue));
                setQueueSize(queue.length);
            } catch (error) {
                console.error('Error saving offline queue:', error);
            }
        },
        [queueKey],
    );

    // Add request to queue
    const enqueue = useCallback(
        (url: string, method: string, data: any) => {
            const request: QueuedRequest = {
                id: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
                url,
                method,
                data,
                timestamp: Date.now(),
                retryCount: 0,
                maxRetries,
            };

            const queue = loadQueue();
            queue.push(request);
            saveQueue(queue);

            // Try to sync immediately if online
            if (isOnline) {
                scheduleSyncAttempt();
            }

            return request.id;
        },
        [isOnline, loadQueue, saveQueue, maxRetries],
    );

    // Remove request from queue
    const dequeue = useCallback(
        (requestId: string) => {
            const queue = loadQueue();
            const filtered = queue.filter((req) => req.id !== requestId);
            saveQueue(filtered);
        },
        [loadQueue, saveQueue],
    );

    // Execute a queued request
    const executeRequest = async (request: QueuedRequest): Promise<boolean> => {
        try {
            const response = await fetch(request.url, {
                method: request.method,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: request.data ? JSON.stringify(request.data) : undefined,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            onSync?.(request);
            return true;
        } catch (error) {
            console.error('Error executing queued request:', error);

            // Update retry count
            request.retryCount++;

            if (request.retryCount >= request.maxRetries) {
                onError?.(request, error);
                return true; // Remove from queue after max retries
            }

            return false;
        }
    };

    // Process the queue
    const processQueue = useCallback(async () => {
        if (isSyncingRef.current || !isOnline) {
            return;
        }

        isSyncingRef.current = true;
        const queue = loadQueue();
        const remainingQueue: QueuedRequest[] = [];

        for (const request of queue) {
            const success = await executeRequest(request);

            if (!success) {
                // Keep in queue for retry
                remainingQueue.push(request);
            }
        }

        saveQueue(remainingQueue);
        isSyncingRef.current = false;

        // Schedule next sync if there are remaining items
        if (remainingQueue.length > 0) {
            scheduleSyncAttempt();
        }
    }, [isOnline, loadQueue, saveQueue]);

    // Schedule a sync attempt
    const scheduleSyncAttempt = useCallback(() => {
        if (syncTimeoutRef.current) {
            clearTimeout(syncTimeoutRef.current);
        }

        syncTimeoutRef.current = setTimeout(() => {
            processQueue();
        }, retryDelay);
    }, [processQueue, retryDelay]);

    // Clear the queue
    const clearQueue = useCallback(() => {
        saveQueue([]);
    }, [saveQueue]);

    // Get queue contents
    const getQueue = useCallback(() => {
        return loadQueue();
    }, [loadQueue]);

    // Handle online/offline events
    useEffect(() => {
        const handleOnline = () => {
            setIsOnline(true);
            processQueue();
        };

        const handleOffline = () => {
            setIsOnline(false);
        };

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        // Check initial state
        setIsOnline(navigator.onLine);

        // Process queue on mount if online
        if (navigator.onLine) {
            processQueue();
        }

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);

            if (syncTimeoutRef.current) {
                clearTimeout(syncTimeoutRef.current);
            }
        };
    }, [processQueue]);

    // Update queue size when it changes
    useEffect(() => {
        setQueueSize(loadQueue().length);
    }, [loadQueue]);

    return {
        enqueue,
        dequeue,
        processQueue,
        clearQueue,
        getQueue,
        isOnline,
        queueSize,
        isProcessing: isSyncingRef.current,
    };
}

// Timer-specific offline queue hook
export function useTimerOfflineQueue() {
    const handleSync = useCallback((request: QueuedRequest) => {}, []);

    const handleError = useCallback((request: QueuedRequest, error: any) => {
        console.error('Timer request failed after max retries:', request, error);
    }, []);

    const queue = useOfflineQueue({
        queueKey: 'enkiflow_timer_offline_queue',
        maxRetries: 5,
        retryDelay: 3000,
        onSync: handleSync,
        onError: handleError,
    });

    // Timer-specific methods
    const queueTimerSync = useCallback(
        (timerData: any) => {
            return queue.enqueue('/api/timer/active/sync', 'POST', timerData);
        },
        [queue],
    );

    const queueTimerStop = useCallback(
        (timerData: any) => {
            return queue.enqueue('/api/timer/active/stop', 'POST', timerData);
        },
        [queue],
    );

    return {
        ...queue,
        queueTimerSync,
        queueTimerStop,
    };
}
