import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

interface DetailsProps {
  plan: string;
}

export default function Details({ plan }: DetailsProps) {
  const [loading, setLoading] = useState(false);
  const [subdomainChecking, setSubdomainChecking] = useState(false);
  const [subdomainAvailable, setSubdomainAvailable] = useState<boolean | null>(null);
  
  const { data, setData, post, errors } = useForm({
    name: '',
    subdomain: '',
    plan: plan,
  });

  // Check if subdomain is already taken
  const checkSubdomain = (subdomain: string) => {
    if (!subdomain || subdomain.length < 3) {
      setSubdomainAvailable(null);
      return;
    }
    
    // Simple validation first
    if (!/^[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?$/.test(subdomain)) {
      setSubdomainAvailable(false);
      return;
    }
    
    setSubdomainChecking(true);
    
    // In a real app, this would make an API call to check subdomain availability
    setTimeout(() => {
      // For this example, we'll simulate checking availability
      // Random result for demonstration purposes
      setSubdomainAvailable(Math.random() > 0.3);
      setSubdomainChecking(false);
    }, 500);
  };

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!subdomainAvailable) {
      return;
    }
    
    setLoading(true);
    post(route('spaces.setup.invite-members'), {
      onSuccess: () => {
        setLoading(false);
      },
      onError: () => {
        setLoading(false);
      },
    });
  };

  return (
    <AppLayout>
      <Head title="Detalles del Espacio" />

      <div className="py-12">
        <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
          <div className="text-center mb-8">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              Configura tu espacio
            </h1>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Proporciona los detalles básicos para tu nuevo espacio de trabajo
            </p>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>Detalles del Espacio</CardTitle>
              <CardDescription>
                Elige un nombre y un subdominio para tu nuevo espacio. El subdominio se usará para acceder a tu espacio.
              </CardDescription>
            </CardHeader>
            <form onSubmit={submit}>
              <CardContent className="space-y-6">
                <div className="space-y-2">
                  <Label htmlFor="name">Nombre del Espacio</Label>
                  <Input
                    id="name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Mi Espacio Creativo"
                    required
                    minLength={3}
                    maxLength={50}
                  />
                  {errors.name && <div className="text-red-500 text-sm">{errors.name}</div>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="subdomain">Subdominio</Label>
                  <div className="flex items-center">
                    <Input
                      id="subdomain"
                      type="text"
                      value={data.subdomain}
                      onChange={(e) => {
                        const value = e.target.value.toLowerCase();
                        setData('subdomain', value);
                        checkSubdomain(value);
                      }}
                      placeholder="mi-espacio"
                      required
                      minLength={3}
                      maxLength={63}
                      className="flex-1"
                      pattern="^[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?$"
                      title="Solo letras minúsculas, números y guiones. Debe comenzar y terminar con letra o número."
                    />
                    <span className="ml-2 text-gray-500 dark:text-gray-400 hidden sm:inline">
                      .example.com
                    </span>
                  </div>
                  <div className="text-sm">
                    {subdomainChecking && (
                      <span className="text-amber-600">Verificando disponibilidad...</span>
                    )}
                    {!subdomainChecking && subdomainAvailable === true && (
                      <span className="text-green-600">✓ Subdominio disponible</span>
                    )}
                    {!subdomainChecking && subdomainAvailable === false && (
                      <span className="text-red-600">✗ Subdominio no disponible o inválido</span>
                    )}
                  </div>
                  {errors.subdomain && <div className="text-red-500 text-sm">{errors.subdomain}</div>}
                </div>

                <div className="bg-muted p-4 rounded-md">
                  <h3 className="font-medium mb-2">Plan Seleccionado: {plan.charAt(0).toUpperCase() + plan.slice(1)}</h3>
                  <p className="text-sm">
                    Puedes cambiar tu plan en cualquier momento después de la creación del espacio.
                  </p>
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
                <Button
                  type="submit"
                  disabled={loading || !subdomainAvailable || !data.name || !data.subdomain}
                >
                  {loading ? 'Procesando...' : 'Continuar'}
                </Button>
              </CardFooter>
            </form>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}