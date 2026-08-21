<script setup>
import { ref, computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

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
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300">

        <!-- TOP NAVBAR HEADER (Minimal, no sidebar) -->
        <nav class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800 transition-colors duration-300">
            <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">

                    <!-- Logo & Nav Links -->
                    <div class="flex items-center gap-8">
                        <Link :href="route('dashboard')" class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-base shadow-sm">
                                P
                            </div>
                        </Link>

                        <div class="hidden sm:flex items-center gap-6">
                            <Link
                                :href="route('panitia.presensi')"
                                :class="[
                                    'text-sm font-medium transition',
                                    route().current('panitia.presensi') || route().current('panitia.presensi.gate')
                                        ? 'text-indigo-600 dark:text-indigo-400 font-bold'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                                ]"
                            >
                                Gate Scanner
                            </Link>
                            <Link
                                :href="route('panitia.presensi.wisudawan')"
                                :class="[
                                    'text-sm font-medium transition',
                                    route().current('panitia.presensi.wisudawan')
                                        ? 'text-indigo-600 dark:text-indigo-400 font-bold'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                                ]"
                            >
                                Data Wisudawan
                            </Link>
                        </div>
                    </div>

                    <!-- Right: Dark Mode + User Dropdown -->
                    <div class="flex items-center gap-3">
                        <button
                            @click="toggleDarkMode"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <span class="hidden sm:inline">Mode Gelap</span>
                        </button>

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                >
                                    <span>{{ user?.name || 'Panitia Wisuda' }}</span>
                                    <svg class="-me-0.5 ms-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                    <p class="text-xs text-gray-400">Pengguna:</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ user?.email }}</p>
                                </div>
                                <DropdownLink :href="route('profile.edit')">Profil Saya</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                </div>
            </div>
        </nav>

        <!-- MAIN CONTENT (Full Width) -->
        <main class="py-6">
            <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>

    </div>
</template>
