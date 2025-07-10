import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BarChart3, BookOpen, Building2, Clock, Folder, LayoutGrid, Mail, Users, type LucideIcon } from 'lucide-react';
import { forwardRef } from 'react';
import AppLogo from './app-logo';

// Icon components
const SpaceIcon = forwardRef<SVGSVGElement, React.SVGProps<SVGSVGElement>>(function SpaceIcon(props, ref) {
    return (
        <svg
            ref={ref}
            {...props}
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z" />
            <path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4" />
            <path d="M9 14h6" />
        </svg>
    );
}) as LucideIcon;

const TaskIcon = forwardRef<SVGSVGElement, React.SVGProps<SVGSVGElement>>(function TaskIcon(props, ref) {
    return (
        <svg
            ref={ref}
            {...props}
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <rect width="8" height="4" x="8" y="2" rx="1" ry="1" />
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
            <path d="m9 14 2 2 4-4" />
        </svg>
    );
}) as LucideIcon;

const ProjectIcon = forwardRef<SVGSVGElement, React.SVGProps<SVGSVGElement>>(function ProjectIcon(props, ref) {
    return (
        <svg
            ref={ref}
            {...props}
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
            <line x1="12" x2="12" y1="10" y2="14" />
            <line x1="10" x2="14" y1="12" y2="12" />
        </svg>
    );
}) as LucideIcon;

const TimesheetIcon = forwardRef<SVGSVGElement, React.SVGProps<SVGSVGElement>>(function TimesheetIcon(props, ref) {
    return (
        <svg
            ref={ref}
            {...props}
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <rect width="18" height="18" x="3" y="3" rx="2" />
            <path d="M3 9h18" />
            <path d="M3 15h18" />
            <path d="M9 3v18" />
            <path d="M15 3v18" />
        </svg>
    );
}) as LucideIcon;

export function AppSidebar() {
    const { tenant, auth } = usePage().props as any;
    const isTenant = !!tenant;
    const isGuest = auth?.isGuest || false;
    const isManager = auth?.isManager || false;
    const isAdmin = auth?.isAdmin || false;
    const isMember = auth?.isMember || false;
    const isOwner = auth?.isOwner || false;
    const spaceRole = auth?.spaceRole;

    // Build navigation items based on context
    const mainNavItems: NavItem[] = [];

    if (!isTenant) {
        // Main domain navigation - only use routes that exist on main domain
        mainNavItems.push(
            {
                title: 'Dashboard',
                href: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Espacios',
                href: '/spaces',
                icon: SpaceIcon,
            },
        );
    } else {
        // Tenant domain navigation - safely build routes
        const tenantNavItems: NavItem[] = [
            {
                title: 'Dashboard',
                href: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Tareas',
                href: '/tasks',
                icon: TaskIcon,
            },
        ];

        // Projects - available to all roles
        tenantNavItems.push({
            title: 'Proyectos',
            href: '/projects',
            icon: ProjectIcon,
        });

        // Time tracking - available to all roles
        tenantNavItems.push({
            title: 'Registro de Tiempo',
            href: '/time',
            icon: Clock,
        });

        // Add items based on user permissions
        if (!isGuest && !isMember) {
            // Clients - available to ADMIN and OWNER only (not for MEMBER)
            if (isAdmin || isOwner) {
                tenantNavItems.push({
                    title: 'Clientes',
                    href: '/clients',
                    icon: Building2,
                });
            }

            // Reports - available to MANAGER, ADMIN and OWNER (not for MEMBER)
            if (isManager || isAdmin || isOwner) {
                tenantNavItems.push({
                    title: 'Reportes',
                    href: '/reports',
                    icon: BarChart3,
                });
            }

            // Users and Invitations - only for ADMIN and OWNER
            if (isAdmin || isOwner) {
                tenantNavItems.push(
                    {
                        title: 'Usuarios',
                        href: '/users',
                        icon: Users,
                    },
                    {
                        title: 'Invitaciones',
                        href: '/invitations',
                        icon: Mail,
                    },
                );
            }
        }

        // Add tenant navigation items
        mainNavItems.push(...tenantNavItems);
    }

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/react-starter-kit',
            icon: Folder,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

    const dashboardHref = isTenant ? '/dashboard' : '/dashboard';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
