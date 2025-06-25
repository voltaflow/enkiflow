import React from 'react';
import { Badge } from '@/components/ui/badge';

interface DemoBadgeProps {
    isDemo?: boolean;
}

export function DemoBadge({ isDemo = false }: DemoBadgeProps) {
    if (!isDemo) return null;

    return (
        <Badge variant="secondary" className="bg-blue-100 text-blue-800">
            DEMO
        </Badge>
    );
}