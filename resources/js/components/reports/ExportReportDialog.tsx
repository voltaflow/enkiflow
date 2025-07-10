import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { useReports } from '@/stores/reportsStore';
import { Download, FileImage, FileSpreadsheet, FileText, Loader2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface ExportReportDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    reportType: string;
}

export default function ExportReportDialog({ open, onOpenChange, reportType }: ExportReportDialogProps) {
    const { state, exportReport } = useReports();
    const [selectedFormat, setSelectedFormat] = useState<'csv' | 'pdf' | 'xlsx'>('csv');

    const handleExport = async () => {
        try {
            await exportReport(selectedFormat === 'xlsx' ? 'excel' : selectedFormat);
            toast.success(`Export started. You'll be notified when it's ready.`);
            onOpenChange(false);
        } catch (error) {
            toast.error('Failed to start export. Please try again.');
        }
    };

    // Get the active export job if any
    const activeJob = state.activeExportId ? state.exportJobs.find(job => job.id === state.activeExportId) : null;

    const formatIcons = {
        csv: <FileText className="h-5 w-5" />,
        pdf: <FileImage className="h-5 w-5" />,
        xlsx: <FileSpreadsheet className="h-5 w-5" />,
    };

    const formatDescriptions = {
        csv: 'Comma-separated values file. Best for data analysis in Excel or other spreadsheet applications.',
        pdf: 'Portable Document Format. Best for sharing and printing reports with formatting preserved.',
        xlsx: 'Microsoft Excel format. Best for advanced data manipulation and analysis.',
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Export Report</DialogTitle>
                    <DialogDescription>Choose a format to export your report. Large reports may take a few minutes to generate.</DialogDescription>
                </DialogHeader>

                {activeJob && activeJob.status === 'processing' ? (
                    <div className="space-y-4 py-8">
                        <div className="flex flex-col items-center text-center">
                            <Loader2 className="text-primary mb-4 h-8 w-8 animate-spin" />
                            <p className="text-sm font-medium">Generating your report...</p>
                            <p className="text-muted-foreground mt-1 text-xs">This may take a few minutes for large datasets</p>
                        </div>
                        <Progress value={activeJob.progress} className="w-full" />
                        <p className="text-muted-foreground text-center text-xs">{activeJob.progress}% complete</p>
                    </div>
                ) : (
                    <>
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label>Export Format</Label>
                                <RadioGroup value={selectedFormat} onValueChange={(value) => setSelectedFormat(value as any)}>
                                    {(['csv', 'pdf', 'xlsx'] as const).map((format) => (
                                        <div
                                            key={format}
                                            className="hover:bg-muted/50 flex cursor-pointer items-start space-x-3 rounded-lg p-3"
                                            onClick={() => setSelectedFormat(format)}
                                        >
                                            <RadioGroupItem value={format} id={format} className="mt-1" />
                                            <div className="flex-1 space-y-1">
                                                <Label htmlFor={format} className="flex cursor-pointer items-center gap-2 font-normal">
                                                    {formatIcons[format]}
                                                    <span className="font-semibold uppercase">{format}</span>
                                                </Label>
                                                <p className="text-muted-foreground text-xs">{formatDescriptions[format]}</p>
                                            </div>
                                        </div>
                                    ))}
                                </RadioGroup>
                            </div>

                            <div className="bg-muted/50 rounded-lg p-3">
                                <p className="mb-1 text-sm font-medium">Report Details</p>
                                <div className="text-muted-foreground space-y-1 text-xs">
                                    <p>Type: {reportType.charAt(0).toUpperCase() + reportType.slice(1)} Report</p>
                                    <p>
                                        Period: {state.filters.start_date} to {state.filters.end_date}
                                    </p>
                                    {state.filters.project_id && <p>Project filtered</p>}
                                    {state.filters.user_id && <p>User filtered</p>}
                                </div>
                            </div>

                            {/* Show recently completed exports */}
                            {state.exportJobs.some((job) => job.status === 'completed') && (
                                <div className="space-y-2">
                                    <Label className="text-xs">Recent Exports</Label>
                                    {state.exportJobs
                                        .filter((job) => job.status === 'completed')
                                        .slice(-3)
                                        .map((job) => (
                                            <div key={job.id} className="bg-muted/30 flex items-center justify-between rounded-lg p-2 text-xs">
                                                <span className="text-muted-foreground">Export ready</span>
                                                <Button variant="ghost" size="xs" onClick={() => window.open(job.url, '_blank')}>
                                                    <Download className="mr-1 h-3 w-3" />
                                                    Download
                                                </Button>
                                            </div>
                                        ))}
                                </div>
                            )}
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => onOpenChange(false)}>
                                Cancel
                            </Button>
                            <Button onClick={handleExport} disabled={state.isExporting}>
                                {state.isExporting ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Exporting...
                                    </>
                                ) : (
                                    <>
                                        <Download className="mr-2 h-4 w-4" />
                                        Export Report
                                    </>
                                )}
                            </Button>
                        </DialogFooter>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
