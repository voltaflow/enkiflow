import React, { createContext, useContext, useState, useCallback, ReactNode } from 'react';
import axios from 'axios';

export interface TimeEntry {
    id: number;
    user_id: number;
    project_id: number | null;
    task_id: number | null;
    description: string;
    started_at: string;
    ended_at: string | null;
    duration: number;
    is_billable: boolean;
    created_via: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
    project?: {
        id: number;
        name: string;
        client?: {
            id: number;
            name: string;
        };
    };
    task?: {
        id: number;
        title: string;
    };
}

export interface ReportFilters {
    start_date: string;
    end_date: string;
    project_id?: number | null;
    user_id?: number | null;
    client_id?: number | null;
    is_billable?: boolean | null;
    groupBy?: 'day' | 'week' | 'month' | 'project' | 'user' | 'client';
}

export interface ReportEntry {
    date: string;
    project?: {
        id: number;
        name: string;
    };
    user?: {
        id: number;
        name: string;
    };
    duration: number;
    billable_duration: number;
    amount: number;
    day_of_week?: number;
    client_id?: number;
}

export interface ReportData {
    meta: {
        start_date: string;
        end_date: string;
        total_duration: number;
        total_billable_duration: number;
        total_billable_amount: number;
        filters: Record<string, any>;
        kpis: Record<string, any>;
    };
    project?: {
        id: number;
        name: string;
    };
    user?: {
        id: number;
        name: string;
    };
    entries: ReportEntry[];
}

export interface KpiMetrics {
    total_hours: number;
    billable_hours: number;
    non_billable_hours: number;
    total_revenue: number;
    average_daily_hours: number;
    utilization_rate: number;
    billable_utilization: number;
    capacity_utilization: number;
    average_hourly_rate: number;
    budget_burn_rate: number | null;
    overtime_hours: number;
    avg_time_to_entry: number;
    projects_count: number;
    tasks_completed: number;
    thresholds: {
        billable_utilization: {
            warning: number;
            critical: number;
        };
        budget_burn_rate: {
            warning: number;
            critical: number;
        };
        avg_time_to_entry: {
            warning: number;
            critical: number;
        };
    };
}

interface ReportsState {
    filters: ReportFilters;
    reportData: ReportData | null;
    metrics: KpiMetrics | null;
    isLoading: boolean;
    isExporting: boolean;
    error: string | null;
    exportJobs: Array<{
        id: string;
        status: 'pending' | 'processing' | 'completed' | 'failed';
        progress: number;
        url?: string;
    }>;
    activeExportId: string | null;
}

interface ReportsContextType {
    state: ReportsState;
    updateFilters: (filters: Partial<ReportFilters>) => void;
    fetchMetrics: () => Promise<void>;
    fetchReport: (reportType: string) => Promise<void>;
    exportReport: (format: 'csv' | 'pdf' | 'excel') => Promise<void>;
}

const ReportsContext = createContext<ReportsContextType | undefined>(undefined);

interface ReportsProviderProps {
    children: ReactNode;
    initialFilters?: Partial<ReportFilters>;
}

export const ReportsProvider: React.FC<ReportsProviderProps> = ({ 
    children, 
    initialFilters = {} 
}) => {
    // Default filters
    const defaultFilters: ReportFilters = {
        start_date: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
        end_date: new Date().toISOString().split('T')[0],
        project_id: null,
        user_id: null,
        client_id: null,
        is_billable: null,
        groupBy: 'day',
        ...initialFilters
    };

    const [state, setState] = useState<ReportsState>({
        filters: defaultFilters,
        reportData: null,
        metrics: null,
        isLoading: false,
        isExporting: false,
        error: null,
        exportJobs: [],
        activeExportId: null
    });

    // Update filters
    const updateFilters = useCallback((newFilters: Partial<ReportFilters>) => {
        setState(prev => ({
            ...prev,
            filters: { ...prev.filters, ...newFilters }
        }));
    }, []);

    // Fetch KPI metrics
    const fetchMetrics = useCallback(async () => {
        setState(prev => ({ ...prev, isLoading: true, error: null }));

        try {
            // Determine scope based on filters
            let scope = 'tenant'; // default for all users/projects
            let scope_id = undefined;
            
            if (state.filters.user_id) {
                scope = 'user';
                scope_id = state.filters.user_id;
            } else if (state.filters.project_id) {
                scope = 'project';
                scope_id = state.filters.project_id;
            }
            
            const response = await axios.get('/api/reports/metrics', {
                params: {
                    start_date: state.filters.start_date,
                    end_date: state.filters.end_date,
                    scope: scope,
                    scope_id: scope_id,
                }
            });

            setState(prev => ({
                ...prev,
                metrics: response.data,
                isLoading: false
            }));
        } catch (err: any) {
            setState(prev => ({
                ...prev,
                error: err.response?.data?.message || 'Error loading metrics',
                isLoading: false
            }));
        }
    }, [state.filters]);

    // Fetch report data
    const fetchReport = useCallback(async (reportType: string) => {
        setState(prev => ({ ...prev, isLoading: true, error: null }));

        const endpoints: Record<string, string> = {
            'date-range': '/api/reports/date-range',
            'billing': '/api/reports/billing',
            'summary': '/api/reports/summary',
            'weekly': '/api/reports/weekly'
        };

        try {
            // Prepare params based on report type
            const params: any = {
                start_date: state.filters.start_date,
                end_date: state.filters.end_date,
            };
            
            // For date-range and billing reports, filters go in a nested object
            if (reportType === 'date-range' || reportType === 'billing') {
                params.filters = {
                    project_id: state.filters.project_id || undefined,
                    user_id: state.filters.user_id || undefined,
                    client_id: state.filters.client_id || undefined,
                    is_billable: state.filters.is_billable !== null ? state.filters.is_billable : undefined,
                };
            } else {
                // For other reports, filters go directly in params
                params.project_id = state.filters.project_id || undefined;
                params.user_id = state.filters.user_id || undefined;
                params.group_by = state.filters.groupBy || undefined;
            }
            
            const response = await axios.get(endpoints[reportType] || endpoints['date-range'], {
                params
            });

            setState(prev => ({
                ...prev,
                reportData: response.data,
                isLoading: false
            }));
        } catch (err: any) {
            setState(prev => ({
                ...prev,
                error: err.response?.data?.message || 'Error loading report',
                isLoading: false
            }));
        }
    }, [state.filters]);

    // Export report
    const exportReport = useCallback(async (format: 'csv' | 'pdf' | 'excel') => {
        setState(prev => ({ ...prev, isExporting: true }));
        
        try {
            const response = await axios.post('/api/reports/export', {
                ...state.filters,
                format,
                project_id: state.filters.project_id || undefined,
                user_id: state.filters.user_id || undefined,
                client_id: state.filters.client_id || undefined,
                is_billable: state.filters.is_billable !== null ? state.filters.is_billable : undefined,
            }, {
                responseType: 'blob'
            });

            // Create download link
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `report-${new Date().toISOString().split('T')[0]}.${format}`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            
            setState(prev => ({ ...prev, isExporting: false }));
        } catch (err: any) {
            setState(prev => ({ ...prev, isExporting: false }));
            throw new Error(err.response?.data?.message || 'Error exporting report');
        }
    }, [state.filters]);

    const value: ReportsContextType = {
        state,
        updateFilters,
        fetchMetrics,
        fetchReport,
        exportReport,
    };

    return (
        <ReportsContext.Provider value={value}>
            {children}
        </ReportsContext.Provider>
    );
};

export const useReports = () => {
    const context = useContext(ReportsContext);
    if (!context) {
        throw new Error('useReports must be used within a ReportsProvider');
    }
    return context;
};