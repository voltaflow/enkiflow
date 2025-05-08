import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Check } from 'lucide-react';
import { Head, Link } from '@inertiajs/react';

interface Plan {
  id: string;
  name: string;
  description: string;
  price: string;
  features: string[];
  most_popular: boolean;
}

interface IndexProps {
  plans: Plan[];
}

export default function Index({ plans }: IndexProps) {
  return (
    <AppLayout>
      <Head title="Crear Nuevo Espacio" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="text-center mb-8">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              Crea tu nuevo espacio de trabajo
            </h1>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Elige el plan que mejor se adapte a las necesidades de tu equipo
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-6">
            {plans.map((plan) => (
              <Card
                key={plan.id}
                className={`relative ${
                  plan.most_popular
                    ? 'border-primary ring-2 ring-primary/10 dark:ring-primary/30'
                    : ''
                }`}
              >
                {plan.most_popular && (
                  <div className="absolute -top-4 left-0 right-0 flex justify-center">
                    <span className="bg-primary text-primary-foreground px-3 py-1 text-sm rounded-full font-medium">
                      Más Popular
                    </span>
                  </div>
                )}
                <CardHeader className="text-center">
                  <CardTitle className="text-2xl">{plan.name}</CardTitle>
                  <CardDescription>{plan.description}</CardDescription>
                </CardHeader>
                <CardContent className="text-center">
                  <div className="mb-4">
                    <span className="text-4xl font-bold">${plan.price}</span>
                    <span className="text-muted-foreground"> / mes</span>
                  </div>
                  <ul className="space-y-2 text-left">
                    {plan.features.map((feature, index) => (
                      <li key={index} className="flex items-start">
                        <Check className="mr-2 h-5 w-5 text-green-500 flex-shrink-0" />
                        <span>{feature}</span>
                      </li>
                    ))}
                  </ul>
                </CardContent>
                <CardFooter>
                  <Button asChild className="w-full" variant={plan.most_popular ? 'default' : 'outline'}>
                    <Link href={route('spaces.setup.details', { plan: plan.id })}>
                      Seleccionar Plan
                    </Link>
                  </Button>
                </CardFooter>
              </Card>
            ))}
          </div>

          <div className="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
            <p>Todos los planes incluyen un período de prueba de 14 días. No se requiere tarjeta de crédito.</p>
            <p className="mt-2">
              ¿Ya tienes un espacio?{' '}
              <Link href={route('spaces.index')} className="text-primary hover:underline">
                Volver a mis espacios
              </Link>
            </p>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}