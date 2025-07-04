import { TimeEntryProvider } from '@/stores/timeEntryStore';
import { ReactNode } from 'react';

interface GlobalProvidersProps {
    children: ReactNode;
}

export function GlobalProviders({ children }: GlobalProvidersProps) {
    return <TimeEntryProvider>{children}</TimeEntryProvider>;
}
