import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    locale?: {
        current: string;
        available: Record<string, string>;
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Project {
    id: number;
    name: string;
    description?: string;
    status: string;
    [key: string]: unknown;
}

export interface Task {
    id: number;
    title: string;
    description?: string | null;
    project_id: number;
    user_id: number;
    parent_task_id?: number | null;
    status: 'pending' | 'in_progress' | 'completed';
    priority: number;
    position: number;
    board_column?: string | null;
    estimated_hours?: number | null;
    due_date?: string | null;
    completed_at?: string | null;
    created_at: string;
    updated_at: string;
    project?: Project;
    user?: User;
    tags?: Tag[];
    comments?: Comment[];
    subtasks?: Task[];
    subtasks_count?: number;
    comments_count?: number;
    time_entries_count?: number;
    total_logged_hours?: number;
    [key: string]: unknown;
}

export interface Tag {
    id: number;
    name: string;
    slug: string;
    [key: string]: unknown;
}

export interface Comment {
    id: number;
    content: string;
    user_id: number;
    task_id: number;
    user?: User;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

export type PageProps<T = Record<string, unknown>> = T & {
    auth: Auth;
    ziggy: Config & { location: string };
    locale?: {
        current: string;
        available: Record<string, string>;
    };
};
