<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Verifikator Keuangan Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                    Verifikator Tanggungan Keuangan
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Validasi Pelunasan Biaya Wisuda & UKT Bebas Tanggungan
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/60 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Panel Verifikasi Keuangan</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pemeriksaan bukti pembayaran dan status lunas UKT seluruh calon wisudawan.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
