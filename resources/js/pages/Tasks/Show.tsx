import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Project {
    id: number;
    name: string;
}

interface Comment {
    id: number;
    content: string;
    user: User;
    created_at: string;
}

interface Tag {
    id: number;
    name: string;
}

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: 'pending' | 'in_progress' | 'completed';
    priority: number;
    due_date: string | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
    project: Project;
    user: User;
    comments: Comment[];
    tags: Tag[];
}

interface ShowProps {
    task: Task;
}

export default function Show({ task }: PageProps<ShowProps>) {
    const { data, setData, post, processing, errors, reset } = useForm({
        content: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post(route('tasks.comments.store', task.id), {
            onSuccess: () => reset(),
        });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending':
                return <Badge className="bg-slate-500">Pendiente</Badge>;
            case 'in_progress':
                return <Badge className="bg-blue-500">En progreso</Badge>;
            case 'completed':
                return <Badge className="bg-green-600">Completada</Badge>;
            default:
                return null;
        }
    };

    const getPriorityBadge = (priority: number) => {
        if (priority >= 4) return <Badge className="bg-red-600">Alta</Badge>;
        if (priority >= 2) return <Badge className="bg-amber-500">Media</Badge>;
        return <Badge className="bg-blue-500">Baja</Badge>;
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('es', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout>
            <Head title={`Tarea: ${task.title}`} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    {/* Task header with actions */}
                    <div className="mb-6 flex items-start justify-between">
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">{task.title}</h1>
                            <div className="text-sm text-gray-500 dark:text-gray-400">Creada el {formatDate(task.created_at)}</div>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link href={route('tasks.index')}>Volver</Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route('tasks.edit', task.id)}>Editar</Link>
                            </Button>
                        </div>
                    </div>

                    {/* Task details card */}
                    <Card className="mb-6">
                        <CardHeader className="pb-2">
                            <div className="flex items-start justify-between">
                                <CardTitle>Detalles de la tarea</CardTitle>
                                <div className="flex gap-2">
                                    {getStatusBadge(task.status)}
                                    {getPriorityBadge(task.priority)}
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Descripción</h3>
                                <p className="mt-1 whitespace-pre-line text-gray-900 dark:text-white">{task.description || 'Sin descripción'}</p>
                            </div>

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Proyecto</h3>
                                    <p className="mt-1 text-gray-900 dark:text-white">
                                        <Link
                                            href={route('tenant.projects.show', task.project.id)}
                                            className="text-blue-600 hover:underline dark:text-blue-400"
                                        >
                                            {task.project.name}
                                        </Link>
                                    </p>
                                </div>
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Asignada a</h3>
                                    <p className="mt-1 text-gray-900 dark:text-white">
                                        {task.user.name} ({task.user.email})
                                    </p>
                                </div>
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de vencimiento</h3>
                                    <p className="mt-1 text-gray-900 dark:text-white">
                                        {task.due_date ? formatDate(task.due_date) : 'Sin fecha de vencimiento'}
                                    </p>
                                </div>
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Completada</h3>
                                    <p className="mt-1 text-gray-900 dark:text-white">
                                        {task.completed_at ? formatDate(task.completed_at) : 'No completada'}
                                    </p>
                                </div>
                            </div>

                            {task.tags.length > 0 && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Etiquetas</h3>
                                    <div className="mt-1 flex flex-wrap gap-1">
                                        {task.tags.map((tag) => (
                                            <Badge key={tag.id} variant="outline" className="bg-gray-100 dark:bg-gray-800">
                                                {tag.name}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                        <CardFooter className="pt-0">
                            {task.status !== 'completed' ? (
                                <div className="flex gap-2">
                                    {task.status === 'pending' && (
                                        <Button asChild variant="secondary">
                                            <Link href={route('tasks.in-progress', task.id)} method="post" as="button">
                                                Iniciar trabajo
                                            </Link>
                                        </Button>
                                    )}
                                    <Button asChild variant="default">
                                        <Link href={route('tasks.complete', task.id)} method="post" as="button">
                                            Marcar como completada
                                        </Link>
                                    </Button>
                                </div>
                            ) : (
                                <Badge className="bg-green-600">Completada el {formatDate(task.completed_at)}</Badge>
                            )}
                        </CardFooter>
                    </Card>

                    {/* Comments section */}
                    <div className="mt-8">
                        <h2 className="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Comentarios</h2>

                        {/* Comment form */}
                        <Card className="mb-6">
                            <form onSubmit={handleSubmit}>
                                <CardContent className="pt-6">
                                    <Textarea
                                        placeholder="Añade un comentario..."
                                        value={data.content}
                                        onChange={(e) => setData('content', e.target.value)}
                                        rows={3}
                                        required
                                    />
                                    <InputError message={errors.content} />
                                </CardContent>
                                <CardFooter className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        Añadir comentario
                                    </Button>
                                </CardFooter>
                            </form>
                        </Card>

                        {/* Comments list */}
                        {task.comments.length > 0 ? (
                            <div className="space-y-4">
                                {task.comments.map((comment) => (
                                    <Card key={comment.id}>
                                        <CardHeader className="pb-2">
                                            <div className="flex items-center justify-between">
                                                <div className="font-medium">{comment.user.name}</div>
                                                <div className="text-xs text-gray-500 dark:text-gray-400">{formatDate(comment.created_at)}</div>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <p className="whitespace-pre-line">{comment.content}</p>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center text-gray-500 dark:text-gray-400">
                                No hay comentarios todavía. Sé el primero en comentar.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
