import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ReportFilters as FiltersType } from '@/stores/reportsStore';
import { format, parseISO, isValid } from 'date-fns';
import { useState } from 'react';

interface ReportFiltersProps {
    projects: Array<{ id: number; name: string }>;
    users: Array<{ id: number; name: string; email: string }>;
    filters: FiltersType;
    onFiltersChange: (filters: Partial<FiltersType>) => void;
}

export default function ReportFilters({ projects, users, filters, onFiltersChange }: ReportFiltersProps) {
    const [localFilters, setLocalFilters] = useState(filters);

    const handleStartDateChange = (date: Date | undefined) => {
        if (date) {
            setLocalFilters({ ...localFilters, start_date: format(date, 'yyyy-MM-dd') });
        }
    };

    const handleEndDateChange = (date: Date | undefined) => {
        if (date) {
            setLocalFilters({ ...localFilters, end_date: format(date, 'yyyy-MM-dd') });
        }
    };

    const handleProjectChange = (value: string) => {
        const projectId = value === 'all' ? null : parseInt(value);
        setLocalFilters({ ...localFilters, project_id: projectId });
    };

    const handleUserChange = (value: string) => {
        const userId = value === 'all' ? null : parseInt(value);
        setLocalFilters({ ...localFilters, user_id: userId });
    };

    const handleGroupByChange = (value: string) => {
        setLocalFilters({
            ...localFilters,
            groupBy: value as 'project' | 'user' | 'client' | 'date',
        });
    };

    const applyFilters = () => {
        onFiltersChange(localFilters);
    };

    const resetFilters = () => {
        const defaultFilters: FiltersType = {
            start_date: format(new Date(new Date().getFullYear(), new Date().getMonth(), 1), 'yyyy-MM-dd'),
            end_date: format(new Date(), 'yyyy-MM-dd'),
            project_id: null,
            user_id: null,
            groupBy: 'project',
        };
        setLocalFilters(defaultFilters);
        onFiltersChange(defaultFilters);
    };

    // Quick date range presets
    const setQuickDateRange = (preset: string) => {
        const today = new Date();
        let startDate: Date;
        let endDate: Date = today;

        switch (preset) {
            case 'today':
                startDate = today;
                break;
            case 'yesterday':
                startDate = new Date(today.setDate(today.getDate() - 1));
                endDate = startDate;
                break;
            case 'last7days':
                startDate = new Date(today.setDate(today.getDate() - 7));
                break;
            case 'last30days':
                startDate = new Date(today.setDate(today.getDate() - 30));
                break;
            case 'thisMonth':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                break;
            case 'lastMonth':
                startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            case 'thisYear':
                startDate = new Date(today.getFullYear(), 0, 1);
                break;
            default:
                return;
        }

        const newFilters = {
            start_date: format(startDate, 'yyyy-MM-dd'),
            end_date: format(endDate, 'yyyy-MM-dd'),
        };
        setLocalFilters({ ...localFilters, ...newFilters });
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold">Filtros</h3>
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="sm" onClick={resetFilters}>
                        Restablecer
                    </Button>
                    <Button size="sm" onClick={applyFilters}>
                        Aplicar filtros
                    </Button>
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                {/* Start Date */}
                <div className="space-y-2">
                    <Label>Fecha de inicio</Label>
                    <DatePicker 
                        value={localFilters.start_date ? parseISO(localFilters.start_date) : undefined} 
                        onChange={handleStartDateChange} 
                        placeholder="Seleccionar fecha" 
                    />
                </div>

                {/* End Date */}
                <div className="space-y-2">
                    <Label>Fecha de fin</Label>
                    <DatePicker 
                        value={localFilters.end_date ? parseISO(localFilters.end_date) : undefined} 
                        onChange={handleEndDateChange} 
                        placeholder="Seleccionar fecha" 
                    />
                </div>

                {/* Quick Date Presets */}
                <div className="space-y-2 lg:col-span-3">
                    <Label>Selección rápida</Label>
                    <div className="flex flex-wrap gap-1">
                        {[
                            { label: 'Hoy', value: 'today' },
                            { label: 'Últimos 7 días', value: 'last7days' },
                            { label: 'Últimos 30 días', value: 'last30days' },
                            { label: 'Este mes', value: 'thisMonth' },
                            { label: 'Mes pasado', value: 'lastMonth' },
                        ].map((preset) => (
                            <Button key={preset.value} variant="ghost" size="xs" onClick={() => setQuickDateRange(preset.value)} className="text-xs">
                                {preset.label}
                            </Button>
                        ))}
                    </div>
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {/* Project Filter */}
                <div className="space-y-2">
                    <Label>Proyecto</Label>
                    <Select value={localFilters.project_id?.toString() || 'all'} onValueChange={handleProjectChange}>
                        <SelectTrigger>
                            <SelectValue placeholder="Todos los proyectos" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los proyectos</SelectItem>
                            {projects.map((project) => (
                                <SelectItem key={project.id} value={project.id.toString()}>
                                    {project.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* User Filter */}
                <div className="space-y-2">
                    <Label>Usuario</Label>
                    <Select value={localFilters.user_id?.toString() || 'all'} onValueChange={handleUserChange}>
                        <SelectTrigger>
                            <SelectValue placeholder="Todos los usuarios" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los usuarios</SelectItem>
                            {users.map((user) => (
                                <SelectItem key={user.id} value={user.id.toString()}>
                                    {user.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Group By */}
                <div className="space-y-2">
                    <Label>Agrupar por</Label>
                    <Select value={localFilters.groupBy || 'project'} onValueChange={handleGroupByChange}>
                        <SelectTrigger>
                            <SelectValue placeholder="Agrupar por..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="project">Proyecto</SelectItem>
                            <SelectItem value="user">Usuario</SelectItem>
                            <SelectItem value="date">Fecha</SelectItem>
                            <SelectItem value="client">Cliente</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>
    );
}
