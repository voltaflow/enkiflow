import BillingReport from '@/components/reports/BillingReport';
import ExportReportDialog from '@/components/reports/ExportReportDialog';
import KpiDashboard from '@/components/reports/KpiDashboard';
import ProjectReport from '@/components/reports/ProjectReport';
import ReportFilters from '@/components/reports/ReportFilters';
import TimeEntriesTable from '@/components/reports/TimeEntriesTable';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { ReportsProvider, useReports } from '@/stores/reportsStore';
import { Head } from '@inertiajs/react';
import { Download, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface ReportsPageProps {
    projects: Array<{ id: number; name: string }>;
    users: Array<{ id: number; name: string; email: string }>;
    initialFilters: {
        start_date: string;
        end_date: string;
    };
}

function ReportsContent({ projects, users, initialFilters }: ReportsPageProps) {
    const { state, fetchMetrics, fetchReport, updateFilters } = useReports();
    const [activeTab, setActiveTab] = useState('overview');
    const [isExportDialogOpen, setIsExportDialogOpen] = useState(false);
    const [isRefreshing, setIsRefreshing] = useState(false);

    // Initialize filters and fetch initial data
    useEffect(() => {
        updateFilters(initialFilters);
        // Fetch data after filters are updated
        setTimeout(() => {
            fetchMetrics();
            fetchReport('date-range');
        }, 100);
    }, []);

    // Refresh data when filters change
    const handleFiltersChange = (newFilters: any) => {
        updateFilters(newFilters);
        refreshData();
    };

    // Refresh all data
    const refreshData = async () => {
        setIsRefreshing(true);
        try {
            await Promise.all([fetchMetrics(), fetchReport(activeTab === 'overview' ? 'date-range' : activeTab)]);
            toast.success('Data refreshed successfully');
        } catch (error) {
            toast.error('Failed to refresh data');
        } finally {
            setIsRefreshing(false);
        }
    };

    // Handle tab changes
    const handleTabChange = (value: string) => {
        setActiveTab(value);
        if (value !== 'overview') {
            fetchReport(value);
        }
    };

    return (
        <AppLayout>
            <Head title="Reports" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Reports & Analytics</h1>
                        <p className="text-muted-foreground mt-1">Track time, analyze productivity, and generate reports</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" onClick={refreshData} disabled={isRefreshing}>
                            <RefreshCw className={`mr-2 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                            Refresh
                        </Button>
                        <Button variant="default" size="sm" onClick={() => setIsExportDialogOpen(true)}>
                            <Download className="mr-2 h-4 w-4" />
                            Export Report
                        </Button>
                    </div>
                </div>

                {/* KPI Dashboard */}
                <KpiDashboard metrics={state.metrics} isLoading={state.isLoading} />

                {/* Filters */}
                <Card className="p-4">
                    <ReportFilters projects={projects} users={users} filters={state.filters} onFiltersChange={handleFiltersChange} />
                </Card>

                {/* Reports Tabs */}
                <Tabs value={activeTab} onValueChange={handleTabChange}>
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="billing">Billing</TabsTrigger>
                        <TabsTrigger value="summary">Summary</TabsTrigger>
                        <TabsTrigger value="weekly">Weekly</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="mt-6">
                        <TimeEntriesTable data={state.reportData} isLoading={state.isLoading} />
                    </TabsContent>

                    <TabsContent value="billing" className="mt-6">
                        <BillingReport data={state.reportData} isLoading={state.isLoading} />
                    </TabsContent>

                    <TabsContent value="summary" className="mt-6">
                        <ProjectReport data={state.reportData} isLoading={state.isLoading} groupBy={state.filters.groupBy || 'project'} />
                    </TabsContent>

                    <TabsContent value="weekly" className="mt-6">
                        <TimeEntriesTable data={state.reportData} isLoading={state.isLoading} isWeeklyView />
                    </TabsContent>
                </Tabs>

                {/* Export Dialog */}
                <ExportReportDialog open={isExportDialogOpen} onOpenChange={setIsExportDialogOpen} reportType={activeTab} />

                {/* Error Display */}
                {state.error && <div className="bg-destructive/10 text-destructive rounded-lg p-4">{state.error}</div>}
            </div>
        </AppLayout>
    );
}

export default function ReportsPage(props: ReportsPageProps) {
    return (
        <ReportsProvider>
            <ReportsContent {...props} />
        </ReportsProvider>
    );
}
