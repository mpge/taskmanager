<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CreditCard, FolderGit2, ListTodo, Sprout, Sun } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Today',
            href: '/dashboard',
            icon: Sun,
        },
        {
            title: 'Tasks',
            href: '/tasks',
            icon: ListTodo,
        },
        {
            title: 'Habits',
            href: '/habits',
            icon: Sprout,
        },
    ];

    if (page.props.billingEnabled) {
        items.push({
            title: 'Billing',
            href: '/billing',
            icon: CreditCard,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Source',
        href: 'https://github.com/mpge/tend',
        icon: FolderGit2,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
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
