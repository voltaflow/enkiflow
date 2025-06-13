import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Plus, FolderOpen, Calendar, CheckCircle } from 'lucide-react';
import { Link } from '@inertiajs/react';

interface Project {
    id: number;
    name: string;
    description?: string;
    status: 'active' | 'completed' | 'archived';
    created_at: string;
    updated_at: string;
    tasks_count?: number;
    completed_tasks_count?: number;
}

interface Props {
    projects: {
        data: Project[];
    };
}

export default function Index({ projects }: Props) {
    const projectsList = projects?.data || [];

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'active':
                return 'text-green-600 bg-green-50';
            case 'completed':
                return 'text-blue-600 bg-blue-50';
            case 'archived':
                return 'text-gray-600 bg-gray-50';
            default:
                return 'text-gray-600 bg-gray-50';
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'active':
                return <FolderOpen className="h-4 w-4" />;
            case 'completed':
                return <CheckCircle className="h-4 w-4" />;
            default:
                return <FolderOpen className="h-4 w-4" />;
        }
    };

    return (
        <AppLayout>
            <Head title="Projects" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Projects</h1>
                        <p className="text-muted-foreground">
                            Manage your projects and track progress
                        </p>
                    </div>
                    <Link href="/projects/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            New Project
                        </Button>
                    </Link>
                </div>

                {projectsList.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <FolderOpen className="h-12 w-12 text-muted-foreground mb-4" />
                            <h3 className="text-lg font-semibold mb-2">No projects yet</h3>
                            <p className="text-muted-foreground text-center mb-4">
                                Get started by creating your first project
                            </p>
                            <Link href="/projects/create">
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Create Project
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {projectsList.map((project) => (
                            <Link 
                                key={project.id} 
                                href={`/projects/${project.id}`}
                                className="block"
                            >
                                <Card className="hover:shadow-md transition-shadow cursor-pointer">
                                    <CardHeader>
                                        <div className="flex items-start justify-between">
                                            <div className="space-y-1">
                                                <CardTitle className="text-lg">
                                                    {project.name}
                                                </CardTitle>
                                                {project.description && (
                                                    <CardDescription className="line-clamp-2">
                                                        {project.description}
                                                    </CardDescription>
                                                )}
                                            </div>
                                            <div className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(project.status)}`}>
                                                {getStatusIcon(project.status)}
                                                <span className="capitalize">{project.status}</span>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex items-center justify-between text-sm text-muted-foreground">
                                            <div className="flex items-center gap-1">
                                                <Calendar className="h-3 w-3" />
                                                <span>
                                                    {new Date(project.created_at).toLocaleDateString()}
                                                </span>
                                            </div>
                                            {project.tasks_count !== undefined && (
                                                <div className="flex items-center gap-1">
                                                    <CheckCircle className="h-3 w-3" />
                                                    <span>
                                                        {project.completed_tasks_count || 0}/{project.tasks_count} tasks
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}