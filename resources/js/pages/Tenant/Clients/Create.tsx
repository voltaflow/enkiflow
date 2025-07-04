import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import React from 'react';

interface FormData {
    name: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    state: string;
    country: string;
    postal_code: string;
    website: string;
    contact_name: string;
    contact_email: string;
    contact_phone: string;
    notes: string;
    timezone: string;
    currency: string;
    is_active: boolean;
}

const timezones = [
    { value: 'America/New_York', label: 'Eastern Time (US & Canada)' },
    { value: 'America/Chicago', label: 'Central Time (US & Canada)' },
    { value: 'America/Denver', label: 'Mountain Time (US & Canada)' },
    { value: 'America/Los_Angeles', label: 'Pacific Time (US & Canada)' },
    { value: 'America/Mexico_City', label: 'Mexico City' },
    { value: 'America/Sao_Paulo', label: 'Brasilia' },
    { value: 'America/Buenos_Aires', label: 'Buenos Aires' },
    { value: 'Europe/London', label: 'London' },
    { value: 'Europe/Madrid', label: 'Madrid' },
    { value: 'Europe/Paris', label: 'Paris' },
    { value: 'Europe/Berlin', label: 'Berlin' },
    { value: 'Asia/Tokyo', label: 'Tokyo' },
    { value: 'Asia/Shanghai', label: 'Beijing' },
    { value: 'Australia/Sydney', label: 'Sydney' },
];

const currencies = [
    { value: 'USD', label: 'USD - US Dollar' },
    { value: 'EUR', label: 'EUR - Euro' },
    { value: 'GBP', label: 'GBP - British Pound' },
    { value: 'MXN', label: 'MXN - Mexican Peso' },
    { value: 'BRL', label: 'BRL - Brazilian Real' },
    { value: 'ARS', label: 'ARS - Argentine Peso' },
    { value: 'CAD', label: 'CAD - Canadian Dollar' },
    { value: 'JPY', label: 'JPY - Japanese Yen' },
    { value: 'CNY', label: 'CNY - Chinese Yuan' },
    { value: 'AUD', label: 'AUD - Australian Dollar' },
];

export default function Create() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        country: '',
        postal_code: '',
        website: '',
        contact_name: '',
        contact_email: '',
        contact_phone: '',
        notes: '',
        timezone: 'America/Mexico_City',
        currency: 'USD',
        is_active: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('tenant.clients.store'));
    };

    return (
        <AppLayout>
            <Head title="Nuevo Cliente" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-4">
                        <Link href={route('tenant.clients.index')}>
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Nuevo Cliente</h1>
                            <p className="text-muted-foreground mt-1">Registra la información de un nuevo cliente</p>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información General</CardTitle>
                                <CardDescription>Datos básicos del cliente</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nombre del Cliente *</Label>
                                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                        {errors.name && <p className="text-destructive text-sm">{errors.name}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="email">Correo Electrónico</Label>
                                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                                        {errors.email && <p className="text-destructive text-sm">{errors.email}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="phone">Teléfono</Label>
                                        <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                                        {errors.phone && <p className="text-destructive text-sm">{errors.phone}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="website">Sitio Web</Label>
                                        <Input
                                            id="website"
                                            type="url"
                                            placeholder="https://ejemplo.com"
                                            value={data.website}
                                            onChange={(e) => setData('website', e.target.value)}
                                        />
                                        {errors.website && <p className="text-destructive text-sm">{errors.website}</p>}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="address">Dirección</Label>
                                    <Input id="address" value={data.address} onChange={(e) => setData('address', e.target.value)} />
                                    {errors.address && <p className="text-destructive text-sm">{errors.address}</p>}
                                </div>

                                <div className="grid gap-4 md:grid-cols-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="city">Ciudad</Label>
                                        <Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} />
                                        {errors.city && <p className="text-destructive text-sm">{errors.city}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="state">Estado/Provincia</Label>
                                        <Input id="state" value={data.state} onChange={(e) => setData('state', e.target.value)} />
                                        {errors.state && <p className="text-destructive text-sm">{errors.state}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="country">País</Label>
                                        <Input id="country" value={data.country} onChange={(e) => setData('country', e.target.value)} />
                                        {errors.country && <p className="text-destructive text-sm">{errors.country}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="postal_code">Código Postal</Label>
                                        <Input id="postal_code" value={data.postal_code} onChange={(e) => setData('postal_code', e.target.value)} />
                                        {errors.postal_code && <p className="text-destructive text-sm">{errors.postal_code}</p>}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Información de Contacto</CardTitle>
                                <CardDescription>Persona de contacto principal</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="contact_name">Nombre de Contacto</Label>
                                        <Input
                                            id="contact_name"
                                            value={data.contact_name}
                                            onChange={(e) => setData('contact_name', e.target.value)}
                                        />
                                        {errors.contact_name && <p className="text-destructive text-sm">{errors.contact_name}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="contact_email">Email de Contacto</Label>
                                        <Input
                                            id="contact_email"
                                            type="email"
                                            value={data.contact_email}
                                            onChange={(e) => setData('contact_email', e.target.value)}
                                        />
                                        {errors.contact_email && <p className="text-destructive text-sm">{errors.contact_email}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="contact_phone">Teléfono de Contacto</Label>
                                        <Input
                                            id="contact_phone"
                                            value={data.contact_phone}
                                            onChange={(e) => setData('contact_phone', e.target.value)}
                                        />
                                        {errors.contact_phone && <p className="text-destructive text-sm">{errors.contact_phone}</p>}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Configuración</CardTitle>
                                <CardDescription>Preferencias y configuración adicional</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="timezone">Zona Horaria</Label>
                                        <Select value={data.timezone} onValueChange={(value) => setData('timezone', value)}>
                                            <SelectTrigger id="timezone">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {timezones.map((tz) => (
                                                    <SelectItem key={tz.value} value={tz.value}>
                                                        {tz.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.timezone && <p className="text-destructive text-sm">{errors.timezone}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="currency">Moneda</Label>
                                        <Select value={data.currency} onValueChange={(value) => setData('currency', value)}>
                                            <SelectTrigger id="currency">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {currencies.map((curr) => (
                                                    <SelectItem key={curr.value} value={curr.value}>
                                                        {curr.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.currency && <p className="text-destructive text-sm">{errors.currency}</p>}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="notes">Notas</Label>
                                    <Textarea id="notes" rows={4} value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                                    {errors.notes && <p className="text-destructive text-sm">{errors.notes}</p>}
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                                    />
                                    <Label
                                        htmlFor="is_active"
                                        className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                    >
                                        Cliente Activo
                                    </Label>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex gap-4">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Guardando...' : 'Guardar Cliente'}
                            </Button>
                            <Link href={route('tenant.clients.index')}>
                                <Button variant="outline" type="button">
                                    Cancelar
                                </Button>
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
