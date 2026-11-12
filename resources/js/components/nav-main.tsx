import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({ items }: { items: NavItem[] }) {
    const { isCurrentUrl } = useCurrentUrl();

    function renderMenu(item: NavItem, depth = 0) {
        const hasChildren = item.children && item.children.length > 0;
        const href = item.href ?? '#';

        if (hasChildren) {
            return (
                <SidebarMenuItem key={item.title}>
                    <SidebarMenuButton>
                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                    </SidebarMenuButton>
                    <SidebarMenuSub>
                        {item.children!.map((child) => (
                            <SidebarMenuSubItem key={child.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl(child.href ?? '#')}
                                    tooltip={{ children: child.title }}
                                >
                                    <Link href={child.href ?? '#'}>
                                        {child.icon && <child.icon />}
                                        <span>{child.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuSubItem>
                        ))}
                    </SidebarMenuSub>
                </SidebarMenuItem>
            );
        }

        return (
            <SidebarMenuItem key={item.title}>
                <SidebarMenuButton
                    asChild
                    isActive={isCurrentUrl(href)}
                    tooltip={{ children: item.title }}
                >
                    <Link href={href}>
                        {item.icon && <item.icon />}
                        <span>{item.title}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        );
    }

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => renderMenu(item))}
            </SidebarMenu>
        </SidebarGroup>
    );
}