import { useTimeEntryStore } from '@/stores/timeEntryStore';
import { useCallback, useEffect, useRef, useState } from 'react';

interface ReminderOptions {
    dailyGoal?: number;
    reminderTime?: string;
    enableNotifications?: boolean;
}

export function useTimeReminders(options: ReminderOptions = {}) {
    const { dailyGoal = 8, reminderTime = '17:00', enableNotifications = true } = options;

    const { todaysTotalHours, sendDailyReminder, state } = useTimeEntryStore();
    const [notificationPermission, setNotificationPermission] = useState<NotificationPermission>('default');
    const [nextReminderTime, setNextReminderTime] = useState<Date | null>(null);
    const timeoutRef = useRef<NodeJS.Timeout | null>(null);

    const requestNotificationPermission = useCallback(async () => {
        if ('Notification' in window && Notification.permission === 'default') {
            const permission = await Notification.requestPermission();
            setNotificationPermission(permission);
        } else if ('Notification' in window) {
            setNotificationPermission(Notification.permission);
        }
    }, []);

    const calculateNextReminderTime = useCallback(() => {
        const now = new Date();
        const [hours, minutes] = reminderTime.split(':').map(Number);

        const reminder = new Date();
        reminder.setHours(hours, minutes, 0, 0);

        // If time has already passed today, schedule for tomorrow
        if (reminder <= now) {
            reminder.setDate(reminder.getDate() + 1);
        }

        // Skip weekends
        while (reminder.getDay() === 0 || reminder.getDay() === 6) {
            reminder.setDate(reminder.getDate() + 1);
        }

        setNextReminderTime(reminder);
        return reminder;
    }, [reminderTime]);

    const shouldSendReminder = useCallback(() => {
        const hoursTracked = todaysTotalHours;
        return hoursTracked < dailyGoal && !state.reminders.dailySent;
    }, [todaysTotalHours, dailyGoal, state.reminders.dailySent]);

    const sendReminder = useCallback(async () => {
        if (!shouldSendReminder()) return;

        const hoursTracked = todaysTotalHours;
        const hoursRemaining = dailyGoal - hoursTracked;

        // Send browser notification if enabled
        if (enableNotifications && notificationPermission === 'granted') {
            new Notification('Recordatorio de Registro de Tiempo', {
                body: `Has registrado ${hoursTracked.toFixed(1)} horas hoy. Te faltan ${hoursRemaining.toFixed(1)} horas para alcanzar tu meta diaria.`,
                icon: '/icon-192.png',
                tag: 'time-reminder',
                requireInteraction: true,
            });
        }

        // Also send through other channels via store
        await sendDailyReminder();
    }, [shouldSendReminder, todaysTotalHours, dailyGoal, enableNotifications, notificationPermission, sendDailyReminder]);

    const scheduleNextReminder = useCallback(() => {
        const next = calculateNextReminderTime();
        const now = new Date();
        const timeUntilReminder = next.getTime() - now.getTime();

        if (timeUntilReminder > 0) {
            timeoutRef.current = setTimeout(() => {
                sendReminder();
                scheduleNextReminder(); // Schedule the next reminder
            }, timeUntilReminder);
        }
    }, [calculateNextReminderTime, sendReminder]);

    // Initialize on mount
    useEffect(() => {
        requestNotificationPermission();
        scheduleNextReminder();

        // Cleanup timeout on unmount
        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, [requestNotificationPermission, scheduleNextReminder]);

    return {
        notificationPermission,
        nextReminderTime,
        shouldSendReminder: shouldSendReminder(),
        sendReminder,
        requestNotificationPermission,
    };
}
