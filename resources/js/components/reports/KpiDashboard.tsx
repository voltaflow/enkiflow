import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { KpiMetrics } from '@/stores/reportsStore';
import { AlertTriangle, Clock, DollarSign, Timer, TrendingDown, TrendingUp, Users, Zap } from 'lucide-react';
import React from 'react';

interface KpiDashboardProps {
    metrics: KpiMetrics | null;
    isLoading: boolean;
}

interface KpiCardProps {
    title: string;
    value: string | number;
    unit?: string;
    icon: React.ElementType;
    trend?: 'up' | 'down' | 'neutral';
    status?: 'good' | 'warning' | 'critical';
    description?: string;
    isLoading?: boolean;
}

function KpiCard({ title, value, unit, icon: Icon, trend, status = 'good', description, isLoading }: KpiCardProps) {
    if (isLoading) {
        return (
            <Card className="p-6">
                <div className="space-y-3">
                    <Skeleton className="h-4 w-32" />
                    <Skeleton className="h-8 w-24" />
                    <Skeleton className="h-3 w-full" />
                </div>
            </Card>
        );
    }

    const statusColors = {
        good: 'text-green-600 bg-green-50 dark:bg-green-950/20',
        warning: 'text-yellow-600 bg-yellow-50 dark:bg-yellow-950/20',
        critical: 'text-red-600 bg-red-50 dark:bg-red-950/20',
    };

    const trendIcons = {
        up: <TrendingUp className="h-4 w-4" />,
        down: <TrendingDown className="h-4 w-4" />,
    };

    return (
        <Card className="p-6 transition-shadow hover:shadow-lg">
            <div className="flex items-start justify-between">
                <div className="flex-1 space-y-3">
                    <p className="text-muted-foreground text-sm font-medium">{title}</p>
                    <div className="flex items-baseline gap-2">
                        <span className="text-2xl font-bold">{value}</span>
                        {unit && <span className="text-muted-foreground text-sm">{unit}</span>}
                        {trend && (
                            <span className={cn('flex items-center gap-1 text-sm', trend === 'up' ? 'text-green-600' : 'text-red-600')}>
                                {trendIcons[trend]}
                            </span>
                        )}
                    </div>
                    {description && <p className="text-muted-foreground text-xs">{description}</p>}
                </div>
                <div className={cn('rounded-lg p-3', statusColors[status])}>
                    <Icon className="h-6 w-6" />
                </div>
            </div>
        </Card>
    );
}

export default function KpiDashboard({ metrics, isLoading }: KpiDashboardProps) {
    if (!metrics && !isLoading) {
        return null;
    }

    // Calculate KPI statuses based on thresholds
    const getBillableStatus = (utilization: number): 'good' | 'warning' | 'critical' => {
        if (!metrics) return 'good';
        const { warning, critical } = metrics.thresholds.billable_utilization;
        if (utilization < critical) return 'critical';
        if (utilization < warning) return 'warning';
        return 'good';
    };

    const getBudgetStatus = (burnRate: number | null): 'good' | 'warning' | 'critical' => {
        if (!burnRate || !metrics) return 'good';
        const { warning, critical } = metrics.thresholds.budget_burn_rate;
        if (burnRate > critical) return 'critical';
        if (burnRate > warning) return 'warning';
        return 'good';
    };

    const getTimeToEntryStatus = (hours: number): 'good' | 'warning' | 'critical' => {
        if (!metrics) return 'good';
        const { warning, critical } = metrics.thresholds.avg_time_to_entry;
        if (hours > critical) return 'critical';
        if (hours > warning) return 'warning';
        return 'good';
    };

    const formatHours = (hours: number): string => {
        return hours.toFixed(1);
    };

    const formatPercentage = (value: number): string => {
        return `${value.toFixed(1)}%`;
    };

    const formatCurrency = (value: number): string => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    };

    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <KpiCard
                title="Total Hours"
                value={metrics ? formatHours(metrics.total_hours) : '-'}
                unit="hours"
                icon={Clock}
                isLoading={isLoading}
                description="Total time tracked"
            />

            <KpiCard
                title="Billable Utilization"
                value={metrics ? formatPercentage(metrics.billable_utilization) : '-'}
                icon={Zap}
                status={metrics ? getBillableStatus(metrics.billable_utilization) : 'good'}
                isLoading={isLoading}
                description={`Target: >${metrics?.thresholds.billable_utilization.warning || 70}%`}
            />

            <KpiCard
                title="Capacity Utilization"
                value={metrics ? formatPercentage(metrics.capacity_utilization) : '-'}
                icon={Users}
                isLoading={isLoading}
                description="Hours logged vs available"
            />

            <KpiCard
                title="Average Rate"
                value={metrics ? formatCurrency(metrics.average_hourly_rate) : '-'}
                icon={DollarSign}
                isLoading={isLoading}
                description="Per billable hour"
            />

            <KpiCard
                title="Billable Hours"
                value={metrics ? formatHours(metrics.billable_hours) : '-'}
                unit="hours"
                icon={DollarSign}
                isLoading={isLoading}
                description={`${metrics ? formatHours(metrics.non_billable_hours) : '-'} non-billable`}
            />

            <KpiCard
                title="Budget Burn Rate"
                value={metrics?.budget_burn_rate ? formatPercentage(metrics.budget_burn_rate) : 'N/A'}
                icon={TrendingUp}
                status={metrics ? getBudgetStatus(metrics.budget_burn_rate) : 'good'}
                isLoading={isLoading}
                description="For projects with budgets"
            />

            <KpiCard
                title="Overtime Hours"
                value={metrics ? formatHours(metrics.overtime_hours) : '-'}
                unit="hours"
                icon={AlertTriangle}
                status={metrics && metrics.overtime_hours > 10 ? 'warning' : 'good'}
                isLoading={isLoading}
                description="Beyond standard hours"
            />

            <KpiCard
                title="Time to Entry"
                value={metrics ? formatHours(metrics.avg_time_to_entry) : '-'}
                unit="hours"
                icon={Timer}
                status={metrics ? getTimeToEntryStatus(metrics.avg_time_to_entry) : 'good'}
                isLoading={isLoading}
                description="Average delay in logging"
            />
        </div>
    );
}
