import { Breadcrumbs } from '@/components/breadcrumbs';
import SpaceSwitcher from '@/components/SpaceSwitcher';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useSpaces } from '@/hooks/useSpaces';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/react';

interface SharedProps {
    auth?: {
        user?: {
            id: number;
            name: string;
            email: string;
        } | null;
    };
    currentSpace?: {
        id: string;
        name: string;
        domains: { domain: string }[];
    };
    tenant?: {
        id: string;
        name: string;
        domains: { domain: string }[];
    };
    userSpaces?: Array<{
        id: string;
        name: string;
        domains: { domain: string }[];
    }>;
    [key: string]: unknown;
}

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { currentSpace, tenant, userSpaces, auth } = usePage<SharedProps>().props;

    // Siempre llamar al hook, pero el hook internamente decide si hacer la petición
    const { spaces, loading } = useSpaces();

    // Usar currentSpace si está disponible, sino usar tenant por compatibilidad
    const activeSpace = currentSpace || tenant;

    // Usar espacios del hook si están disponibles y estamos autenticados, sino usar userSpaces
    const shouldUseApiSpaces = auth?.user && (currentSpace || tenant) && !loading && spaces.length > 0;
    const availableSpaces = shouldUseApiSpaces ? spaces : userSpaces || [];

    return (
        <header className="border-sidebar-border/50 flex h-16 shrink-0 items-center justify-between gap-2 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="flex items-center gap-4">
                {/* Mostrar SpaceSwitcher cuando hay un espacio activo y más de un espacio disponible */}
                {activeSpace && availableSpaces.length > 0 && !loading && <SpaceSwitcher currentSpace={activeSpace} spaces={availableSpaces} />}
            </div>
        </header>
    );
}
