<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Verifikator Prodi Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                        Verifikator Akademik Prodi
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Program Studi: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ user.program_studi?.nama_prodi || 'Semua Prodi' }}</span>
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Persetujuan Berkas & Judul TA</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Verifikasi transkrip nilai, IPK, judul tugas akhir, serta surat bebas pinjam pustaka untuk wisudawan prodi Anda.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
