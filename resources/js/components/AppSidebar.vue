<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Database, LayoutGrid, Network, Share2, ShieldCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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
import tree from '@/routes/tree';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

const page = usePage();
const permissions = computed(() => page.props.auth.permissions);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (permissions.value.includes('view_dashboard')) {
        items.push({
            title: 'Family Tree',
            href: tree.show(),
            icon: Network,
        });
    }

    return items;
});

const adminNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (permissions.value.includes('manage_users')) {
        items.push({
            title: 'Users',
            href: admin.users.index(),
            icon: Users,
        });
    }

    if (permissions.value.includes('manage_roles')) {
        items.push({
            title: 'Roles',
            href: admin.roles.index(),
            icon: ShieldCheck,
        });
    }

    if (permissions.value.includes('manage_master_data')) {
        items.push({
            title: 'Social Media',
            href: admin.socialMedias.index(),
            icon: Share2,
        });

        items.push({
            title: 'Data Details',
            href: admin.dataDetails.index(),
            icon: Database,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
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

            <template v-if="adminNavItems.length > 0">
                <div class="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider mt-4">
                    Admin Panel
                </div>
                <NavMain :items="adminNavItems" />
            </template>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
