import { Link } from '@inertiajs/react';
import {
    Banknote,
    BookOpen,
    Calculator,
    FilePenLine,
    FileText,
    FolderGit2,
    LayoutGrid,
    Logs,
    PackageSearch,
    Store,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard @',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Cashier',
        href: '/cashier',
        icon: Calculator,
    },
    {
        title: 'Transaction',
        href: '/transaction',
        icon: Logs,
    },
    {
        title: 'Purchase Order @',
        href: '/purchase-order',
        icon: FilePenLine,
    },
    {
        title: 'Settlement @',
        href: '/settlement',
        icon: Banknote,
    },
    {
        title: 'Product',
        href: '/product',
        icon: PackageSearch,
    },
    {
        title: 'Branch Store',
        href: '/store',
        icon: Store,
    },
    {
        title: 'Customer',
        href: '/customer',
        icon: Users,
    },
    {
        title: 'Report @',
        href: '/report',
        icon: FileText,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
