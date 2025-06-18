import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import axios from 'axios';
import { format } from 'date-fns';
import { CheckCircle, Clock, Edit, FileText, Plus, Star, Trash2, Zap } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Template {
    id: number;
    name: string;
    description: string | null;
    default_hours: number;
    is_billable: boolean;
    usage_count: number;
    last_used_at: string | null;
    project?: {
        id: number;
        name: string;
    };
    task?: {
        id: number;
        name: string;
    };
    category?: {
        id: number;
        name: string;
    };
}

interface Props {
    onEntryCreated?: () => void;
    projectId?: number;
}

export default function TemplateSelector({ onEntryCreated, projectId }: Props) {
    const [templates, setTemplates] = useState<Template[]>([]);
    const [suggestions, setSuggestions] = useState<Template[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [selectedTemplates, setSelectedTemplates] = useState<Set<number>>(new Set());
    const [isBulkMode, setIsBulkMode] = useState(false);

    useEffect(() => {
        loadTemplates();
        loadSuggestions();
    }, [projectId]);

    const loadTemplates = async () => {
        try {
            const response = await axios.get('/api/templates');
            setTemplates(response.data.templates.data);
        } catch (error) {
            console.error('Failed to load templates:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const loadSuggestions = async () => {
        try {
            const params = projectId ? { project_id: projectId } : {};
            const response = await axios.get('/api/templates/suggestions', { params });
            setSuggestions(response.data.suggestions);
        } catch (error) {
            console.error('Failed to load suggestions:', error);
        }
    };

    const useTemplate = async (template: Template) => {
        try {
            const response = await axios.post(`/api/templates/${template.id}/use`, {
                started_at: new Date().toISOString(),
            });

            toast.success('Time entry created from template');
            onEntryCreated?.();
            loadTemplates(); // Refresh to update usage counts
        } catch (error) {
            toast.error('Failed to create time entry');
            console.error(error);
        }
    };

    const toggleTemplateSelection = (templateId: number) => {
        const newSelection = new Set(selectedTemplates);
        if (newSelection.has(templateId)) {
            newSelection.delete(templateId);
        } else {
            newSelection.add(templateId);
        }
        setSelectedTemplates(newSelection);
    };

    const useBulkTemplates = async () => {
        if (selectedTemplates.size === 0) {
            toast.error('Please select at least one template');
            return;
        }

        try {
            const response = await axios.post('/api/templates/bulk-use', {
                template_ids: Array.from(selectedTemplates),
                date: new Date().toISOString().split('T')[0],
            });

            toast.success(response.data.message);
            setSelectedTemplates(new Set());
            setIsBulkMode(false);
            onEntryCreated?.();
            loadTemplates();
        } catch (error) {
            toast.error('Failed to create time entries');
            console.error(error);
        }
    };

    const deleteTemplate = async (template: Template) => {
        if (!confirm(`Delete template "${template.name}"?`)) {
            return;
        }

        try {
            await axios.delete(`/api/templates/${template.id}`);
            toast.success('Template deleted');
            loadTemplates();
        } catch (error) {
            toast.error('Failed to delete template');
            console.error(error);
        }
    };

    if (isLoading) {
        return <div className="p-4 text-center">Loading templates...</div>;
    }

    return (
        <div className="space-y-6">
            {/* Suggested Templates */}
            {suggestions.length > 0 && (
                <div>
                    <h3 className="mb-3 flex items-center gap-2 text-sm font-medium">
                        <Zap className="h-4 w-4 text-yellow-500" />
                        Suggested Templates
                    </h3>
                    <div className="grid grid-cols-1 gap-2 md:grid-cols-2">
                        {suggestions.map((template) => (
                            <Card
                                key={template.id}
                                className="cursor-pointer transition-shadow hover:shadow-md"
                                onClick={() => useTemplate(template)}
                            >
                                <CardContent className="p-3">
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <h4 className="text-sm font-medium">{template.name}</h4>
                                            {template.description && <p className="text-muted-foreground mt-1 text-xs">{template.description}</p>}
                                            <div className="mt-2 flex items-center gap-2">
                                                <Badge variant="secondary" className="text-xs">
                                                    {template.default_hours}h
                                                </Badge>
                                                {template.project && (
                                                    <Badge variant="outline" className="text-xs">
                                                        {template.project.name}
                                                    </Badge>
                                                )}
                                                {template.usage_count > 10 && <Star className="h-3 w-3 text-yellow-500" />}
                                            </div>
                                        </div>
                                        <Button size="sm" variant="ghost">
                                            <Plus className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            )}

            {/* All Templates */}
            <div>
                <div className="mb-3 flex items-center justify-between">
                    <h3 className="flex items-center gap-2 text-sm font-medium">
                        <FileText className="h-4 w-4" />
                        All Templates
                    </h3>
                    <div className="flex gap-2">
                        <Button size="sm" variant={isBulkMode ? 'default' : 'outline'} onClick={() => setIsBulkMode(!isBulkMode)}>
                            {isBulkMode ? 'Cancel Bulk' : 'Bulk Mode'}
                        </Button>
                        {isBulkMode && selectedTemplates.size > 0 && (
                            <Button size="sm" onClick={useBulkTemplates}>
                                Use {selectedTemplates.size} Templates
                            </Button>
                        )}
                    </div>
                </div>

                {templates.length === 0 ? (
                    <Card>
                        <CardContent className="py-8 text-center">
                            <p className="text-muted-foreground">No templates yet</p>
                            <Button size="sm" className="mt-4">
                                <Plus className="mr-2 h-4 w-4" />
                                Create Template
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-2">
                        {templates.map((template) => (
                            <Card
                                key={template.id}
                                className={`transition-shadow hover:shadow-md ${
                                    !isBulkMode ? 'cursor-pointer' : ''
                                } ${selectedTemplates.has(template.id) ? 'ring-primary ring-2' : ''}`}
                                onClick={() => {
                                    if (isBulkMode) {
                                        toggleTemplateSelection(template.id);
                                    } else {
                                        useTemplate(template);
                                    }
                                }}
                            >
                                <CardContent className="p-3">
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                {isBulkMode && (
                                                    <CheckCircle
                                                        className={`h-4 w-4 ${
                                                            selectedTemplates.has(template.id) ? 'text-primary' : 'text-muted-foreground'
                                                        }`}
                                                    />
                                                )}
                                                <h4 className="font-medium">{template.name}</h4>
                                            </div>
                                            {template.description && <p className="text-muted-foreground mt-1 text-sm">{template.description}</p>}
                                            <div className="text-muted-foreground mt-2 flex items-center gap-3 text-xs">
                                                <span className="flex items-center gap-1">
                                                    <Clock className="h-3 w-3" />
                                                    {template.default_hours}h
                                                </span>
                                                {template.project && <span>{template.project.name}</span>}
                                                {template.task && <span>{template.task.name}</span>}
                                                <span>Used {template.usage_count} times</span>
                                                {template.last_used_at && <span>Last: {format(new Date(template.last_used_at), 'MMM d')}</span>}
                                            </div>
                                        </div>
                                        {!isBulkMode && (
                                            <div className="flex gap-1">
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        // TODO: Implement edit
                                                    }}
                                                >
                                                    <Edit className="h-3 w-3" />
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        deleteTemplate(template);
                                                    }}
                                                >
                                                    <Trash2 className="h-3 w-3" />
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
