import { useState, useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Calendar } from '@/components/ui/calendar';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { CalendarIcon, Clock } from 'lucide-react';

interface Project {
  id: number;
  name: string;
}

interface Task {
  id: number;
  title: string;
  project_id: number;
  project: Project;
}

interface TimeCategory {
  id: number;
  name: string;
  color: string;
  billable_default: boolean;
}

interface TimeEntryFormData {
  description: string;
  project_id: number | null;
  task_id: number | null;
  category_id: number | null;
  is_billable: boolean;
  date?: string;
  start_time?: string;
  end_time?: string;
}

interface TimeEntryFormProps {
  projects: Project[];
  tasks: Task[];
  categories: TimeCategory[];
  initialValues?: {
    description?: string;
    project_id?: number;
    task_id?: number;
    category_id?: number;
    is_billable?: boolean;
    date?: Date;
    start_time?: string;
    end_time?: string;
  };
  onSubmit: (data: TimeEntryFormData) => void;
  onCancel: () => void;
  mode?: 'create' | 'edit' | 'manual';
}

export function TimeEntryForm({
  projects,
  tasks,
  categories,
  initialValues = {},
  onSubmit,
  onCancel,
  mode = 'create'
}: TimeEntryFormProps) {
  const [description, setDescription] = useState(initialValues.description || '');
  const [projectId, setProjectId] = useState<number | null>(initialValues.project_id || null);
  const [taskId, setTaskId] = useState<number | null>(initialValues.task_id || null);
  const [categoryId, setCategoryId] = useState<number | null>(initialValues.category_id || null);
  const [isBillable, setIsBillable] = useState(initialValues.is_billable !== undefined ? initialValues.is_billable : true);
  const [date, setDate] = useState<Date | undefined>(initialValues.date || new Date());
  const [startTime, setStartTime] = useState(initialValues.start_time || format(new Date(), 'HH:mm'));
  const [endTime, setEndTime] = useState(initialValues.end_time || '');
  
  const [filteredTasks, setFilteredTasks] = useState<Task[]>([]);
  const descriptionRef = useRef<HTMLTextAreaElement>(null);
  
  // Focus on description field when form is mounted
  useEffect(() => {
    if (descriptionRef.current) {
      descriptionRef.current.focus();
    }
  }, []);
  
  // Update tasks when project changes
  useEffect(() => {
    if (projectId) {
      setFilteredTasks(tasks.filter(task => task.project_id === projectId));
    } else {
      setFilteredTasks(tasks);
    }
    
    // Reset task ID if project changes and current task doesn't belong to new project
    if (projectId && taskId) {
      const taskExists = tasks.some(task => task.id === taskId && task.project_id === projectId);
      if (!taskExists) {
        setTaskId(null);
      }
    }
  }, [projectId, tasks]);
  
  // Set billable default based on category selection
  useEffect(() => {
    if (categoryId) {
      const category = categories.find(cat => cat.id === categoryId);
      if (category) {
        setIsBillable(category.billable_default);
      }
    }
  }, [categoryId, categories]);
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const formData: TimeEntryFormData = {
      description,
      project_id: projectId,
      task_id: taskId,
      category_id: categoryId,
      is_billable: isBillable
    };
    
    if (mode === 'manual' && date && startTime) {
      // If manual entry mode, include date and time information
      formData.date = format(date, 'yyyy-MM-dd');
      formData.start_time = startTime;
      
      if (endTime) {
        formData.end_time = endTime;
      }
    }
    
    onSubmit(formData);
  };
  
  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="description">Descripción</Label>
        <Textarea
          id="description"
          ref={descriptionRef}
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          placeholder="¿En qué estás trabajando?"
          required
          className="resize-none"
          rows={2}
        />
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="project">Proyecto</Label>
          <Select
            value={projectId?.toString() || ''}
            onValueChange={(value) => setProjectId(value ? parseInt(value) : null)}
          >
            <SelectTrigger id="project">
              <SelectValue placeholder="Seleccionar proyecto" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">Sin proyecto</SelectItem>
              {projects.map((project) => (
                <SelectItem key={project.id} value={project.id.toString()}>
                  {project.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="task">Tarea</Label>
          <Select
            value={taskId?.toString() || ''}
            onValueChange={(value) => setTaskId(value ? parseInt(value) : null)}
            disabled={!projectId || filteredTasks.length === 0}
          >
            <SelectTrigger id="task">
              <SelectValue placeholder="Seleccionar tarea" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">Sin tarea</SelectItem>
              {filteredTasks.map((task) => (
                <SelectItem key={task.id} value={task.id.toString()}>
                  {task.title}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="category">Categoría</Label>
          <Select
            value={categoryId?.toString() || ''}
            onValueChange={(value) => setCategoryId(value ? parseInt(value) : null)}
          >
            <SelectTrigger id="category">
              <SelectValue placeholder="Seleccionar categoría" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">Sin categoría</SelectItem>
              {categories.map((category) => (
                <SelectItem 
                  key={category.id} 
                  value={category.id.toString()}
                >
                  <div className="flex items-center gap-2">
                    <div 
                      className="w-3 h-3 rounded-full" 
                      style={{ backgroundColor: category.color }}
                    ></div>
                    <span>{category.name}</span>
                  </div>
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        
        <div className="flex items-end">
          <div className="flex items-center space-x-2">
            <Checkbox 
              id="billable" 
              checked={isBillable}
              onCheckedChange={(checked) => setIsBillable(!!checked)}
            />
            <Label 
              htmlFor="billable" 
              className="text-sm font-normal"
            >
              Facturable
            </Label>
          </div>
        </div>
      </div>
      
      {mode === 'manual' && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label>Fecha</Label>
            <Popover>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  className="w-full justify-start text-left font-normal"
                >
                  <CalendarIcon className="mr-2 h-4 w-4" />
                  {date ? format(date, 'PP', { locale: es }) : <span>Seleccionar fecha</span>}
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                  mode="single"
                  selected={date}
                  onSelect={setDate}
                  initialFocus
                  locale={es}
                />
              </PopoverContent>
            </Popover>
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="start-time">Hora inicio</Label>
            <div className="flex items-center">
              <Clock className="mr-2 h-4 w-4 text-muted-foreground" />
              <Input
                id="start-time"
                type="time"
                value={startTime}
                onChange={(e) => setStartTime(e.target.value)}
                required
              />
            </div>
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="end-time">Hora fin</Label>
            <div className="flex items-center">
              <Clock className="mr-2 h-4 w-4 text-muted-foreground" />
              <Input
                id="end-time"
                type="time"
                value={endTime}
                onChange={(e) => setEndTime(e.target.value)}
                min={startTime}
              />
            </div>
          </div>
        </div>
      )}
      
      <div className="flex justify-end gap-2 pt-4">
        <Button type="button" variant="outline" onClick={onCancel}>
          Cancelar
        </Button>
        
        <Button type="submit" disabled={!description.trim()}>
          {mode === 'create' ? 'Iniciar' : mode === 'edit' ? 'Guardar' : 'Registrar'}
        </Button>
      </div>
    </form>
  );
}