<script setup>
import { ref, computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    hideSidebar: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const isDarkMode = ref(false);
const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

const menuUtama = [
    {
        label: 'Dashboard',
        route: 'dashboard',
        activePattern: 'dashboard',
        iconSvg: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>`,
    },
    {
        label: 'Presisi Layar Wisuda',
        route: 'admin.stage-layout.edit',
        activePattern: 'admin.stage-layout.*',
        iconSvg: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>`,
    },
    {
        label: 'Operator Stage Console',
        route: 'panitia.stage-control',
        activePattern: 'panitia.stage-control*',
        iconSvg: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>`,
    },
];

const menuAdministrasi = [
    {
        label: 'Periode Wisuda',
        route: 'admin.periode.index',
        activePattern: 'admin.periode.*',
        iconSvg: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`,
    },
    {
        label: 'Data Wisudawan',
        route: 'admin.buku-kenangan.index',
        activePattern: 'admin.buku-kenangan.*',
        iconSvg: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>`,
    },
];

const isActive = (pattern) => {
    try {
        return route().current(pattern);
    } catch {
        return false;
    }
};

const shouldHideSidebar = computed(() => props.hideSidebar);
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
        
        <!-- TOP NAVBAR HEADER -->
        <nav class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800 transition-colors duration-300">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        
                        <!-- Logo Branding -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-base shadow-sm">
                                    P
                                </div>
                            </Link>
                        </div>

                        <!-- Header Nav Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <Link
                                :href="route('dashboard')"
                                :class="[
                                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out',
                                    route().current('dashboard')
                                        ? 'border-indigo-400 text-gray-900 dark:text-gray-100 focus:border-indigo-700'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                                ]"
                            >
                                Dashboard
                            </Link>

                            <Link
                                :href="route('panitia.presensi')"
                                :class="[
                                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out',
                                    route().current('panitia.presensi')
                                        ? 'border-indigo-400 text-gray-900 dark:text-gray-100 focus:border-indigo-700'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                                ]"
                            >
                                Presensi Gate
                            </Link>

                            <Link
                                :href="route('panitia.presensi.wisudawan')"
                                :class="[
                                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out',
                                    route().current('panitia.presensi.wisudawan')
                                        ? 'border-indigo-400 text-gray-900 dark:text-gray-100 focus:border-indigo-700'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                                ]"
                            >
                                Presensi Wisudawan
                            </Link>
                        </div>
                    </div>

                    <!-- Right Top Bar Elements -->
                    <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-3">
                        
                        <!-- Mode Gelap Toggle Button -->
                        <button
                            @click="toggleDarkMode"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <span>Mode Gelap</span>
                        </button>

                        <!-- User Profile Dropdown -->
                        <div class="relative ms-1">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                    >
                                        <span>{{ user?.name || 'Panitia Wisuda' }}</span>
                                        <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                        <p class="text-xs text-gray-400">Pengguna:</p>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ user?.email }}</p>
                                    </div>
                                    <DropdownLink :href="route('profile.edit')">
                                        Profil Saya
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                    </div>
                </div>
            </div>
        </nav>

        <!-- SUB HEADER TITLE -->
        <header class="bg-white shadow dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Sistem Informasi Wisuda
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Halo, <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ user?.name || 'Panitia Wisuda' }}</span> (OPERATOR / PANITIA)
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT BODY -->
        <main class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <!-- Layout with Side Menu -->
                <div v-if="!shouldHideSidebar" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- Left Column Navigation Box -->
                    <div class="lg:col-span-3">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sticky top-6">
                            
                            <!-- MENU UTAMA -->
                            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-3 mb-3">
                                Menu Utama
                            </p>
                            <nav class="space-y-1">
                                <Link
                                    v-for="item in menuUtama"
                                    :key="item.route"
                                    :href="route(item.route)"
                                    :class="[
                                        'w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all',
                                        isActive(item.activePattern)
                                            ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400 shadow-sm font-semibold'
                                            : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50'
                                    ]"
                                >
                                    <span v-html="item.iconSvg"></span>
                                    <span>{{ item.label }}</span>
                                </Link>
                            </nav>

                            <!-- ADMINISTRASI -->
                            <div class="border-t border-gray-100 dark:border-gray-700 my-4"></div>
                            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-3 mb-3">
                                Administrasi
                            </p>
                            <nav class="space-y-1">
                                <Link
                                    v-for="item in menuAdministrasi"
                                    :key="item.route"
                                    :href="route(item.route)"
                                    :class="[
                                        'w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all',
                                        isActive(item.activePattern)
                                            ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400 shadow-sm font-semibold'
                                            : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50'
                                    ]"
                                >
                                    <span v-html="item.iconSvg"></span>
                                    <span>{{ item.label }}</span>
                                </Link>
                            </nav>

                        </div>
                    </div>

                    <!-- Right Column Main Content Slot -->
                    <div class="lg:col-span-9 min-w-0 space-y-6">
                        <slot />
                    </div>

                </div>

                <!-- Full Width Layout without Side Menu -->
                <div v-else class="space-y-6">
                    <slot />
                </div>

            </div>
        </main>

    </div>
</template>
