import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ReportData } from '@/stores/reportsStore';
import { format } from 'date-fns';
import React from 'react';

interface TimeEntriesTableProps {
    data: ReportData | null;
    isLoading: boolean;
    isWeeklyView?: boolean;
}

export default function TimeEntriesTable({ data, isLoading, isWeeklyView }: TimeEntriesTableProps) {
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

    const getDayOfWeek = (dayNumber: number): string => {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        // PostgreSQL EXTRACT(DOW) returns 0-6 (0=Sunday)
        return days[dayNumber] || '';
    };

    if (isLoading) {
        return (
            <Card className="p-6">
                <div className="space-y-3">
                    <Skeleton className="h-8 w-full" />
                    <Skeleton className="h-8 w-full" />
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
                    <p className="text-muted-foreground">No time entries found for the selected period.</p>
                </div>
            </Card>
        );
    }

    // Group entries by date for better display
    const groupedEntries = isWeeklyView
        ? data.entries.reduce(
              (acc, entry) => {
                  const key = entry.date;
                  if (!acc[key]) {
                      acc[key] = [];
                  }
                  acc[key].push(entry);
                  return acc;
              },
              {} as Record<string, typeof data.entries>,
          )
        : null;

    return (
        <Card className="overflow-hidden">
            <div className="p-6">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-lg font-semibold">Time Entries</h3>
                    <div className="text-muted-foreground text-sm">
                        Total: {formatDuration(data.meta.total_duration)} ({formatDuration(data.meta.total_billable_duration)} billable)
                    </div>
                </div>

                {/* Summary Stats */}
                <div className="mb-6 grid grid-cols-3 gap-4">
                    <div className="bg-muted/50 rounded-lg p-3">
                        <p className="text-muted-foreground text-sm">Total Hours</p>
                        <p className="text-xl font-bold">{formatDuration(data.meta.total_duration)}</p>
                    </div>
                    <div className="bg-muted/50 rounded-lg p-3">
                        <p className="text-muted-foreground text-sm">Billable Hours</p>
                        <p className="text-xl font-bold">{formatDuration(data.meta.total_billable_duration)}</p>
                    </div>
                    <div className="bg-muted/50 rounded-lg p-3">
                        <p className="text-muted-foreground text-sm">Total Amount</p>
                        <p className="text-xl font-bold">{formatCurrency(data.meta.total_billable_amount)}</p>
                    </div>
                </div>

                <div className="-mx-6 overflow-x-auto px-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Project</TableHead>
                                <TableHead>User</TableHead>
                                <TableHead className="text-right">Duration</TableHead>
                                <TableHead className="text-right">Billable</TableHead>
                                <TableHead className="text-right">Amount</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {isWeeklyView && groupedEntries
                                ? // Weekly grouped view
                                  Object.entries(groupedEntries).map(([date, entries]) => (
                                      <React.Fragment key={date}>
                                          <TableRow className="bg-muted/50">
                                              <TableCell colSpan={7} className="font-semibold">
                                                  {format(new Date(date), 'EEEE, MMM d')}
                                              </TableCell>
                                          </TableRow>
                                          {entries.map((entry, index) => (
                                              <TableRow key={`${date}-${index}`}>
                                                  <TableCell className="pl-8">{format(new Date(entry.date), 'HH:mm')}</TableCell>
                                                  <TableCell>
                                                      {entry.project?.name || <span className="text-muted-foreground">No project</span>}
                                                  </TableCell>
                                                  <TableCell>{entry.user?.name || '-'}</TableCell>
                                                  <TableCell className="text-right font-medium">{formatDuration(entry.duration)}</TableCell>
                                                  <TableCell className="text-right">{formatDuration(entry.billable_duration)}</TableCell>
                                                  <TableCell className="text-right">{formatCurrency(entry.amount)}</TableCell>
                                                  <TableCell>
                                                      {entry.billable_duration > 0 ? (
                                                          <Badge variant="default" className="text-xs">
                                                              Billable
                                                          </Badge>
                                                      ) : (
                                                          <Badge variant="secondary" className="text-xs">
                                                              Non-billable
                                                          </Badge>
                                                      )}
                                                  </TableCell>
                                              </TableRow>
                                          ))}
                                      </React.Fragment>
                                  ))
                                : // Regular view
                                  data.entries.map((entry, index) => (
                                      <TableRow key={index}>
                                          <TableCell>
                                              {format(new Date(entry.date), 'MMM d, yyyy')}
                                              {entry.day_of_week && (
                                                  <span className="text-muted-foreground block text-xs">{getDayOfWeek(entry.day_of_week)}</span>
                                              )}
                                          </TableCell>
                                          <TableCell>{entry.project?.name || <span className="text-muted-foreground">No project</span>}</TableCell>
                                          <TableCell>{entry.user?.name || '-'}</TableCell>
                                          <TableCell className="text-right font-medium">{formatDuration(entry.duration)}</TableCell>
                                          <TableCell className="text-right">{formatDuration(entry.billable_duration)}</TableCell>
                                          <TableCell className="text-right">{formatCurrency(entry.amount)}</TableCell>
                                          <TableCell>
                                              {entry.billable_duration > 0 ? (
                                                  <Badge variant="default" className="text-xs">
                                                      Billable
                                                  </Badge>
                                              ) : (
                                                  <Badge variant="secondary" className="text-xs">
                                                      Non-billable
                                                  </Badge>
                                              )}
                                          </TableCell>
                                      </TableRow>
                                  ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </Card>
    );
}
