import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { endOfMonth, endOfWeek, format, startOfMonth, startOfWeek } from 'date-fns';
import { Activity, BarChart3, Calendar, ChevronDown, ChevronUp, Clock, Download, Filter } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface Stats {
    today: {
        hours: number;
        entries: number;
    };
    week: {
        hours: number;
        billable_hours: number;
        projects: number;
    };
    month: {
        hours: number;
        average_daily: number;
    };
}

interface WeeklyDataPoint {
    date: string;
    day: string;
    total_hours: number;
    billable_hours: number;
    non_billable_hours: number;
    entry_count: number;
}

interface ProjectData {
    id: number;
    name: string;
    hours: number;
    percentage: number;
    color: string;
}

interface ProductivityTrend {
    date: string;
    total_hours: number;
    focus_hours: number;
    productivity_score: number;
    entry_count: number;
}

interface Props {
    stats: Stats;
    weeklyData: WeeklyDataPoint[];
    projectDistribution: ProjectData[];
    productivityTrends: ProductivityTrend[];
}

export default function Analytics({ stats, weeklyData, projectDistribution, productivityTrends }: Props) {
    const [selectedPeriod, setSelectedPeriod] = useState('week');
    const [isLoading, setIsLoading] = useState(false);
    const [chartData, setChartData] = useState<any>(null);

    // Calculate project percentages
    const totalProjectHours = projectDistribution.reduce((sum, p) => sum + p.hours, 0);
    const projectsWithPercentage = projectDistribution.map((p) => ({
        ...p,
        percentage: totalProjectHours > 0 ? (p.hours / totalProjectHours) * 100 : 0,
    }));

    // Find productivity trends
    const avgProductivity = productivityTrends.reduce((sum, t) => sum + t.productivity_score, 0) / productivityTrends.length;
    const productivityChange =
        productivityTrends.length >= 2
            ? productivityTrends[productivityTrends.length - 1].productivity_score - productivityTrends[0].productivity_score
            : 0;

    const loadPeriodData = async (period: string) => {
        setIsLoading(true);
        try {
            let startDate, endDate;

            switch (period) {
                case 'week':
                    startDate = startOfWeek(new Date());
                    endDate = endOfWeek(new Date());
                    break;
                case 'month':
                    startDate = startOfMonth(new Date());
                    endDate = endOfMonth(new Date());
                    break;
                case 'year':
                    startDate = new Date(new Date().getFullYear(), 0, 1);
                    endDate = new Date(new Date().getFullYear(), 11, 31);
                    break;
                default:
                    return;
            }

            const response = await axios.get('/api/analytics/data', {
                params: {
                    start_date: format(startDate, 'yyyy-MM-dd'),
                    end_date: format(endDate, 'yyyy-MM-dd'),
                    metric: 'hours',
                },
            });

            setChartData(response.data);
        } catch (error) {
            toast.error('Failed to load analytics data');
        } finally {
            setIsLoading(false);
        }
    };

    const exportReport = async () => {
        try {
            const response = await axios.post('/api/analytics/export', {
                start_date: format(startOfMonth(new Date()), 'yyyy-MM-dd'),
                end_date: format(endOfMonth(new Date()), 'yyyy-MM-dd'),
                format: 'pdf',
            });

            toast.info('Export functionality coming soon');
        } catch (error) {
            toast.error('Export not yet available');
        }
    };

    const StatCard = ({ title, value, subtitle, icon: Icon, trend }: any) => (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className="text-muted-foreground h-4 w-4" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                {subtitle && <p className="text-muted-foreground text-xs">{subtitle}</p>}
                {trend !== undefined && (
                    <div className={`mt-1 flex items-center text-xs ${trend > 0 ? 'text-green-600' : 'text-red-600'}`}>
                        {trend > 0 ? <ChevronUp className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
                        <span>{Math.abs(trend).toFixed(1)}%</span>
                    </div>
                )}
            </CardContent>
        </Card>
    );

    return (
        <AppLayout>
            <Head title="Analytics" />

            <div className="container mx-auto py-6">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-bold">Analytics Dashboard</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm">
                            <Filter className="mr-2 h-4 w-4" />
                            Filters
                        </Button>
                        <Button variant="outline" size="sm" onClick={exportReport}>
                            <Download className="mr-2 h-4 w-4" />
                            Export
                        </Button>
                    </div>
                </div>

                {/* Overview Stats */}
                <div className="mb-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Today's Hours"
                        value={`${stats.today.hours.toFixed(1)}h`}
                        subtitle={`${stats.today.entries} entries`}
                        icon={Clock}
                    />
                    <StatCard
                        title="This Week"
                        value={`${stats.week.hours.toFixed(1)}h`}
                        subtitle={`${stats.week.billable_hours.toFixed(1)}h billable`}
                        icon={Calendar}
                    />
                    <StatCard
                        title="This Month"
                        value={`${stats.month.hours.toFixed(1)}h`}
                        subtitle={`${stats.month.average_daily.toFixed(1)}h daily avg`}
                        icon={BarChart3}
                    />
                    <StatCard
                        title="Productivity"
                        value={`${avgProductivity.toFixed(0)}%`}
                        subtitle="Focus time ratio"
                        icon={Activity}
                        trend={productivityChange}
                    />
                </div>

                <Tabs defaultValue="overview" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="productivity">Productivity</TabsTrigger>
                        <TabsTrigger value="projects">Projects</TabsTrigger>
                        <TabsTrigger value="trends">Trends</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Weekly Overview</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {weeklyData.map((day) => (
                                        <div key={day.date} className="flex items-center justify-between">
                                            <div className="flex items-center gap-4">
                                                <span className="w-12 text-sm font-medium">{day.day}</span>
                                                <div className="w-64">
                                                    <Progress value={(day.total_hours / 8) * 100} className="h-2" />
                                                </div>
                                            </div>
                                            <div className="flex gap-4 text-sm">
                                                <span>{day.total_hours.toFixed(1)}h total</span>
                                                <span className="text-green-600">{day.billable_hours.toFixed(1)}h billable</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="productivity" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Productivity Score</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="mb-8 text-center">
                                    <div className="text-primary text-6xl font-bold">{avgProductivity.toFixed(0)}%</div>
                                    <p className="text-muted-foreground">Average focus time ratio</p>
                                </div>

                                <div className="space-y-4">
                                    <div>
                                        <div className="mb-2 flex justify-between text-sm">
                                            <span>Focus Time</span>
                                            <span>{productivityTrends[productivityTrends.length - 1]?.focus_hours.toFixed(1)}h</span>
                                        </div>
                                        <Progress value={75} className="h-2" />
                                    </div>

                                    <div>
                                        <div className="mb-2 flex justify-between text-sm">
                                            <span>Deep Work Sessions</span>
                                            <span>4 today</span>
                                        </div>
                                        <Progress value={80} className="h-2" />
                                    </div>

                                    <div>
                                        <div className="mb-2 flex justify-between text-sm">
                                            <span>Average Session Length</span>
                                            <span>45 min</span>
                                        </div>
                                        <Progress value={60} className="h-2" />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="projects" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Project Distribution</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {projectsWithPercentage.map((project) => (
                                        <div key={project.id}>
                                            <div className="mb-2 flex justify-between text-sm">
                                                <span className="font-medium">{project.name}</span>
                                                <span>
                                                    {project.hours.toFixed(1)}h ({project.percentage.toFixed(0)}%)
                                                </span>
                                            </div>
                                            <div className="relative">
                                                <Progress value={project.percentage} className="h-6" />
                                                <div
                                                    className="absolute inset-0 h-6 rounded-md opacity-20"
                                                    style={{ backgroundColor: project.color, width: `${project.percentage}%` }}
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="mt-6 border-t pt-6">
                                    <div className="flex justify-between">
                                        <span className="font-medium">Total</span>
                                        <span className="font-bold">{totalProjectHours.toFixed(1)} hours</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="trends" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>30-Day Trends</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-6">
                                    <div>
                                        <h4 className="mb-4 text-sm font-medium">Daily Hours</h4>
                                        <div className="flex h-32 items-end gap-1">
                                            {productivityTrends.slice(-14).map((trend, index) => (
                                                <div
                                                    key={trend.date}
                                                    className="bg-primary hover:bg-primary/80 flex-1 rounded-t transition-colors"
                                                    style={{
                                                        height: `${(trend.total_hours / 10) * 100}%`,
                                                        minHeight: '4px',
                                                    }}
                                                    title={`${format(new Date(trend.date), 'MMM d')}: ${trend.total_hours}h`}
                                                />
                                            ))}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-3 gap-4 text-center">
                                        <div>
                                            <p className="text-2xl font-bold">{stats.week.projects}</p>
                                            <p className="text-muted-foreground text-xs">Active Projects</p>
                                        </div>
                                        <div>
                                            <p className="text-2xl font-bold">{((stats.week.billable_hours / stats.week.hours) * 100).toFixed(0)}%</p>
                                            <p className="text-muted-foreground text-xs">Billable Ratio</p>
                                        </div>
                                        <div>
                                            <p className="text-2xl font-bold">{stats.month.average_daily.toFixed(1)}h</p>
                                            <p className="text-muted-foreground text-xs">Daily Average</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
