<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useAuthUtils } from '@/composables/useAuthUtils';
import { dashboard, documentation } from '@/routes';
import { index as auditsIndex } from '@/routes/audits';
import { index as categoriesIndex } from '@/routes/categories';
import { index as inventoryIndex } from '@/routes/inventory';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as productsIndex } from '@/routes/products';
import { index as rolesIndex } from '@/routes/roles';
import { index as schoolsIndex } from '@/routes/schools';
import { index as usersIndex } from '@/routes/users';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Activity, BookOpen, Folder, Key, LayoutGrid, Package, School, Shield, Users, Warehouse } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const { filterNavigationByPermissions } = useAuthUtils();

const allMainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Schools',
        href: schoolsIndex(),
        icon: School,
    },
    {
        title: 'Categories',
        href: categoriesIndex(),
        icon: Folder,
    },
    {
        title: 'Products',
        href: productsIndex(),
        icon: Package,
    },
    {
        title: 'Inventory',
        href: inventoryIndex(),
        icon: Warehouse,
    },
    {
        title: 'Users',
        href: usersIndex(),
        icon: Users,
    },
    {
        title: 'Roles',
        href: rolesIndex(),
        icon: Shield,
    },
    {
        title: 'Permissions',
        href: permissionsIndex(),
        icon: Key,
    },
    {
        title: 'Audits',
        href: auditsIndex(),
        icon: Activity,
    },
];

const allFooterNavItems: NavItem[] = [
    {
        title: 'Documentation',
        href: documentation(),
        icon: BookOpen,
    },
];

// Filter navigation items based on user permissions
const mainNavItems = computed(() => {
    return filterNavigationByPermissions(allMainNavItems);
});

const footerNavItems = computed(() => {
    return filterNavigationByPermissions(allFooterNavItems);
});
</script>

<template>
    <Sidebar collapsible="icon" variant="floating">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
