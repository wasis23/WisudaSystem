<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    wisudawans: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');

const handleFilter = () => {
    router.get(route('verifikator.berkas.index'), {
        search: search.value,
        status: selectedStatus.value,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Antrean Verifikasi Berkas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Pusat Verifikasi Wisudawan</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Daftar calon wisudawan dan antrean pemeriksaan dokumen akademik.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Stat Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div @click="selectedStatus = ''; handleFilter()" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm cursor-pointer hover:border-indigo-500 transition">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Pendaftar</span>
                        <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.total }}</h3>
                    </div>
                    <div @click="selectedStatus = 'pending'; handleFilter()" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-amber-200 dark:border-amber-700/60 shadow-sm cursor-pointer hover:border-amber-500 transition">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Menunggu Verifikasi</span>
                        <h3 class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ stats.pending }}</h3>
                    </div>
                    <div @click="selectedStatus = 'verified'; handleFilter()" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-emerald-200 dark:border-emerald-700/60 shadow-sm cursor-pointer hover:border-emerald-500 transition">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Disetujui (Verified)</span>
                        <h3 class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ stats.verified }}</h3>
                    </div>
                    <div @click="selectedStatus = 'rejected'; handleFilter()" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-red-200 dark:border-red-700/60 shadow-sm cursor-pointer hover:border-red-500 transition">
                        <span class="text-xs font-bold uppercase tracking-wider text-red-600 dark:text-red-400">Ditolak (Rejected)</span>
                        <h3 class="text-3xl font-extrabold text-red-600 dark:text-red-400 mt-2">{{ stats.rejected }}</h3>
                    </div>
                </div>

                <!-- Filters & Search -->
                <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <input
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text"
                            placeholder="Cari NIM atau Nama..."
                            class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm w-full md:w-64"
                        />
                        <button @click="handleFilter" class="px-4 py-2 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition">
                            Cari
                        </button>
                    </div>

                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <span class="text-xs font-bold text-slate-500">Filter Status:</span>
                        <select v-model="selectedStatus" @change="handleFilter" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="verified">Verified</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 dark:bg-slate-900/60 text-xs uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                <tr>
                                    <th class="px-6 py-4">NIM</th>
                                    <th class="px-6 py-4">Nama Lengkap</th>
                                    <th class="px-6 py-4">Program Studi</th>
                                    <th class="px-6 py-4">IPK</th>
                                    <th class="px-6 py-4">Dokumen</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700/60">
                                <tr v-for="w in wisudawans.data" :key="w.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                                    <td class="px-6 py-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ w.nim }}</td>
                                    <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ w.nama_lengkap }}</td>
                                    <td class="px-6 py-4">{{ w.program_studi?.nama_prodi }}</td>
                                    <td class="px-6 py-4 font-mono font-bold">{{ w.ipk }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-lg">
                                            {{ w.berkas ? w.berkas.length : 0 }} Berkas
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="[
                                                'px-3 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1.5 uppercase',
                                                w.status_verifikasi === 'verified' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300' :
                                                w.status_verifikasi === 'rejected' ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/40 dark:text-red-300' :
                                                'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/40 dark:text-amber-300'
                                            ]"
                                        >
                                            {{ w.status_verifikasi }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link
                                            :href="route('verifikator.berkas.show', w.id)"
                                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg transition inline-block shadow-sm"
                                        >
                                            Periksa Berkas →
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!wisudawans.data || wisudawans.data.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-400">Tidak ada pendaftar wisuda ditemukan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
