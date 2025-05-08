import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Heading } from '@/components/heading';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Head, Link } from '@inertiajs/react';
import { Calendar, ChevronLeft, Clock, BarChart, PieChart, Users, Layers } from 'lucide-react';
import { useState, useEffect } from 'react';
import axios from 'axios';
import { format, parseISO, startOfMonth, endOfMonth, subMonths } from 'date-fns';
import { es } from 'date-fns/locale';

interface TimeReportData {
  totalHours: number;
  billableHours: number;
  billablePercentage: number;
  byProject: {
    id: number;
    name: string;
    hours: number;
    percentage: number;
  }[];
  byUser: {
    id: number;
    name: string;
    hours: number;
    percentage: number;
  }[];
  byCategory: {
    id: number;
    name: string;
    color: string;
    hours: number;
    percentage: number;
  }[];
  byDay: {
    date: string;
    hours: number;
  }[];
  detailed: {
    id: number;
    description: string;
    project: string;
    task: string;
    category: string;
    categoryColor: string;
    user: string;
    date: string;
    duration: number;
    formattedDuration: string;
    billable: boolean;
  }[];
}

interface Props {
  initialReportData?: TimeReportData;
  periodType?: string;
  periodValue?: string;
}

export default function TimeTrackingReports({ 
  initialReportData,
  periodType = 'month',
  periodValue = format(new Date(), 'yyyy-MM')
}: Props) {
  const [reportData, setReportData] = useState<TimeReportData | null>(initialReportData || null);
  const [loading, setLoading] = useState(!initialReportData);
  const [currentTab, setCurrentTab] = useState('summary');
  const [period, setPeriod] = useState({
    type: periodType,
    value: periodValue
  });
  
  const fetchReportData = async () => {
    setLoading(true);
    
    try {
      const response = await axios.get(route('tenant.time.report'), {
        params: {
          period_type: period.type,
          period_value: period.value
        }
      });
      
      setReportData(response.data);
    } catch (error) {
      console.error('Error fetching report data:', error);
    } finally {
      setLoading(false);
    }
  };
  
  useEffect(() => {
    fetchReportData();
  }, [period]);
  
  const handlePreviousPeriod = () => {
    if (period.type === 'month') {
      const currentDate = parseISO(`${period.value}-01`);
      const previousMonth = subMonths(currentDate, 1);
      setPeriod({
        ...period,
        value: format(previousMonth, 'yyyy-MM')
      });
    }
  };
  
  const handleNextPeriod = () => {
    if (period.type === 'month') {
      const currentDate = parseISO(`${period.value}-01`);
      const nextMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
      
      // Don't allow future periods
      if (nextMonth <= new Date()) {
        setPeriod({
          ...period,
          value: format(nextMonth, 'yyyy-MM')
        });
      }
    }
  };
  
  const getPeriodLabel = () => {
    if (period.type === 'month') {
      const date = parseISO(`${period.value}-01`);
      return format(date, 'MMMM yyyy', { locale: es });
    }
    return '';
  };
  
  return (
    <AppShell>
      <Head title="Reportes de Tiempo" />
      
      <div className="container py-6 space-y-6">
        <div className="flex justify-between items-center">
          <Heading>Reportes de Tiempo</Heading>
          <div className="flex gap-2">
            <Link href={route('tenant.time.index')}>
              <Button variant="outline" className="flex items-center gap-2">
                <Clock className="h-4 w-4" />
                <span>Volver al Registro</span>
              </Button>
            </Link>
          </div>
        </div>
        
        <Card>
          <CardHeader className="pb-4">
            <div className="flex justify-between items-center">
              <div>
                <CardTitle>Período</CardTitle>
                <CardDescription>Seleccione el período para el reporte</CardDescription>
              </div>
              <div className="flex items-center gap-2">
                <Select
                  value={period.type}
                  onValueChange={(value) => setPeriod({ ...period, type: value })}
                >
                  <SelectTrigger className="w-[120px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="month">Mensual</SelectItem>
                    <SelectItem value="week">Semanal</SelectItem>
                    <SelectItem value="custom">Personalizado</SelectItem>
                  </SelectContent>
                </Select>
                
                <div className="flex items-center gap-2">
                  <Button 
                    variant="outline" 
                    size="icon" 
                    onClick={handlePreviousPeriod}
                    title="Período anterior"
                  >
                    <ChevronLeft className="h-4 w-4" />
                  </Button>
                  
                  <div className="min-w-[140px] text-center font-medium">
                    {getPeriodLabel()}
                  </div>
                  
                  <Button 
                    variant="outline" 
                    size="icon" 
                    onClick={handleNextPeriod}
                    disabled={period.type === 'month' && period.value === format(new Date(), 'yyyy-MM')}
                    title="Período siguiente"
                  >
                    <ChevronLeft className="h-4 w-4 rotate-180" />
                  </Button>
                </div>
              </div>
            </div>
          </CardHeader>
        </Card>
        
        {loading ? (
          <Card>
            <CardContent className="flex justify-center items-center py-12">
              <div className="text-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                <p className="mt-2 text-muted-foreground">Cargando datos del reporte...</p>
              </div>
            </CardContent>
          </Card>
        ) : reportData ? (
          <>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-lg">Horas Totales</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-3xl font-bold">{reportData.totalHours.toFixed(1)}</div>
                  <p className="text-muted-foreground text-sm">horas registradas</p>
                </CardContent>
              </Card>
              
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-lg">Horas Facturables</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-3xl font-bold">{reportData.billableHours.toFixed(1)}</div>
                  <p className="text-muted-foreground text-sm">
                    {reportData.billablePercentage.toFixed(0)}% del total
                  </p>
                </CardContent>
              </Card>
              
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-lg">Proyectos</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-3xl font-bold">{reportData.byProject.length}</div>
                  <p className="text-muted-foreground text-sm">con tiempo registrado</p>
                </CardContent>
              </Card>
            </div>
            
            <Tabs value={currentTab} onValueChange={setCurrentTab}>
              <TabsList className="grid grid-cols-4 w-full max-w-md">
                <TabsTrigger value="summary" className="flex items-center gap-2">
                  <PieChart className="h-4 w-4" />
                  <span>Resumen</span>
                </TabsTrigger>
                <TabsTrigger value="by-project" className="flex items-center gap-2">
                  <Layers className="h-4 w-4" />
                  <span>Proyectos</span>
                </TabsTrigger>
                <TabsTrigger value="by-user" className="flex items-center gap-2">
                  <Users className="h-4 w-4" />
                  <span>Usuarios</span>
                </TabsTrigger>
                <TabsTrigger value="detailed" className="flex items-center gap-2">
                  <BarChart className="h-4 w-4" />
                  <span>Detallado</span>
                </TabsTrigger>
              </TabsList>
              
              <TabsContent value="summary" className="mt-6 space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <Card>
                    <CardHeader>
                      <CardTitle>Distribución por Categoría</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div className="space-y-4">
                        {reportData.byCategory.map((category) => (
                          <div key={category.id} className="flex items-center">
                            <div className="w-3 h-3 rounded-full mr-2" style={{ backgroundColor: category.color }}></div>
                            <div className="flex-1">{category.name}</div>
                            <div className="font-medium">{category.hours.toFixed(1)}h</div>
                            <div className="ml-2 text-muted-foreground">{category.percentage.toFixed(0)}%</div>
                          </div>
                        ))}
                      </div>
                    </CardContent>
                  </Card>
                  
                  <Card>
                    <CardHeader>
                      <CardTitle>Distribución por Día</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div className="h-56">
                        {/* Here we would ideally render a bar chart
                             For this implementation, we'll use a simple visualization */}
                        <div className="space-y-2">
                          {reportData.byDay.map((day) => {
                            const maxHours = Math.max(...reportData.byDay.map(d => d.hours));
                            const percentage = (day.hours / maxHours) * 100;
                            
                            return (
                              <div key={day.date} className="flex items-center">
                                <div className="w-20 text-sm">{format(parseISO(day.date), 'E dd', { locale: es })}</div>
                                <div className="flex-1">
                                  <div 
                                    className="bg-primary h-5 rounded"
                                    style={{ width: `${percentage}%` }}
                                  ></div>
                                </div>
                                <div className="w-16 text-right">{day.hours.toFixed(1)}h</div>
                              </div>
                            );
                          })}
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>
              </TabsContent>
              
              <TabsContent value="by-project" className="mt-6">
                <Card>
                  <CardContent className="pt-6">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Proyecto</TableHead>
                          <TableHead className="text-right">Horas</TableHead>
                          <TableHead className="text-right">Porcentaje</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {reportData.byProject.map((project) => (
                          <TableRow key={project.id}>
                            <TableCell>{project.name}</TableCell>
                            <TableCell className="text-right">{project.hours.toFixed(1)}</TableCell>
                            <TableCell className="text-right">{project.percentage.toFixed(0)}%</TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </CardContent>
                </Card>
              </TabsContent>
              
              <TabsContent value="by-user" className="mt-6">
                <Card>
                  <CardContent className="pt-6">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Usuario</TableHead>
                          <TableHead className="text-right">Horas</TableHead>
                          <TableHead className="text-right">Porcentaje</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {reportData.byUser.map((user) => (
                          <TableRow key={user.id}>
                            <TableCell>{user.name}</TableCell>
                            <TableCell className="text-right">{user.hours.toFixed(1)}</TableCell>
                            <TableCell className="text-right">{user.percentage.toFixed(0)}%</TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </CardContent>
                </Card>
              </TabsContent>
              
              <TabsContent value="detailed" className="mt-6">
                <Card>
                  <CardContent className="pt-6">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Fecha</TableHead>
                          <TableHead>Descripción</TableHead>
                          <TableHead>Proyecto / Tarea</TableHead>
                          <TableHead>Categoría</TableHead>
                          <TableHead>Usuario</TableHead>
                          <TableHead className="text-right">Duración</TableHead>
                          <TableHead>Facturable</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {reportData.detailed.map((entry) => (
                          <TableRow key={entry.id}>
                            <TableCell>{format(parseISO(entry.date), 'dd/MM/yyyy')}</TableCell>
                            <TableCell>{entry.description}</TableCell>
                            <TableCell>
                              <div>{entry.project}</div>
                              {entry.task && (
                                <div className="text-sm text-muted-foreground">{entry.task}</div>
                              )}
                            </TableCell>
                            <TableCell>
                              {entry.category && (
                                <Badge 
                                  style={{ 
                                    backgroundColor: entry.categoryColor,
                                    color: '#fff'
                                  }}
                                >
                                  {entry.category}
                                </Badge>
                              )}
                            </TableCell>
                            <TableCell>{entry.user}</TableCell>
                            <TableCell className="text-right">{entry.formattedDuration}</TableCell>
                            <TableCell>
                              {entry.billable ? 
                                <Badge variant="default">Sí</Badge> : 
                                <Badge variant="outline">No</Badge>
                              }
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>
          </>
        ) : (
          <Card>
            <CardContent className="flex justify-center items-center py-12">
              <div className="text-center">
                <p className="text-muted-foreground">No hay datos disponibles para el período seleccionado</p>
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </AppShell>
  );
}