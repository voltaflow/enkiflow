import Heading from '@/components/heading';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { Database } from 'lucide-react';
import { type PropsWithChildren } from 'react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: '/settings/profile',
        icon: null,
    },
    {
        title: 'Password',
        href: '/settings/password',
        icon: null,
    },
    {
        title: 'Appearance',
        href: '/settings/appearance',
        icon: null,
    },
    {
        title: 'Datos Demo',
        href: '/settings/developer/demo-data',
        icon: Database,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <div className="px-4 py-6">
            <Heading title="Settings" description="Manage your profile and account settings" />

            <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {sidebarNavItems.map((item, index) => (
                            <Link
                                key={`${item.href}-${index}`}
                                href={item.href}
                                className={cn(
                                    'hover:bg-accent hover:text-accent-foreground flex w-full items-center justify-start gap-2 rounded-md px-2 py-1.5 text-sm font-medium',
                                    {
                                        'bg-muted': currentPath === item.href,
                                    },
                                )}
                                preserveState
                                preserveScroll
                            >
                                {item.icon && <item.icon className="h-4 w-4" />}
                                {item.title}
                            </Link>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 md:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">{children}</section>
                </div>
            </div>
        </div>
    );
}
