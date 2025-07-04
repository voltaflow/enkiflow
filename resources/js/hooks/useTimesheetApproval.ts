import { useTimeEntryStore } from '@/stores/timeEntryStore';
import axios from 'axios';
import { useCallback, useMemo, useState } from 'react';

export function useTimesheetApproval() {
    const {
        state,
        submitTimesheet: storeSubmitTimesheet,
        approveTimesheet: storeApproveTimesheet,
        lockTimesheet,
        todaysTotalHours,
        timesheetStatus,
    } = useTimeEntryStore();

    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isApproving, setIsApproving] = useState(false);

    const canSubmit = useMemo(
        () => !state.approval.isSubmitted && !state.approval.isLocked && todaysTotalHours > 0,
        [state.approval.isSubmitted, state.approval.isLocked, todaysTotalHours],
    );

    const canApprove = useMemo(
        () => state.approval.isSubmitted && !state.approval.isApproved && !state.approval.isLocked,
        [state.approval.isSubmitted, state.approval.isApproved, state.approval.isLocked],
    );

    const canEdit = useMemo(() => !state.approval.isLocked, [state.approval.isLocked]);

    const submitTimesheet = useCallback(
        async (weekStart: Date, weekEnd: Date) => {
            if (!canSubmit) return { success: false, error: 'Cannot submit timesheet' };

            setIsSubmitting(true);
            try {
                await storeSubmitTimesheet(weekStart, weekEnd);
                return { success: true };
            } catch (error) {
                return { success: false, error };
            } finally {
                setIsSubmitting(false);
            }
        },
        [canSubmit, storeSubmitTimesheet],
    );

    const approveTimesheet = useCallback(
        async (userId: number, weekStart: Date) => {
            if (!canApprove) return { success: false, error: 'Cannot approve timesheet' };

            setIsApproving(true);
            try {
                await storeApproveTimesheet(userId, weekStart);
                await lockTimesheet();
                return { success: true };
            } catch (error) {
                return { success: false, error };
            } finally {
                setIsApproving(false);
            }
        },
        [canApprove, storeApproveTimesheet, lockTimesheet],
    );

    const rejectTimesheet = useCallback(async (userId: number, weekStart: Date, reason: string) => {
        try {
            const response = await axios.post('/api/timesheets/reject', {
                user_id: userId,
                week_start: weekStart.toISOString(),
                reason,
            });

            // Reset approval status would be handled by the store
            // For now, we just return the response

            return { success: true, data: response.data };
        } catch (error) {
            return { success: false, error };
        }
    }, []);

    return {
        isSubmitting,
        isApproving,
        canSubmit,
        canApprove,
        canEdit,
        submitTimesheet,
        approveTimesheet,
        rejectTimesheet,
        status: timesheetStatus,
    };
}
