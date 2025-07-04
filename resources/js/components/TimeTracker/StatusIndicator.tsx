import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Circle, Pause, PlayCircle } from 'lucide-react';

interface StatusIndicatorProps {
    status: 'running' | 'paused' | 'stopped';
    duration: number;
    startTime: Date | string | null;
    showDuration?: boolean;
}

export function StatusIndicator({ status, duration, startTime, showDuration = true }: StatusIndicatorProps) {
    const formatDuration = (seconds: number) => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    const formatStartTime = (time: Date | string | null) => {
        if (!time) return '';
        const date = typeof time === 'string' ? new Date(time) : time;
        return format(date, 'HH:mm', { locale: es });
    };

    const getStatusConfig = () => {
        switch (status) {
            case 'running':
                return {
                    icon: PlayCircle,
                    label: 'En ejecución',
                    variant: 'default' as const,
                    className: 'bg-green-500 hover:bg-green-600 text-white',
                };
            case 'paused':
                return {
                    icon: Pause,
                    label: 'En pausa',
                    variant: 'secondary' as const,
                    className: 'bg-yellow-500 hover:bg-yellow-600 text-white',
                };
            case 'stopped':
            default:
                return {
                    icon: Circle,
                    label: 'Detenido',
                    variant: 'outline' as const,
                    className: '',
                };
        }
    };

    const config = getStatusConfig();
    const Icon = config.icon;

    return (
        <div className="flex items-center gap-4">
            <Badge variant={config.variant} className={`flex items-center gap-2 ${config.className}`}>
                <Icon className="h-3 w-3" />
                <span>{config.label}</span>
            </Badge>

            {showDuration && duration > 0 && <div className="text-muted-foreground text-sm">{formatDuration(duration)}</div>}

            {startTime && status !== 'stopped' && <div className="text-muted-foreground text-sm">Iniciado a las {formatStartTime(startTime)}</div>}
        </div>
    );
}
