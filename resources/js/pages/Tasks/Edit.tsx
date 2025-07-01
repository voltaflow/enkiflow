import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { PageProps } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Project {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
}

interface Task {
    id: number;
    title: string;
    description: string | null;
    project_id: number;
    user_id: number;
    status: string;
    priority: number;
    due_date: string | null;
    tags: Tag[];
}

interface EditProps {
    task: Task;
    projects: Project[];
    users: User[];
    availableTags: Tag[];
}

export default function Edit({ task, projects, users, availableTags }: PageProps<EditProps>) {
    const { data, setData, put, processing, errors } = useForm({
        title: task.title,
        description: task.description || '',
        project_id: task.project_id.toString(),
        user_id: task.user_id.toString(),
        status: task.status,
        priority: task.priority.toString(),
        due_date: task.due_date || '',
        tags: task.tags.map((tag) => tag.id),
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        put(route('tasks.update', task.id));
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
    };

    return (
        <AppLayout>
            <Head title="Editar Tarea" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Editar Tarea</CardTitle>
                            <CardDescription>Actualiza la información de la tarea</CardDescription>
                        </CardHeader>
                        <form onSubmit={handleSubmit}>
                            <CardContent className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="title">Título *</Label>
                                    <Input id="title" type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} required />
                                    <InputError message={errors.title} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Descripción</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={4}
                                    />
                                    <InputError message={errors.description} />
                                </div>

                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="project_id">Proyecto *</Label>
                                        <Select value={data.project_id} onValueChange={(value) => setData('project_id', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar proyecto" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {projects.map((project) => (
                                                    <SelectItem key={project.id} value={project.id.toString()}>
                                                        {project.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.project_id} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="user_id">Asignar a *</Label>
                                        <Select value={data.user_id} onValueChange={(value) => setData('user_id', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar usuario" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {users.map((user) => (
                                                    <SelectItem key={user.id} value={user.id.toString()}>
                                                        {user.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.user_id} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="status">Estado *</Label>
                                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar estado" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="pending">Pendiente</SelectItem>
                                                <SelectItem value="in_progress">En progreso</SelectItem>
                                                <SelectItem value="completed">Completada</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.status} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="priority">Prioridad *</Label>
                                        <Select value={data.priority} onValueChange={(value) => setData('priority', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar prioridad" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="0">Baja</SelectItem>
                                                <SelectItem value="2">Media</SelectItem>
                                                <SelectItem value="4">Alta</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.priority} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="due_date">Fecha de vencimiento</Label>
                                        <Input
                                            id="due_date"
                                            type="date"
                                            value={formatDate(data.due_date)}
                                            onChange={(e) => setData('due_date', e.target.value)}
                                        />
                                        <InputError message={errors.due_date} />
                                    </div>
                                </div>

                                {availableTags.length > 0 && (
                                    <div className="space-y-2">
                                        <Label>Etiquetas</Label>
                                        <div className="flex flex-wrap gap-2">
                                            {availableTags.map((tag) => (
                                                <div key={tag.id} className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        id={`tag-${tag.id}`}
                                                        checked={data.tags.includes(tag.id)}
                                                        onChange={(e) => {
                                                            const isChecked = e.target.checked;
                                                            setData(
                                                                'tags',
                                                                isChecked ? [...data.tags, tag.id] : data.tags.filter((id) => id !== tag.id),
                                                            );
                                                        }}
                                                        className="mr-1"
                                                    />
                                                    <label htmlFor={`tag-${tag.id}`} className="text-sm">
                                                        {tag.name}
                                                    </label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                            <CardFooter className="flex justify-end space-x-2">
                                <Button type="button" variant="outline" onClick={() => window.history.back()} disabled={processing}>
                                    Cancelar
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Actualizar Tarea
                                </Button>
                            </CardFooter>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
