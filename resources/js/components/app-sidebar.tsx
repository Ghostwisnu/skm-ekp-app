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
import type { NavItem } from '@/types';

const ICON_MAP: Record<string, React.ElementType> = {
    LayoutGrid,
    Truck,
    Wallet,
    Building2,
    Receipt,
    BarChart3,
    Settings,
    Users: Settings,
    Calendar: LayoutGrid,
    FileText: Receipt,
    Package: LayoutGrid,
    MapPin: LayoutGrid,
    Clock: LayoutGrid,
    Database: Settings,
    Shield: Settings,
    Plus: LayoutGrid,
    Minus: LayoutGrid,
    Edit: LayoutGrid,
    Trash2: LayoutGrid,
    Search: LayoutGrid,
    Filter: LayoutGrid,
    Download: LayoutGrid,
    Upload: LayoutGrid,
    RefreshCw: LayoutGrid,
    CheckCircle: LayoutGrid,
    AlertCircle: LayoutGrid,
};

function iconByName(name: string | null): React.ElementType | null {
    return name ? (ICON_MAP[name] || LayoutGrid) : null;
}

type RawMenuItem = {
    id: number;
    name: string;
    route: string | null;
    icon: string | null;
    sortOrder: number;
    isActive: boolean;
    children?: RawMenuItem[];
};

function toNavItem(raw: RawMenuItem): NavItem {
    return {
        title: raw.name,
        href: raw.route ?? '#',
        icon: iconByName(raw.icon),
        isActive: false,
        children: raw.children?.map(toNavItem),
    };
}

export function AppSidebar() {
    const { auth } = usePage<{ auth: any }>().props;
    const rawMenus = (usePage<{ props: { navigationMenus?: RawMenuItem[] } }>().props as any).navigationMenus ?? [];

    const navItems: NavItem[] = rawMenus.map(toNavItem);

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
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}