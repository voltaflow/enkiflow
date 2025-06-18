import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import axios from 'axios';
import { format } from 'date-fns';
import { Clock, Plus, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface Project {
    id: number;
    name: string;
}

interface Task {
    id: number;
    name: string;
    project_id: number;
}

interface Props {
    projects: Project[];
    tasks: Task[];
    onEntryCreated?: () => void;
}

interface ParsedEntry {
    description: string;
    duration_hours: number;
    date: string;
    project?: string;
    task?: string;
    is_billable: boolean;
}

export default function NaturalLanguageInput({ projects, tasks, onEntryCreated }: Props) {
    const [input, setInput] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    const [parsedEntry, setParsedEntry] = useState<ParsedEntry | null>(null);
    const [showPreview, setShowPreview] = useState(false);

    const examples = [
        'Worked on API integration for 2.5 hours today',
        '3h meeting with client yesterday',
        'Fixed bug in payment system 45 minutes',
        '2 hours code review for Project X',
        '1.5h designing new landing page on Monday',
    ];

    const parseNaturalLanguage = (text: string): ParsedEntry | null => {
        // Extract duration patterns
        const durationPatterns = [
            /(\d+(?:\.\d+)?)\s*h(?:ours?)?/i,
            /(\d+(?:\.\d+)?)\s*hours?/i,
            /(\d+)\s*m(?:ins?|inutes?)/i,
            /for\s+(\d+(?:\.\d+)?)\s+hours?/i,
        ];

        let hours = 0;
        for (const pattern of durationPatterns) {
            const match = text.match(pattern);
            if (match) {
                const value = parseFloat(match[1]);
                if (pattern.toString().includes('m')) {
                    hours = value / 60;
                } else {
                    hours = value;
                }
                break;
            }
        }

        if (hours === 0) {
            return null;
        }

        // Extract date
        let date = format(new Date(), 'yyyy-MM-dd');
        if (text.toLowerCase().includes('yesterday')) {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            date = format(yesterday, 'yyyy-MM-dd');
        } else if (text.toLowerCase().includes('monday')) {
            // Find last Monday
            const today = new Date();
            const dayOfWeek = today.getDay();
            const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
            const monday = new Date(today);
            monday.setDate(today.getDate() - daysToMonday);
            date = format(monday, 'yyyy-MM-dd');
        }
        // Add more date parsing as needed

        // Extract project/task references
        let projectName: string | undefined;
        let taskName: string | undefined;

        // Check for project names in the text
        for (const project of projects) {
            if (text.toLowerCase().includes(project.name.toLowerCase())) {
                projectName = project.name;
                break;
            }
        }

        // Check for task names
        for (const task of tasks) {
            if (text.toLowerCase().includes(task.name.toLowerCase())) {
                taskName = task.name;
                // If task is found, use its project
                const project = projects.find((p) => p.id === task.project_id);
                if (project) {
                    projectName = project.name;
                }
                break;
            }
        }

        // Clean description by removing time and date references
        let description = text;
        durationPatterns.forEach((pattern) => {
            description = description.replace(pattern, '');
        });
        description = description
            .replace(/\b(today|yesterday|monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/gi, '')
            .replace(/\b(for|on)\b/gi, '')
            .replace(/\s+/g, ' ')
            .trim();

        // Determine if billable (default to true unless specified)
        const is_billable = !text.toLowerCase().includes('non-billable') && !text.toLowerCase().includes('internal');

        return {
            description,
            duration_hours: hours,
            date,
            project: projectName,
            task: taskName,
            is_billable,
        };
    };

    const handleSubmit = async () => {
        const parsed = parseNaturalLanguage(input);
        if (!parsed) {
            toast.error('Could not parse time entry. Please include a duration (e.g., "2 hours", "45 minutes")');
            return;
        }

        setParsedEntry(parsed);
        setShowPreview(true);
    };

    const confirmEntry = async () => {
        if (!parsedEntry) return;

        setIsProcessing(true);
        try {
            // Find project and task IDs
            const project = projects.find((p) => p.name === parsedEntry.project);
            const task = tasks.find((t) => t.name === parsedEntry.task);

            const response = await axios.post('/time', {
                description: parsedEntry.description,
                started_at: `${parsedEntry.date} 09:00:00`, // Default start time
                duration: parsedEntry.duration_hours * 3600, // Convert to seconds
                project_id: project?.id || null,
                task_id: task?.id || null,
                is_billable: parsedEntry.is_billable,
            });

            toast.success('Time entry created successfully');
            setInput('');
            setParsedEntry(null);
            setShowPreview(false);
            onEntryCreated?.();
        } catch (error) {
            toast.error('Failed to create time entry');
            console.error(error);
        } finally {
            setIsProcessing(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="relative">
                <Sparkles className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                <Input
                    placeholder="Type naturally: '2 hours working on API documentation'"
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && handleSubmit()}
                    className="pr-20 pl-10"
                />
                <Button size="sm" onClick={handleSubmit} disabled={!input || isProcessing} className="absolute top-1/2 right-1 -translate-y-1/2">
                    <Plus className="h-4 w-4" />
                </Button>
            </div>

            <div className="text-muted-foreground text-xs">
                <p>Examples:</p>
                <ul className="mt-1 space-y-1">
                    {examples.slice(0, 3).map((example, i) => (
                        <li key={i} className="italic">
                            • {example}
                        </li>
                    ))}
                </ul>
            </div>

            {showPreview && parsedEntry && (
                <Card className="space-y-3 p-4">
                    <h4 className="flex items-center gap-2 font-medium">
                        <Clock className="h-4 w-4" />
                        Preview Time Entry
                    </h4>
                    <div className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Description:</span>
                            <span>{parsedEntry.description}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Duration:</span>
                            <span>{parsedEntry.duration_hours} hours</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Date:</span>
                            <span>{parsedEntry.date}</span>
                        </div>
                        {parsedEntry.project && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Project:</span>
                                <span>{parsedEntry.project}</span>
                            </div>
                        )}
                        {parsedEntry.task && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Task:</span>
                                <span>{parsedEntry.task}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Billable:</span>
                            <span>{parsedEntry.is_billable ? 'Yes' : 'No'}</span>
                        </div>
                    </div>
                    <div className="flex gap-2 pt-2">
                        <Button size="sm" onClick={confirmEntry} disabled={isProcessing}>
                            Confirm
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => {
                                setShowPreview(false);
                                setParsedEntry(null);
                            }}
                        >
                            Cancel
                        </Button>
                    </div>
                </Card>
            )}
        </div>
    );
}
