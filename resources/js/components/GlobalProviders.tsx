import React, { ReactNode } from 'react';
import { TimeEntryProvider } from '@/stores/timeEntryStore';

interface GlobalProvidersProps {
    children: ReactNode;
}

export function GlobalProviders({ children }: GlobalProvidersProps) {
    return (
        <TimeEntryProvider>
            {children}
        </TimeEntryProvider>
    );
}