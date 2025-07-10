import { Card } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ReportData } from '@/stores/reportsStore';
import { Briefcase, DollarSign, TrendingUp, Users } from 'lucide-react';
import React from 'react';

interface BillingReportProps {
    data: ReportData | null;
    isLoading: boolean;
}

interface GroupedBillingData {
    project: { id: number; name: string } | null;
    users: Array<{
        user: { id: number; name: string } | null;
        duration: number;
        amount: number;
    }>;
    totalDuration: number;
    totalAmount: number;
}

export default function BillingReport({ data, isLoading }: BillingReportProps) {
    const formatDuration = (seconds: number): string => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        return `${hours}h ${minutes}m`;
    };

    const formatCurrency = (amount: number): string => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    };

    // Group data by project
    const groupDataByProject = (): GroupedBillingData[] => {
        if (!data) return [];

        const grouped = data.entries.reduce(
            (acc, entry) => {
                const projectKey = entry.project?.id || 'no-project';
                if (!acc[projectKey]) {
                    acc[projectKey] = {
                        project: entry.project,
                        users: new Map(),
                        totalDuration: 0,
                        totalAmount: 0,
                    };
                }

                const userKey = entry.user?.id || 'no-user';
                const userEntry = acc[projectKey].users.get(userKey) || {
                    user: entry.user,
                    duration: 0,
                    amount: 0,
                };

                userEntry.duration += entry.billable_duration;
                userEntry.amount += entry.amount;
                acc[projectKey].users.set(userKey, userEntry);

                acc[projectKey].totalDuration += entry.billable_duration;
                acc[projectKey].totalAmount += entry.amount;

                return acc;
            },
            {} as Record<string, any>,
        );

        return Object.values(grouped).map((item) => ({
            ...item,
            users: Array.from(item.users.values()),
        }));
    };

    if (isLoading) {
        return (
            <Card className="p-6">
                <div className="space-y-3">
                    <Skeleton className="h-20 w-full" />
                    <Skeleton className="h-8 w-full" />
                    <Skeleton className="h-8 w-full" />
                    <Skeleton className="h-8 w-full" />
                </div>
            </Card>
        );
    }

    if (!data || data.entries.length === 0) {
        return (
            <Card className="p-12">
                <div className="text-center">
                    <DollarSign className="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                    <p className="text-muted-foreground">No billable entries found for the selected period.</p>
                </div>
            </Card>
        );
    }

    const groupedData = groupDataByProject();
    const totalRevenue = data.meta.total_billable_amount;
    const totalBillableHours = data.meta.total_billable_duration;

    return (
        <div className="space-y-6">
            {/* Summary Cards */}
            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Card className="p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-muted-foreground text-sm">Total Revenue</p>
                            <p className="text-2xl font-bold">{formatCurrency(totalRevenue)}</p>
                        </div>
                        <DollarSign className="h-8 w-8 text-green-600" />
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-muted-foreground text-sm">Billable Hours</p>
                            <p className="text-2xl font-bold">{formatDuration(totalBillableHours)}</p>
                        </div>
                        <TrendingUp className="h-8 w-8 text-blue-600" />
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-muted-foreground text-sm">Active Projects</p>
                            <p className="text-2xl font-bold">{groupedData.length}</p>
                        </div>
                        <Briefcase className="h-8 w-8 text-purple-600" />
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-muted-foreground text-sm">Avg Rate</p>
                            <p className="text-2xl font-bold">{totalBillableHours > 0 ? formatCurrency(totalRevenue / totalBillableHours) : '$0'}</p>
                        </div>
                        <Users className="h-8 w-8 text-orange-600" />
                    </div>
                </Card>
            </div>

            {/* Detailed Billing Table */}
            <Card className="overflow-hidden">
                <div className="p-6">
                    <h3 className="mb-4 text-lg font-semibold">Billing Details by Project</h3>
                    <div className="-mx-6 overflow-x-auto px-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Project</TableHead>
                                    <TableHead>Team Member</TableHead>
                                    <TableHead className="text-right">Hours</TableHead>
                                    <TableHead className="text-right">Rate</TableHead>
                                    <TableHead className="text-right">Amount</TableHead>
                                    <TableHead className="text-right">% of Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {groupedData.map((projectData, projectIndex) => (
                                    <React.Fragment key={projectIndex}>
                                        {/* Project Header Row */}
                                        <TableRow className="bg-muted/50 font-semibold">
                                            <TableCell colSpan={2}>{projectData.project?.name || 'No Project'}</TableCell>
                                            <TableCell className="text-right">{formatDuration(projectData.totalDuration)}</TableCell>
                                            <TableCell className="text-right">-</TableCell>
                                            <TableCell className="text-right">{formatCurrency(projectData.totalAmount)}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Progress value={(projectData.totalAmount / totalRevenue) * 100} className="h-2 w-16" />
                                                    <span className="text-sm">{((projectData.totalAmount / totalRevenue) * 100).toFixed(1)}%</span>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                        {/* User Rows */}
                                        {projectData.users.map((userData, userIndex) => (
                                            <TableRow key={`${projectIndex}-${userIndex}`}>
                                                <TableCell></TableCell>
                                                <TableCell className="pl-8">{userData.user?.name || 'Unknown User'}</TableCell>
                                                <TableCell className="text-right">{formatDuration(userData.duration)}</TableCell>
                                                <TableCell className="text-right">
                                                    {userData.duration > 0 ? formatCurrency(userData.amount / userData.duration) : '-'}
                                                </TableCell>
                                                <TableCell className="text-right">{formatCurrency(userData.amount)}</TableCell>
                                                <TableCell className="text-muted-foreground text-right">
                                                    {((userData.amount / totalRevenue) * 100).toFixed(1)}%
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </React.Fragment>
                                ))}
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    <TableCell colSpan={2} className="font-semibold">
                                        Total
                                    </TableCell>
                                    <TableCell className="text-right font-semibold">{formatDuration(totalBillableHours)}</TableCell>
                                    <TableCell className="text-right font-semibold">
                                        {totalBillableHours > 0 ? formatCurrency(totalRevenue / totalBillableHours) : '-'}
                                    </TableCell>
                                    <TableCell className="text-right font-semibold">{formatCurrency(totalRevenue)}</TableCell>
                                    <TableCell className="text-right font-semibold">100%</TableCell>
                                </TableRow>
                            </TableFooter>
                        </Table>
                    </div>
                </div>
            </Card>
        </div>
    );
}
