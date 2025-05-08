import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

interface Task {
  id: number;
  title: string;
  description: string | null;
  status: 'pending' | 'in_progress' | 'completed';
  priority: number;
  due_date: string | null;
  project: {
    id: number;
    name: string;
  };
  user: {
    id: number;
    name: string;
  };
}

interface TasksProps {
  tasks: {
    data: Task[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export default function Index({ auth, tasks }: PageProps<TasksProps>) {
  const [searchTerm, setSearchTerm] = useState('');

  const filteredTasks = tasks.data.filter(task => 
    task.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (task.description && task.description.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  const getPriorityBadge = (priority: number) => {
    if (priority >= 4) return <Badge className="bg-red-600">Alta</Badge>;
    if (priority >= 2) return <Badge className="bg-amber-500">Media</Badge>;
    return <Badge className="bg-blue-500">Baja</Badge>;
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

  const handleStatusChange = (taskId: number, status: string) => {
    let routeName;
    switch (status) {
      case 'in_progress':
        routeName = 'tasks.in-progress';
        break;
      case 'completed':
        routeName = 'tasks.complete';
        break;
      default:
        return;
    }

    router.post(route(routeName, taskId));
  };

  const handleDelete = (taskId: number) => {
    if (confirm('¿Estás seguro de que quieres eliminar esta tarea?')) {
      router.delete(route('tasks.destroy', taskId));
    }
  };

  return (
    <AppLayout>
      <Head title="Tareas" />
      
      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="flex justify-between items-center mb-6">
            <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Tareas</h1>
            <Button asChild>
              <Link href={route('tasks.create')}>
                Crear tarea
              </Link>
            </Button>
          </div>

          <div className="mb-6">
            <input
              type="text"
              placeholder="Buscar tareas..."
              className="w-full p-2 border border-gray-300 rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-700"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>

          <div className="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
            {filteredTasks.length > 0 ? (
              filteredTasks.map((task) => (
                <Card key={task.id} className="shadow-sm">
                  <CardHeader className="pb-2">
                    <div className="flex justify-between items-start">
                      <CardTitle className="text-lg">
                        <Link href={route('tasks.show', task.id)} className="hover:underline">
                          {task.title}
                        </Link>
                      </CardTitle>
                      <div className="flex gap-1">
                        {getPriorityBadge(task.priority)}
                        {getStatusBadge(task.status)}
                      </div>
                    </div>
                    <CardDescription>
                      Proyecto: {task.project.name}
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="pb-2">
                    <p className="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                      {task.description || 'Sin descripción'}
                    </p>
                    {task.due_date && (
                      <p className="text-xs mt-2 text-gray-500 dark:text-gray-400">
                        Vencimiento: {new Date(task.due_date).toLocaleDateString()}
                      </p>
                    )}
                  </CardContent>
                  <Separator />
                  <CardFooter className="pt-4 pb-2 flex justify-between items-center">
                    <span className="text-xs text-gray-500 dark:text-gray-400">
                      Asignado a: {task.user.name}
                    </span>
                    <div className="flex gap-2">
                      {task.status !== 'completed' && (
                        <Button 
                          size="sm" 
                          variant="outline"
                          onClick={() => handleStatusChange(task.id, task.status === 'pending' ? 'in_progress' : 'completed')}
                        >
                          {task.status === 'pending' ? 'Iniciar' : 'Completar'}
                        </Button>
                      )}
                      <Button size="sm" variant="outline" asChild>
                        <Link href={route('tasks.edit', task.id)}>
                          Editar
                        </Link>
                      </Button>
                      <Button 
                        size="sm" 
                        variant="destructive" 
                        onClick={() => handleDelete(task.id)}
                      >
                        Eliminar
                      </Button>
                    </div>
                  </CardFooter>
                </Card>
              ))
            ) : (
              <div className="col-span-3 text-center py-12">
                <p className="text-gray-500 dark:text-gray-400">
                  No se encontraron tareas. {' '}
                  <Link href={route('tasks.create')} className="text-blue-500 hover:underline">
                    Crea una nueva tarea
                  </Link>
                </p>
              </div>
            )}
          </div>

          {tasks.last_page > 1 && (
            <div className="mt-6 flex justify-center">
              <nav className="flex gap-2">
                {Array.from({ length: tasks.last_page }, (_, i) => i + 1).map((page) => (
                  <Link
                    key={page}
                    href={route('tasks.index', { page })}
                    className={`px-3 py-1 rounded ${
                      page === tasks.current_page
                        ? 'bg-blue-500 text-white'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'
                    }`}
                  >
                    {page}
                  </Link>
                ))}
              </nav>
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}