import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Building2,
    LayoutGrid,
    Receipt,
    Settings,
    Truck,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
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
import type { Auth, NavItem } from '@/types';

const navByRole = (role: Auth['user']['role']): NavItem[] => {
    const items: (NavItem & { roles: Auth['user']['role'][] })[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
            roles: ['admin', 'operator', 'keuangan'],
        },
        {
            title: 'Trip',
            href: '/trips',
            icon: Truck,
            roles: ['admin', 'operator'],
        },
        {
            title: 'Biaya Harian',
            href: '/daily-costs',
            icon: Wallet,
            roles: ['admin', 'operator'],
        },
        {
            title: 'Client',
            href: '/clients',
            icon: Building2,
            roles: ['admin', 'operator', 'keuangan'],
        },
        {
            title: 'Kendaraan',
            href: '/vehicles',
            icon: Truck,
            roles: ['admin', 'operator', 'keuangan'],
        },
        {
            title: 'Invoice',
            href: '/invoices',
            icon: Receipt,
            roles: ['admin', 'keuangan'],
        },
        {
            title: 'Laporan Plat',
            href: '/reports/vehicles',
            icon: BarChart3,
            roles: ['admin', 'keuangan'],
        },
        {
            title: 'Pengguna & Pengaturan',
            href: '/admin/users',
            icon: Settings,
            roles: ['admin'],
        },
    ];

    return items.filter((item) => item.roles.includes(role));
};
export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
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
                <NavMain items={navByRole(auth.user.role)} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
