import { useCallback, useEffect, useRef, useState } from 'react';

interface IdleDetectionOptions {
    threshold?: number; // Seconds of inactivity
    onIdle?: () => void;
    onActive?: () => void;
    events?: string[];
}

export function useIdleDetection(options: IdleDetectionOptions = {}) {
    const {
        threshold = 600, // 10 minutes by default
        onIdle = () => {},
        onActive = () => {},
        events = ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll'],
    } = options;

    const [isIdle, setIsIdle] = useState(false);
    const [lastActivity, setLastActivity] = useState(Date.now());
    const [idleStarted, setIdleStarted] = useState<Date | null>(null);
    const checkIntervalRef = useRef<NodeJS.Timeout | null>(null);

    const resetActivity = useCallback(() => {
        const wasIdle = isIdle;
        setLastActivity(Date.now());
        setIsIdle(false);
        setIdleStarted(null);

        if (wasIdle) {
            onActive();
        }
    }, [isIdle, onActive]);

    const checkIdleStatus = useCallback(() => {
        const now = Date.now();
        const timeSinceActivity = (now - lastActivity) / 1000;

        if (!isIdle && timeSinceActivity >= threshold) {
            setIsIdle(true);
            setIdleStarted(new Date());
            onIdle();
        }
    }, [isIdle, lastActivity, threshold, onIdle]);

    const handleVisibilityChange = useCallback(() => {
        if (document.hidden) {
            // Mark as idle if tab is hidden
            if (!isIdle) {
                setIsIdle(true);
                setIdleStarted(new Date());
                onIdle();
            }
        } else {
            resetActivity();
        }
    }, [isIdle, onIdle, resetActivity]);

    const getIdleMinutes = useCallback(() => {
        if (!idleStarted) return 0;
        return Math.floor((Date.now() - idleStarted.getTime()) / 60000);
    }, [idleStarted]);

    useEffect(() => {
        // Add event listeners for activity
        events.forEach((event) => {
            window.addEventListener(event, resetActivity);
        });

        // Special listener for visibility change
        document.addEventListener('visibilitychange', handleVisibilityChange);

        // Start interval to check idle status
        checkIntervalRef.current = setInterval(checkIdleStatus, 10000); // Check every 10 seconds

        // Cleanup
        return () => {
            // Remove event listeners
            events.forEach((event) => {
                window.removeEventListener(event, resetActivity);
            });

            document.removeEventListener('visibilitychange', handleVisibilityChange);

            // Clear interval
            if (checkIntervalRef.current) {
                clearInterval(checkIntervalRef.current);
            }
        };
    }, [events, resetActivity, handleVisibilityChange, checkIdleStatus]);

    return {
        isIdle,
        lastActivity,
        idleStarted,
        getIdleMinutes,
        resetActivity,
    };
}
