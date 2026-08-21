<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
const user = computed(() => page.props.auth.user);

const roleLabel = computed(() => {
    switch (user.value?.role) {
        case 'admin_utama': return 'Admin Utama';
        case 'security': return 'Security Officer';
        case 'receptionist': return 'Receptionist';
        case 'wisudawan': return 'Wisudawan';
        default: return 'Pengguna';
    }
});

const roleBadgeClass = computed(() => {
    switch (user.value?.role) {
        case 'admin_utama': return 'bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300 border-purple-200 dark:border-purple-700';
        case 'security': return 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-300 dark:border-amber-700';
        case 'receptionist': return 'bg-purple-500/20 text-purple-600 dark:text-purple-400 border-purple-300 dark:border-purple-700';
        default: return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700';
    }
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors">
            <nav class="border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sticky top-0 z-40">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center">
                        <div class="flex items-center space-x-6">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-500 flex items-center justify-center shadow-md shadow-indigo-500/20 text-white font-bold text-lg">
                                        P
                                    </div>
                                    <div class="hidden md:block">
                                        <span class="font-extrabold text-base tracking-tight bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-800 dark:from-white dark:via-indigo-200 dark:to-slate-300 bg-clip-text text-transparent">
                                            POLITEKNIK INDONUSA
                                        </span>
                                        <span class="block text-[10px] font-semibold tracking-wider text-indigo-600 dark:text-indigo-400 uppercase">
                                            Wisuda Smart System
                                        </span>
                                    </div>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-4 sm:flex sm:items-center">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard') || route().current('*.dashboard')">
                                    Dashboard
                                </NavLink>

                                <template v-if="user?.role === 'admin_utama'">
                                    <NavLink :href="route('admin.monitoring-presensi', { status: 'belum_hadir' })" :active="route().current('admin.monitoring-presensi*')">
                                        ⏳ Peserta Belum Hadir
                                    </NavLink>
                                    <NavLink :href="route('admin.duty-assignments.index')" :active="route().current('admin.duty-assignments.*')">
                                        Tugas Scan SIMPEG
                                    </NavLink>
                                    <NavLink :href="route('admin.periode.index')" :active="route().current('admin.periode.*')">
                                        Periode Wisuda
                                    </NavLink>
                                    <NavLink :href="route('admin.stage-layout.edit')" :active="route().current('admin.stage-layout.*')">
                                        Presisi Layar
                                    </NavLink>
                                    <NavLink :href="route('admin.buku-kenangan.index')" :active="route().current('admin.buku-kenangan.*')">
                                        Buku Kenangan
                                    </NavLink>
                                    <NavLink :href="route('kiosk.display')" target="_blank">
                                         Kiosk TV
                                    </NavLink>
                                </template>

                                <template v-if="user?.role === 'security'">
                                    <NavLink :href="route('security.scan')" :active="route().current('security.scan')">
                                         Scan Gate Security
                                    </NavLink>
                                </template>

                                <template v-if="user?.role === 'receptionist'">
                                    <NavLink :href="route('receptionist.scan')" :active="route().current('receptionist.scan')">
                                        ‍ Scan Reception & Snack
                                    </NavLink>
                                </template>


                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-4">
                            <!-- Role Badge -->
                            <span :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border', roleBadgeClass]">
                                <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5 animate-pulse"></span>
                                {{ roleLabel }}
                            </span>

                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                        >
                                            <div class="w-7 h-7 rounded-full bg-indigo-600 text-white font-semibold text-xs flex items-center justify-center">
                                                {{ user?.name?.charAt(0).toUpperCase() }}
                                            </div>
                                            <span>{{ user?.name }}</span>
                                            <svg
                                                class="h-4 w-4 text-slate-400"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700">
                                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Login sebagai:</p>
                                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ user?.email }}</p>
                                        </div>

                                        <DropdownLink :href="route('profile.edit')">
                                            Pengaturan Profil
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Keluar Sistem
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                    class="sm:hidden border-t border-slate-200 dark:border-slate-800"
                >
                    <div class="space-y-1 pb-3 pt-2 px-4">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        <template v-if="user?.role === 'admin_utama'">
                            <ResponsiveNavLink :href="route('admin.periode.index')" :active="route().current('admin.periode.*')">
                                Periode Wisuda
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.stage-layout.edit')" :active="route().current('admin.stage-layout.*')">
                                Presisi Layar
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.buku-kenangan.index')" :active="route().current('admin.buku-kenangan.*')">
                                Buku Kenangan
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('panitia.stage-control')" :active="route().current('panitia.stage-control')">
                                Stage Console
                            </ResponsiveNavLink>
                        </template>

                    </div>

                    <div class="border-t border-slate-200 pb-3 pt-4 dark:border-slate-800 px-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-600 text-white font-semibold text-sm flex items-center justify-center">
                                {{ user?.name?.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ user?.name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ user?.email }}</div>
                            </div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Pengaturan Profil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Keluar Sistem
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/60" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
