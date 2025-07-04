import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Calendar, CalendarDays, Clock } from 'lucide-react';

interface ViewSelectorProps {
    currentView: 'timer' | 'day' | 'week';
    onViewChange: (view: 'timer' | 'day' | 'week') => void;
}

export function ViewSelector({ currentView, onViewChange }: ViewSelectorProps) {
    const views = [
        { id: 'timer', label: 'Temporizador', icon: Clock },
        { id: 'day', label: 'Día', icon: Calendar },
        { id: 'week', label: 'Semana', icon: CalendarDays },
    ] as const;

    return (
        <div className="bg-muted flex w-fit rounded-lg p-1">
            {views.map((view) => {
                const Icon = view.icon;
                const isActive = currentView === view.id;

                return (
                    <Button
                        key={view.id}
                        variant={isActive ? 'default' : 'ghost'}
                        size="sm"
                        onClick={() => onViewChange(view.id)}
                        className={cn('flex items-center gap-2 px-3 py-2 transition-all', isActive && 'shadow-sm')}
                    >
                        <Icon className="h-4 w-4" />
                        <span>{view.label}</span>
                    </Button>
                );
            })}
        </div>
    );
}
