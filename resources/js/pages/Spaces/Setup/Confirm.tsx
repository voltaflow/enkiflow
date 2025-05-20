import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Check, Users } from 'lucide-react';
import { useState } from 'react';

interface InviteItem {
  email: string;
  role: string;
}

interface ConfirmProps {
  name: string;
  subdomain: string;
  plan: string;
  invites: InviteItem[];
}

export default function Confirm({ name, subdomain, plan, invites }: ConfirmProps) {
  const [loading, setLoading] = useState(false);
  
  const { post } = useForm();

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    
    setLoading(true);
    post(route('spaces.setup.store'));
  };

  const formatRole = (role: string) => {
    switch (role) {
      case 'admin':
        return 'Administrador';
      case 'manager':
        return 'Gerente';
      case 'member':
        return 'Miembro';
      case 'guest':
        return 'Invitado';
      default:
        return role;
    }
  };

  return (
    <AppLayout>
      <Head title="Confirmar Creación de Espacio" />

      <div className="py-12">
        <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
          <div className="text-center mb-8">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              Confirma los Detalles
            </h1>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Revisa la información antes de crear tu nuevo espacio
            </p>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>Resumen del Espacio</CardTitle>
              <CardDescription>
                A continuación se muestra un resumen de la configuración de tu nuevo espacio.
              </CardDescription>
            </CardHeader>
            <form onSubmit={submit}>
              <CardContent className="space-y-6">
                <div className="grid md:grid-cols-2 gap-4">
                  <div className="p-4 bg-muted rounded-md">
                    <h3 className="font-semibold mb-2">Información del Espacio</h3>
                    <div className="space-y-2">
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Nombre:</span>
                        <span className="font-medium">{name}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Subdominio:</span>
                        <span className="font-medium">{subdomain}.example.com</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Plan:</span>
                        <span className="font-medium">{plan.charAt(0).toUpperCase() + plan.slice(1)}</span>
                      </div>
                    </div>
                  </div>

                  <div className="p-4 bg-muted rounded-md">
                    <h3 className="font-semibold mb-2">Miembros Invitados</h3>
                    {invites.length > 0 ? (
                      <div className="space-y-2">
                        {invites.map((invite, index) => (
                          <div key={index} className="flex justify-between">
                            <span className="truncate mr-2">{invite.email}</span>
                            <span className="text-sm text-muted-foreground">{formatRole(invite.role)}</span>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="flex flex-col items-center justify-center py-4 text-muted-foreground">
                        <Users className="h-8 w-8 mb-2" />
                        <p>No se han invitado miembros</p>
                      </div>
                    )}
                  </div>
                </div>

                <div className="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-900 rounded-md p-4">
                  <div className="flex gap-2">
                    <Check className="h-5 w-5 text-green-600 dark:text-green-400 mt-0.5" />
                    <div>
                      <h3 className="font-medium text-green-800 dark:text-green-300">Todo listo para comenzar</h3>
                      <p className="text-sm text-green-700 dark:text-green-400">
                        Tu nuevo espacio estará disponible inmediatamente después de la creación.
                        No se requiere tarjeta de crédito para el período de prueba.
                      </p>
                    </div>
                  </div>
                </div>
              </CardContent>
              <CardFooter className="flex justify-between">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => window.history.back()}
                >
                  Volver
                </Button>
                <Button type="submit" disabled={loading}>
                  {loading ? 'Creando espacio...' : 'Crear Espacio'}
                </Button>
              </CardFooter>
            </form>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}