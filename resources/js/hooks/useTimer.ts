import { useState, useRef, useEffect, useCallback } from 'react';

export function useTimer(initialSeconds = 0) {
    const [seconds, setSeconds] = useState(initialSeconds);
    const [isRunning, setIsRunning] = useState(false);
    const [isPaused, setIsPaused] = useState(false);
    const [startTime, setStartTime] = useState<Date | null>(null);
    const [pausedAt, setPausedAt] = useState<Date | null>(null);
    const [totalPausedTime, setTotalPausedTime] = useState(0);
    const intervalRef = useRef<NodeJS.Timeout | null>(null);

    // Format time as HH:MM:SS
    const formattedTime = (() => {
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    })();

    const updateTimer = useCallback(() => {
        if (!isRunning || !startTime) return;

        const now = new Date();
        const elapsedSeconds = Math.floor((now.getTime() - startTime.getTime()) / 1000);
        setSeconds(elapsedSeconds - totalPausedTime);
    }, [isRunning, startTime, totalPausedTime]);

    const start = useCallback(() => {
        if (isRunning) return;

        const now = new Date();
        setStartTime(now);
        setIsRunning(true);
        setIsPaused(false);
        setSeconds(initialSeconds);
        setTotalPausedTime(0);
    }, [isRunning, initialSeconds]);

    const pause = useCallback(() => {
        if (!isRunning || isPaused) return;

        setIsPaused(true);
        setIsRunning(false);
        setPausedAt(new Date());
    }, [isRunning, isPaused]);

    const resume = useCallback(() => {
        if (!isPaused || !pausedAt) return;

        const now = new Date();
        const pauseDuration = Math.floor((now.getTime() - pausedAt.getTime()) / 1000);
        setTotalPausedTime(prev => prev + pauseDuration);

        setIsPaused(false);
        setIsRunning(true);
        setPausedAt(null);
    }, [isPaused, pausedAt]);

    const stop = useCallback(() => {
        const finalSeconds = seconds;

        setIsRunning(false);
        setIsPaused(false);
        setSeconds(0);
        setStartTime(null);
        setPausedAt(null);
        setTotalPausedTime(0);

        return finalSeconds;
    }, [seconds]);

    const adjustDuration = useCallback((adjustmentSeconds: number) => {
        setSeconds(prev => Math.max(0, prev + adjustmentSeconds));
    }, []);

    // Set up interval to update timer
    useEffect(() => {
        if (isRunning && !isPaused) {
            intervalRef.current = setInterval(updateTimer, 1000);
        } else {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }
        }

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, [isRunning, isPaused, updateTimer]);

    return {
        seconds,
        formattedTime,
        isRunning,
        isPaused,
        start,
        pause,
        resume,
        stop,
        adjustDuration,
        startTime,
        pausedAt,
        totalPausedTime
    };
}