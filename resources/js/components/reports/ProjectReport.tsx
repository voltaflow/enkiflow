import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ReportData } from '@/stores/reportsStore';
import { Bar, BarChart, CartesianGrid, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

interface ProjectReportProps {
    data: ReportData | null;
    isLoading: boolean;
    groupBy: 'project' | 'user' | 'client' | 'date';
}

interface ChartData {
    name: string;
    totalHours: number;
    billableHours: number;
    nonBillableHours: number;
    amount: number;
}

const COLORS = [
    '#3b82f6', // blue
    '#10b981', // emerald
    '#f59e0b', // amber
    '#ef4444', // red
    '#8b5cf6', // violet
    '#ec4899', // pink
    '#14b8a6', // teal
    '#f97316', // orange
];

export default function ProjectReport({ data, isLoading, groupBy }: ProjectReportProps) {
    const formatDuration = (seconds: number): string => {
        const hours = seconds / 3600;
        return hours.toFixed(1);
    };

    const formatCurrency = (amount: number): string => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    // Process data for charts
    const processChartData = (): ChartData[] => {
        if (!data) return [];

        const grouped = data.entries.reduce(
            (acc, entry) => {
                let key: string;
                switch (groupBy) {
                    case 'project':
                        key = entry.project?.name || 'No Project';
                        break;
                    case 'user':
                        key = entry.user?.name || 'Unknown User';
                        break;
                    case 'date':
                        key = new Date(entry.date).toLocaleDateString();
                        break;
                    default:
                        key = 'Unknown';
                }

                if (!acc[key]) {
                    acc[key] = {
                        name: key,
                        totalHours: 0,
                        billableHours: 0,
                        nonBillableHours: 0,
                        amount: 0,
                    };
                }

                acc[key].totalHours += entry.duration / 3600;
                acc[key].billableHours += entry.billable_duration / 3600;
                acc[key].nonBillableHours += (entry.duration - entry.billable_duration) / 3600;
                acc[key].amount += entry.amount;

                return acc;
            },
            {} as Record<string, ChartData>,
        );

        return Object.values(grouped).sort((a, b) => b.totalHours - a.totalHours);
    };

    // Process data for pie chart
    const processPieData = () => {
        const chartData = processChartData();
        return chartData.map((item, index) => ({
            name: item.name,
            value: item.totalHours,
            color: COLORS[index % COLORS.length],
        }));
    };

    if (isLoading) {
        return (
            <Card className="p-6">
                <div className="space-y-4">
                    <Skeleton className="h-8 w-48" />
                    <Skeleton className="h-64 w-full" />
                    <Skeleton className="h-64 w-full" />
                </div>
            </Card>
        );
    }

    if (!data || data.entries.length === 0) {
        return (
            <Card className="p-12">
                <div className="text-center">
                    <p className="text-muted-foreground">No data available for the selected period.</p>
                </div>
            </Card>
        );
    }

    const chartData = processChartData();
    const pieData = processPieData();

    const CustomTooltip = ({ active, payload, label }: any) => {
        if (active && payload && payload.length) {
            return (
                <div className="bg-background rounded-lg border p-3 shadow-lg">
                    <p className="font-semibold">{label}</p>
                    {payload.map((entry: any, index: number) => (
                        <p key={index} className="text-sm" style={{ color: entry.color }}>
                            {entry.name}: {formatDuration(entry.value)}h
                        </p>
                    ))}
                    <p className="mt-1 text-sm font-semibold">Amount: {formatCurrency(payload[0].payload.amount)}</p>
                </div>
            );
        }
        return null;
    };

    const PieTooltip = ({ active, payload }: any) => {
        if (active && payload && payload.length) {
            return (
                <div className="bg-background rounded-lg border p-3 shadow-lg">
                    <p className="font-semibold">{payload[0].name}</p>
                    <p className="text-sm">Hours: {formatDuration(payload[0].value)}</p>
                    <p className="text-sm">Percentage: {((payload[0].value / data.meta.total_duration) * 100).toFixed(1)}%</p>
                </div>
            );
        }
        return null;
    };

    return (
        <div className="space-y-6">
            {/* Summary Stats */}
            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                <Card className="p-4">
                    <p className="text-muted-foreground text-sm">Total Items</p>
                    <p className="text-2xl font-bold">{chartData.length}</p>
                </Card>
                <Card className="p-4">
                    <p className="text-muted-foreground text-sm">Total Hours</p>
                    <p className="text-2xl font-bold">{formatDuration(data.meta.total_duration)}</p>
                </Card>
                <Card className="p-4">
                    <p className="text-muted-foreground text-sm">Billable %</p>
                    <p className="text-2xl font-bold">{((data.meta.total_billable_duration / data.meta.total_duration) * 100).toFixed(1)}%</p>
                </Card>
                <Card className="p-4">
                    <p className="text-muted-foreground text-sm">Total Revenue</p>
                    <p className="text-2xl font-bold">{formatCurrency(data.meta.total_billable_amount)}</p>
                </Card>
            </div>

            {/* Charts */}
            <Tabs defaultValue="bar" className="w-full">
                <TabsList className="grid w-full grid-cols-2">
                    <TabsTrigger value="bar">Bar Chart</TabsTrigger>
                    <TabsTrigger value="pie">Distribution</TabsTrigger>
                </TabsList>

                <TabsContent value="bar" className="mt-6">
                    <Card className="p-6">
                        <h3 className="mb-4 text-lg font-semibold">Hours by {groupBy}</h3>
                        <div className="h-96">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={chartData} margin={{ top: 20, right: 30, left: 20, bottom: 60 }}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                    <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} className="text-xs" />
                                    <YAxis className="text-xs" />
                                    <Tooltip content={<CustomTooltip />} />
                                    <Legend />
                                    <Bar dataKey="billableHours" stackId="a" fill="#10b981" name="Billable Hours" />
                                    <Bar dataKey="nonBillableHours" stackId="a" fill="#ef4444" name="Non-Billable Hours" />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </Card>
                </TabsContent>

                <TabsContent value="pie" className="mt-6">
                    <Card className="p-6">
                        <h3 className="mb-4 text-lg font-semibold">Time Distribution by {groupBy}</h3>
                        <div className="h-96">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={pieData}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                        outerRadius={120}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {pieData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip content={<PieTooltip />} />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </Card>
                </TabsContent>
            </Tabs>

            {/* Top Items Table */}
            <Card className="p-6">
                <h3 className="mb-4 text-lg font-semibold">Top 10 by Hours</h3>
                <div className="space-y-2">
                    {chartData.slice(0, 10).map((item, index) => (
                        <div key={index} className="hover:bg-muted/50 flex items-center justify-between rounded-lg p-3">
                            <div className="flex items-center gap-3">
                                <div className="h-3 w-3 rounded-full" style={{ backgroundColor: COLORS[index % COLORS.length] }} />
                                <span className="font-medium">{item.name}</span>
                            </div>
                            <div className="flex items-center gap-6 text-sm">
                                <span>{formatDuration(item.totalHours)}h total</span>
                                <span className="text-green-600">{formatDuration(item.billableHours)}h billable</span>
                                <span className="font-semibold">{formatCurrency(item.amount)}</span>
                            </div>
                        </div>
                    ))}
                </div>
            </Card>
        </div>
    );
}
